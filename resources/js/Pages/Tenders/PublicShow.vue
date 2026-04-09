<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import axios from "axios";
import PublicNavbar from "@/Components/PublicNavbar.vue";

const props = defineProps({
  tender: {
    type: Object,
    required: true,
  },
  hasAccess: {
    type: Boolean,
    default: false,
  },
  plans: {
    type: Array,
    default: () => [],
  },
  currentUrl: {
    type: String,
    default: "",
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const formatDateTime = (val) => {
  if (!val) return "—";
  return new Date(val).toLocaleString("en-KE", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const statusBadgeClass = computed(() => {
  const name = props.tender?.status?.name;
  if (name === "Active") return "badge-success";
  if (name === "Closed") return "badge-secondary";
  if (name === "Cancelled") return "badge-danger";
  return "badge-light";
});

const institutionLogoUrl = computed(() => {
  if (!props.tender?.institution?.logo) return null;
  return `/storage/${props.tender.institution.logo}`;
});

const fileDownloadUrl = (filepath) => {
  if (!filepath) return "#";
  return `/storage/${filepath}`;
};

// ── Payment modal state ─────────────────────────────────────────────────
const payModalOpen = ref(false);
const payModalContext = ref({ type: null, planId: null, amount: 0, label: "" });
const payMethod = ref("mpesa"); // future: 'card', 'bank', etc.
const payPhone = ref(user.value?.telephone || "");
const payLoading = ref(false);
const payError = ref("");
const selectedPlanId = ref(null);

// Polling state
const pollCheckoutId = ref(null);
const pollState = ref(null); // null | 'waiting' | 'paid' | 'failed' | 'timeout'
const pollInterval = ref(null);
const pollMessage = ref("");
const pollSeconds = ref(0);
let pollTimer = null;
let pollInitTimeout = null;

const stopPolling = () => {
  if (pollInitTimeout) {
    clearTimeout(pollInitTimeout);
    pollInitTimeout = null;
  }
  if (pollInterval.value) {
    clearInterval(pollInterval.value);
    pollInterval.value = null;
  }
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

const startPolling = (checkoutRequestId) => {
  pollCheckoutId.value = checkoutRequestId;
  pollState.value = "waiting";
  pollSeconds.value = 0;
  let attempts = 0;

  // Tick seconds counter
  pollTimer = setInterval(() => {
    pollSeconds.value++;
  }, 1000);

  // Wait 10s before the first query — Safaricom needs time to process the PIN
  const doPoll = async () => {
    attempts++;
    // Allow up to 60 attempts × 5s = 300s (5 minutes)
    if (attempts > 60) {
      stopPolling();
      pollState.value = "timeout";
      payError.value =
        "Payment confirmation timed out. If you completed the payment, please contact support.";
      return;
    }
    try {
      const { data } = await axios.get(
        route("mpesa.poll", pollCheckoutId.value)
      );
      pollMessage.value = data.message || "";
      if (data.status === "Paid") {
        stopPolling();
        pollState.value = "paid";
        setTimeout(() => window.location.reload(), 1800);
      } else if (data.status === "Failed" || data.status === "Cancelled") {
        stopPolling();
        pollState.value = "failed";
        payError.value =
          data.message || "Payment failed or was cancelled. Please try again.";
      }
    } catch (_) {
      // network hiccup — just retry next tick
    }
  };

  // First poll after 10s, then every 5s
  pollInitTimeout = setTimeout(() => {
    doPoll();
    pollInterval.value = setInterval(doPoll, 5000);
  }, 10000);
};

const openPayModal = (type, planId = null) => {
  payError.value = "";
  pollState.value = null;
  pollCheckoutId.value = null;
  pollMessage.value = "";
  pollSeconds.value = 0;
  stopPolling();
  payMethod.value = "mpesa";
  selectedPlanId.value = planId;
  if (type === "tender") {
    payModalContext.value = {
      type: "tender",
      planId: null,
      amount: props.tender.tender_fee_amount || 0,
      label: `Tender Access Fee – ${props.tender.tender_number || ""}`,
    };
  } else {
    const plan = props.plans.find((p) => p.id === planId);
    payModalContext.value = {
      type: "plan",
      planId,
      amount: plan?.amount || 0,
      label: plan
        ? `${plan.plan_name} (${plan.period} days)`
        : "Subscription Plan",
    };
  }
  payModalOpen.value = true;
};

const closePayModal = () => {
  if (payLoading.value) return;
  if (pollState.value === "waiting") return; // prevent accidental close while polling
  stopPolling();
  pollState.value = null;
  payModalOpen.value = false;
};

const submitPayment = async () => {
  payError.value = "";
  if (payMethod.value === "mpesa" && !payPhone.value) {
    payError.value = "Please enter your M-Pesa phone number.";
    return;
  }
  payLoading.value = true;
  try {
    const ctx = payModalContext.value;
    const { data } = await axios.post(route("mpesa.stk_push"), {
      phone: payPhone.value,
      payment_type: ctx.type,
      plan_id: ctx.type === "plan" ? ctx.planId : null,
      tender_id: ctx.type === "tender" ? props.tender.id : null,
    });
    startPolling(data.checkout_request_id);
  } catch (err) {
    payError.value =
      err?.response?.data?.message ||
      "Failed to initiate payment. Please try again.";
  } finally {
    payLoading.value = false;
  }
};
// ────────────────────────────────────────────────────────────────────────

// Apply UI state (frontend only)
const toast = useToast();
const applyMode = ref(false);
const applicant = ref({
  company_name: "",
  address: "",
  telephone: "",
  website: "",
  county: "",
  email: "",
  additional_notes: "",
});
const representative = ref({
  full_name: "",
  position: "",
  telephone: "",
  email: "",
});
const requirementFiles = ref({});
const requirementsList = computed(() => props.tender.requirements || []);
const countyOptions = computed(() => page.props?.counties || []);

const currentApplyStep = ref(1);

const startApply = () => {
  applyMode.value = true;
  currentApplyStep.value = 1;
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const cancelApply = () => {
  applyMode.value = false;
  currentApplyStep.value = 1;
};

const goToStep = (n) => {
  currentApplyStep.value = n;
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const nextStep = () => {
  if (currentApplyStep.value < 2) currentApplyStep.value++;
};

const prevStep = () => {
  if (currentApplyStep.value > 1) currentApplyStep.value--;
};

const onRequirementFileChange = (event, idx, reqId = null) => {
  const file = event.target.files?.[0] || null;
  if (!file) return;

  // initialize state
  requirementFiles.value[idx] = {
    uploading: true,
    progress: 0,
    name: file.name,
  };

  const xhr = new XMLHttpRequest();
  const url = route("tenders.upload_file", { slug: props.tender.slug });

  xhr.upload.addEventListener("progress", (e) => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      requirementFiles.value[idx].progress = percent;
    }
  });

  xhr.addEventListener("load", () => {
    try {
      const data = JSON.parse(xhr.responseText);
      if (data.success) {
        requirementFiles.value[idx] = {
          uploaded: true,
          filepath: data.filepath,
          name: data.file_name,
        };
      } else {
        requirementFiles.value[idx] = { error: true };
        toast.error(data.message || "Upload failed");
      }
    } catch (err) {
      requirementFiles.value[idx] = { error: true };
      toast.error("Upload error");
    }
  });

  xhr.addEventListener("error", () => {
    requirementFiles.value[idx] = { error: true };
    toast.error("Upload failed");
  });

  const fd = new FormData();
  fd.append("file", file);
  if (reqId) fd.append("requirement_id", reqId);

  // include CSRF token in the form as a fallback (some browsers/blockers strip headers)
  const token =
    document.querySelector("meta[name=csrf-token]")?.getAttribute("content") ||
    "";
  if (token) fd.append("_token", token);

  xhr.open("POST", url);
  // set standard headers
  xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
  if (token) xhr.setRequestHeader("X-CSRF-TOKEN", token);
  xhr.send(fd);
};

const triggerFileInput = (idx) => {
  if (typeof document === "undefined") return;
  const el = document.getElementById("reqFile_" + idx);
  if (el && typeof el.click === "function") el.click();
};

// removed per-requirement email inputs — requirements now show Required/Optional labels

const removeRequirementFile = async (idx) => {
  const rf = requirementFiles.value[idx];
  if (rf && rf.uploaded && rf.filepath) {
    try {
      const res = await fetch(
        route("tenders.delete_temp_file", { slug: props.tender.slug }),
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN":
              document
                .querySelector("meta[name=csrf-token]")
                ?.getAttribute("content") || "",
          },
          body: JSON.stringify({ filepath: rf.filepath }),
        }
      );
      const data = await res.json();
      if (!data.success) {
        toast.error("Could not remove file");
      }
    } catch (e) {
      // ignore
    }
  }

  delete requirementFiles.value[idx];
};

const submitApplication = async () => {
  // Basic required fields (business)
  const missing = [];
  if (!applicant.value.company_name?.trim())
    missing.push("Company/Organization name");
  if (!applicant.value.telephone?.trim()) missing.push("Telephone");
  if (!applicant.value.email?.trim()) missing.push("Email");
  if (!applicant.value.address?.trim()) missing.push("Address");
  if (!applicant.value.county) missing.push("County");

  // Representative required
  if (!representative.value.full_name?.trim())
    missing.push("Representative name");
  if (!representative.value.telephone?.trim())
    missing.push("Representative telephone");
  if (!representative.value.email?.trim()) missing.push("Representative email");

  if (missing.length) {
    toast.error("Please fill required fields: " + missing.join(", "));
    // ensure user sees the basic details
    goToStep(1);
    return;
  }

  // Ensure all mandatory requirement files uploaded
  const missingFiles = [];
  requirementsList.value.forEach((req, idx) => {
    if (req.mandatory) {
      const rf = requirementFiles.value[idx];
      if (!(rf && rf.uploaded)) {
        missingFiles.push(req.title || `Requirement ${idx + 1}`);
      }
    }
  });

  if (missingFiles.length) {
    toast.error("Please upload required files: " + missingFiles.join(", "));
    goToStep(2);
    return;
  }

  const form = new FormData();
  form.append("company_name", applicant.value.company_name || "");
  form.append("telephone", applicant.value.telephone || "");
  form.append("website", applicant.value.website || "");
  form.append("county_id", applicant.value.county || "");
  form.append("address", applicant.value.address || "");
  form.append("email", applicant.value.email || "");
  form.append("representative_name", representative.value.full_name || "");
  form.append("representative_position", representative.value.position || "");
  form.append("representative_telephone", representative.value.telephone || "");
  form.append("representative_email", representative.value.email || "");
  form.append("additional_notes", applicant.value.additional_notes || "");

  requirementsList.value.forEach((req, idx) => {
    form.append(`requirement_titles[${idx}]`, req.title || "");
    const rf = requirementFiles.value[idx];
    form.append(
      `requirement_file_paths[${idx}]`,
      rf && rf.uploaded ? rf.filepath : ""
    );
    form.append(`requirement_file_requirement_ids[${idx}]`, req.id || "");
    form.append(
      `requirement_file_original_names[${idx}]`,
      rf && rf.uploaded ? rf.name : ""
    );
  });

  try {
    // append CSRF token as fallback
    const token =
      document
        .querySelector("meta[name=csrf-token]")
        ?.getAttribute("content") || "";
    if (token) form.append("_token", token);

    const res = await fetch(
      route("tenders.apply", { slug: props.tender.slug }),
      {
        method: "POST",
        headers: token ? { "X-CSRF-TOKEN": token } : {},
        body: form,
      }
    );

    if (!res.ok) {
      let errText = `${res.status} ${res.statusText}`;
      try {
        const j = await res.json();
        errText = j.message || JSON.stringify(j);
      } catch (err) {
        try {
          errText = await res.text();
        } catch (_) {}
      }
      toast.error(`Submission failed: ${errText}`);
      return;
    }

    const data = await res.json();
    if (data.success) {
      toast.success("Application submitted");
      applyMode.value = false;
    } else {
      toast.error(data.message || "Submission failed");
    }
  } catch (e) {
    toast.error("Submission error: " + (e.message || e));
  }
};
</script>

<template>
  <Head :title="`${tender.title} - Tender Details`" />

  <div class="public-tender-page bg-light min-vh-100">
    <PublicNavbar
      :canLogin="true"
      :canRegister="true"
      active-page="browse"
      :redirect-url="props.currentUrl"
    />

    <div class="container py-4">
      <!-- Hero — always visible -->
      <div class="card tender-hero mb-4">
        <div class="card-body px-4 pt-4 pb-3">
          <div
            class="d-flex flex-wrap align-items-center mb-3"
            style="gap: 0.4rem"
          >
            <span
              class="badge badge-pill hero-status-badge"
              :class="statusBadgeClass"
            >
              {{ tender.status?.name || "Unknown" }}
            </span>
            <span class="badge badge-pill hero-industry-badge">
              {{ tender.industry?.name || "Industry" }}
            </span>
          </div>

          <h1 class="hero-title mb-3">{{ tender.title }}</h1>

          <div class="hero-meta-row">
            <div class="hero-meta-item">
              <span class="hero-meta-label">Tender No.</span>
              <span class="hero-meta-value">{{ tender.tender_no || "—" }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label">County</span>
              <span class="hero-meta-value">{{
                tender.county?.name || "—"
              }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label"
                ><i class="far fa-calendar-alt mr-1"></i>Closing Date</span
              >
              <span class="hero-meta-value text-danger">{{
                formatDateTime(tender.closing_date_and_time)
              }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label"
                ><i class="far fa-calendar-check mr-1"></i>Expiry Date</span
              >
              <span class="hero-meta-value">{{
                formatDateTime(tender.expiry_date)
              }}</span>
            </div>
          </div>

          <div v-if="hasAccess" class="d-flex justify-content-end mt-3">
            <button
              v-if="!applyMode && tender.tender_link_process"
              class="btn btn-success"
              @click="startApply"
            >
              <i class="fas fa-paper-plane mr-1"></i> Apply for this Tender
            </button>
          </div>
        </div>
      </div>

      <!-- ── LOCKED STATE ───────────────────────────────────────────────── -->
      <template v-if="!hasAccess">
        <div class="row align-items-start">
          <!-- Institution card (always visible on lock screen) -->
          <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm mb-0">
              <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                  <img
                    v-if="institutionLogoUrl"
                    :src="institutionLogoUrl"
                    :alt="tender.institution?.institution_name"
                    class="institution-logo mr-3"
                  />
                  <div v-else class="institution-logo-placeholder mr-3">
                    {{
                      tender.institution?.institution_name
                        ?.charAt(0)
                        ?.toUpperCase() || "I"
                    }}
                  </div>
                  <div>
                    <h6 class="font-weight-bold mb-1">
                      {{ tender.institution?.institution_name || "—" }}
                    </h6>
                    <span class="badge badge-success-light">{{
                      tender.institution?.institution_type?.name || "—"
                    }}</span>
                    <div
                      v-if="tender.institution?.address"
                      class="small text-muted mt-1"
                    >
                      <i class="fas fa-map-marker-alt text-success mr-1"></i>
                      {{ tender.institution.address }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tender details summary (locked preview) -->
            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white border-0 pb-1">
                <h6 class="font-weight-bold mb-0">Tender Details</h6>
              </div>
              <div class="card-body pt-2">
                <table class="table table-sm table-borderless mb-0">
                  <tbody>
                    <tr>
                      <td class="text-muted" style="width:140px"><i class="fas fa-hashtag mr-1 text-success"></i> Tender No.</td>
                      <td class="font-weight-bold">{{ tender.tender_no || '—' }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted"><i class="fas fa-industry mr-1 text-success"></i> Industry</td>
                      <td>{{ tender.industry?.name || '—' }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted"><i class="fas fa-map-marker-alt mr-1 text-success"></i> County</td>
                      <td>{{ tender.county?.name || '—' }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted"><i class="far fa-calendar-alt mr-1 text-success"></i> Closing</td>
                      <td class="text-danger font-weight-bold">{{ formatDateTime(tender.closing_date_and_time) }}</td>
                    </tr>
                    <tr>
                      <td class="text-muted"><i class="far fa-calendar-check mr-1 text-success"></i> Expiry</td>
                      <td>{{ formatDateTime(tender.expiry_date) }}</td>
                    </tr>
                    <tr v-if="tender.files?.length">
                      <td class="text-muted"><i class="fas fa-paperclip mr-1 text-success"></i> Documents</td>
                      <td>{{ tender.files.length }} file{{ tender.files.length !== 1 ? 's' : '' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Payment wall -->
          <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm" style="position: sticky; top: 1rem;">
              <div class="card-body text-center py-5">
                <!-- Not logged in: show lock + login prompt -->
                <template v-if="!user">
                  <div class="mb-3">
                    <span
                      class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
                      style="width: 72px; height: 72px"
                    >
                      <i class="fas fa-lock fa-2x text-secondary"></i>
                    </span>
                  </div>
                  <h5 class="font-weight-bold mb-2">
                    Login to view Full Tender Details
                  </h5>
                  <p class="text-muted mb-4">
                    Please log in or create an account to access full tender
                    details.
                  </p>
                  <div
                    class="d-flex justify-content-center flex-column align-items-center"
                    style="gap: 0.75rem; max-width: 260px; margin: 0 auto"
                  >
                    <a
                      :href="
                        route('auth.google') +
                        (props.currentUrl
                          ? '?redirect=' + encodeURIComponent(props.currentUrl)
                          : '')
                      "
                      class="btn btn-block px-4"
                      style="
                        border: 1px solid #d8d8d8;
                        background: #fff;
                        color: #444;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.4rem;
                      "
                    >
                      <img
                        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                        width="16"
                        height="16"
                        alt=""
                      />
                      Continue with Google
                    </a>
                    <Link
                      :href="
                        route('login') +
                        (props.currentUrl
                          ? '?redirect=' + encodeURIComponent(props.currentUrl)
                          : '')
                      "
                      class="btn btn-success btn-block px-4"
                    >
                      <i class="fas fa-sign-in-alt mr-1"></i> Login with Email
                    </Link>
                  </div>
                </template>

                <!-- Logged in — tender-specific fee required -->
                <template v-else-if="tender.tender_link_process">
                  <div class="mb-3">
                    <span
                      class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="
                        width: 72px;
                        height: 72px;
                        background: rgba(9, 23, 111, 0.1);
                      "
                    >
                      <i
                        class="fas fa-file-invoice-dollar fa-2x text-success"
                      ></i>
                    </span>
                  </div>
                  <h5 class="font-weight-bold text-uppercase mb-2">
                    Payment Required
                  </h5>
                  <p class="text-muted mb-1">
                    To access the full details of this tender, a one-time tender
                    fee payment is required.
                  </p>
                  <div class="my-4">
                    <span class="d-block text-muted small mb-1"
                      >Tender Access Fee</span
                    >
                    <span class="h2 font-weight-bold text-success">
                      KES
                      {{
                        Number(tender.tender_fee_amount || 0).toLocaleString(
                          "en-KE",
                          { minimumFractionDigits: 2 }
                        )
                      }}
                    </span>
                  </div>
                  <button
                    class="btn btn-success btn-lg px-5"
                    @click="openPayModal('tender')"
                  >
                    <i class="fas fa-credit-card mr-2"></i>Pay &amp; Unlock
                    Tender
                  </button>
                  <p class="text-muted small mt-3">
                    <i class="fas fa-shield-alt mr-1 text-success"></i>
                    One-time payment · Instant access · Secure checkout
                  </p>
                </template>

                <!-- Logged in — subscription plan required -->
                <template v-else>
                  <h5 class="font-weight-bold text-uppercase mb-3">
                    Subscription Required
                  </h5>
                  <p class="text-muted mb-4">
                    You need an active subscription plan to view tender details.
                    Choose a plan below.
                  </p>

                  <div class="plans-row">
                    <div
                      v-for="(plan, idx) in plans"
                      :key="plan.id"
                      class="plan-card"
                      :class="idx === 2 ? 'plan-card--featured' : ''"
                    >
                      <div v-if="idx === 2" class="plan-popular-badge">
                        Most Popular
                      </div>
                      <div class="plan-icon mb-2">
                        <i
                          class="fas fa-crown"
                          v-if="idx === plans.length - 1"
                        ></i>
                        <i class="fas fa-gem" v-else-if="idx === 2"></i>
                        <i class="fas fa-tag" v-else></i>
                      </div>
                      <div class="plan-name">{{ plan.plan_name }}</div>
                      <div class="plan-duration">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        {{ plan.period }} day{{ plan.period !== 1 ? "s" : "" }}
                        access
                      </div>
                      <div class="plan-price">
                        <span class="plan-currency">KES</span>
                        {{
                          Number(plan.amount).toLocaleString("en-KE", {
                            minimumFractionDigits: 2,
                          })
                        }}
                      </div>
                      <p v-if="plan.description" class="plan-desc">
                        {{ plan.description }}
                      </p>
                      <button
                        class="plan-btn"
                        :class="idx === 2 ? 'plan-btn--featured' : ''"
                        @click="openPayModal('plan', plan.id)"
                      >
                        <i class="fas fa-check-circle mr-1"></i> Choose Plan
                      </button>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ── FULL CONTENT (has access) ─────────────────────────────────── -->
      <template v-else>
        <div class="row">
          <template v-if="!applyMode">
            <div class="col-lg-8 mb-4 mb-lg-0">
              <!-- Description -->
              <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Tender Description</h5>
                </div>
                <div class="card-body">
                  <div class="content-html" v-html="tender.description"></div>
                </div>
              </div>

              <!-- Requirements -->
              <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Key Requirements</h5>
                </div>
                <div class="card-body">
                  <div
                    v-if="tender.key_requirements"
                    class="content-html"
                    v-html="tender.key_requirements"
                  ></div>
                  <p v-else class="text-muted mb-0">
                    No key requirements specified.
                  </p>
                </div>
              </div>

              <!-- Files -->
              <div class="card border-0 shadow-sm">
                <div
                  class="card-header bg-white border-0 pb-1 d-flex justify-content-between align-items-center"
                >
                  <h5 class="font-weight-bold mb-0">Tender Documents</h5>
                  <span class="badge badge-light"
                    >{{ tender.files?.length || 0 }} files</span
                  >
                </div>
                <div class="card-body">
                  <div
                    v-if="!tender.files || tender.files.length === 0"
                    class="alert alert-light border mb-0"
                  >
                    No downloadable documents available.
                  </div>
                  <div v-else class="list-group list-group-flush">
                    <a
                      v-for="file in tender.files"
                      :key="file.id"
                      :href="fileDownloadUrl(file.filepath)"
                      :download="file.file_name"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <div class="d-flex align-items-center">
                        <i class="fas fa-file-alt text-success mr-2"></i>
                        <span>{{ file.file_name }}</span>
                      </div>
                      <span class="btn btn-sm btn-outline-success">
                        <i class="fas fa-download mr-1"></i> Download
                      </span>
                    </a>
                  </div>
                </div>
              </div>
              <div class="mt-3 d-flex justify-content-end">
                <button
                  v-if="!applyMode && tender.tender_link_process"
                  class="btn btn-success"
                  @click="startApply"
                >
                  <i class="fas fa-paper-plane mr-1"></i> Apply for this Tender
                </button>
              </div>
            </div>

            <div class="col-lg-4">
              <!-- Institution -->
              <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Tender Institution</h5>
                </div>
                <div class="card-body">
                  <div class="d-flex align-items-start mb-3">
                    <img
                      v-if="institutionLogoUrl"
                      :src="institutionLogoUrl"
                      :alt="tender.institution?.institution_name"
                      class="institution-logo mr-3"
                    />
                    <div v-else class="institution-logo-placeholder mr-3">
                      {{
                        tender.institution?.institution_name
                          ?.charAt(0)
                          ?.toUpperCase() || "I"
                      }}
                    </div>
                    <div>
                      <h6 class="font-weight-bold mb-1">
                        {{ tender.institution?.institution_name || "—" }}
                      </h6>
                      <span class="badge badge-success-light">{{
                        tender.institution?.institutionType?.name || "—"
                      }}</span>
                    </div>
                  </div>
                  <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2" v-if="tender.institution?.email">
                      <i class="fas fa-envelope text-success mr-2"></i
                      >{{ tender.institution.email }}
                    </li>
                    <li class="mb-2" v-if="tender.institution?.telephone">
                      <i class="fas fa-phone text-success mr-2"></i
                      >{{ tender.institution.telephone }}
                    </li>
                    <li class="mb-2" v-if="tender.institution?.website">
                      <i class="fas fa-globe text-success mr-2"></i>
                      <a
                        :href="tender.institution.website"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-success"
                      >
                        {{ tender.institution.website }}
                      </a>
                    </li>
                    <li v-if="tender.institution?.address">
                      <i class="fas fa-map-marker-alt text-success mr-2"></i
                      >{{ tender.institution.address }}
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Quick summary -->
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Quick Summary</h5>
                </div>
                <div class="card-body small">
                  <div
                    class="d-flex justify-content-between border-bottom py-2"
                  >
                    <span class="text-muted">Tender No</span>
                    <span class="font-weight-semibold">{{
                      tender.tender_no
                    }}</span>
                  </div>
                  <div
                    class="d-flex justify-content-between border-bottom py-2"
                  >
                    <span class="text-muted">Status</span>
                    <span class="font-weight-semibold">{{
                      tender.status?.name || "—"
                    }}</span>
                  </div>
                  <div
                    class="d-flex justify-content-between border-bottom py-2"
                  >
                    <span class="text-muted">Industry</span>
                    <span class="font-weight-semibold">{{
                      tender.industry?.name || "—"
                    }}</span>
                  </div>
                  <div
                    class="d-flex justify-content-between border-bottom py-2"
                  >
                    <span class="text-muted">County</span>
                    <span class="font-weight-semibold">{{
                      tender.county?.name || "—"
                    }}</span>
                  </div>
                  <div class="d-flex justify-content-between pt-2">
                    <span class="text-muted">Documents</span>
                    <span class="font-weight-semibold">{{
                      tender.files?.length || 0
                    }}</span>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="col-12">
              <div class="card border-0 shadow-sm mb-4">
                <div
                  class="card-header bg-white border-0 pb-1 d-flex align-items-center justify-content-between"
                >
                  <div
                    class="d-flex align-items-center flex-grow-1"
                    style="gap: 0.5rem"
                  >
                    <button
                      :class="[
                        'btn',
                        currentApplyStep === 1
                          ? 'btn-success'
                          : 'btn-outline-secondary',
                        'btn-sm',
                      ]"
                      @click.prevent="goToStep(1)"
                    >
                      1. Basic Details
                    </button>
                    <button
                      :class="[
                        'btn',
                        currentApplyStep === 2
                          ? 'btn-success'
                          : 'btn-outline-secondary',
                        'btn-sm',
                      ]"
                      @click.prevent="goToStep(2)"
                    >
                      2. Requirements
                    </button>
                  </div>
                  <button
                    class="btn btn-outline-success btn-sm ms-3"
                    @click="cancelApply"
                  >
                    Back to Details
                  </button>
                </div>
                <div class="card-body">
                  <form @submit.prevent="submitApplication">
                    <div class="row">
                      <!-- Step 1: Basic Details -->
                      <template v-if="currentApplyStep === 1">
                        <div class="col-12 mb-2">
                          <label class="font-weight-semibold"
                            >Company / Organization Name
                            <span class="text-danger">*</span></label
                          >
                          <input
                            v-model="applicant.company_name"
                            type="text"
                            class="form-control form-control-sm"
                          />
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="font-weight-semibold"
                            >Telephone <span class="text-danger">*</span></label
                          >
                          <input
                            v-model="applicant.telephone"
                            type="text"
                            class="form-control form-control-sm"
                          />
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="font-weight-semibold">Website</label>
                          <input
                            v-model="applicant.website"
                            type="url"
                            class="form-control form-control-sm"
                          />
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="font-weight-semibold"
                            >Email <span class="text-danger">*</span></label
                          >
                          <input
                            v-model="applicant.email"
                            type="email"
                            class="form-control form-control-sm"
                          />
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="font-weight-semibold"
                            >County <span class="text-danger">*</span></label
                          >
                          <select
                            v-model="applicant.county"
                            class="form-control form-control-sm"
                          >
                            <option value="">Select county</option>
                            <option
                              v-for="c in countyOptions"
                              :key="c.id || c"
                              :value="c.id || c"
                            >
                              {{ c.name || c }}
                            </option>
                          </select>
                        </div>
                        <div class="col-12 mb-2">
                          <label class="font-weight-semibold">Address</label>
                          <textarea
                            v-model="applicant.address"
                            class="form-control form-control-sm"
                            rows="2"
                          ></textarea>
                        </div>

                        <div class="col-12 mb-2">
                          <h6 class="mb-2">Representative Details</h6>
                          <div class="row">
                            <div class="col-md-6 mb-2">
                              <label class="font-weight-semibold"
                                >Full Names
                                <span class="text-danger">*</span></label
                              >
                              <input
                                v-model="representative.full_name"
                                type="text"
                                class="form-control form-control-sm"
                              />
                            </div>
                            <div class="col-md-6 mb-2">
                              <label class="font-weight-semibold"
                                >Position</label
                              >
                              <input
                                v-model="representative.position"
                                type="text"
                                class="form-control form-control-sm"
                              />
                            </div>
                            <div class="col-md-6 mb-2">
                              <label class="font-weight-semibold"
                                >Telephone
                                <span class="text-danger">*</span></label
                              >
                              <input
                                v-model="representative.telephone"
                                type="text"
                                class="form-control form-control-sm"
                              />
                            </div>
                            <div class="col-md-6 mb-2">
                              <label class="font-weight-semibold"
                                >Email <span class="text-danger">*</span></label
                              >
                              <input
                                v-model="representative.email"
                                type="email"
                                class="form-control form-control-sm"
                              />
                            </div>
                          </div>
                        </div>

                        <div
                          class="col-12 d-flex justify-content-end mt-2"
                          style="gap: 0.75rem"
                        >
                          <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="cancelApply"
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            class="btn btn-success btn-sm"
                            @click="nextStep"
                          >
                            Next: Requirements
                          </button>
                        </div>
                      </template>

                      <!-- Step 2: Requirements -->
                      <template v-if="currentApplyStep === 2">
                        <div class="col-12 mb-2">
                          <h6 class="mb-2">Tender Requirements</h6>
                          <div
                            v-if="!requirementsList.length"
                            class="text-muted mb-3"
                          >
                            No specific requirements listed.
                          </div>
                          <div
                            v-for="(req, idx) in requirementsList"
                            :key="req.id || idx"
                            class="border rounded p-2 mb-2"
                          >
                            <div
                              class="d-flex justify-content-between align-items-start mb-1"
                            >
                              <div class="me-3" style="flex: 1">
                                <div class="font-weight-semibold">
                                  {{ req.title || "Requirement " + (idx + 1) }}
                                </div>
                                <small class="text-muted d-block">{{
                                  req.notes || ""
                                }}</small>
                                <div class="mt-2">
                                  <small
                                    :class="
                                      req.mandatory
                                        ? 'text-danger'
                                        : 'text-muted'
                                    "
                                    >{{
                                      req.mandatory ? "Required" : "Optional"
                                    }}</small
                                  >
                                </div>
                              </div>
                              <div class="text-end ms-2">
                                <input
                                  type="file"
                                  :id="'reqFile_' + idx"
                                  class="d-none"
                                  @change="
                                    (e) =>
                                      onRequirementFileChange(e, idx, req.id)
                                  "
                                />
                                <button
                                  type="button"
                                  class="btn btn-sm choose-file-btn"
                                  @click.prevent="triggerFileInput(idx)"
                                >
                                  <template
                                    v-if="
                                      requirementFiles[idx] &&
                                      requirementFiles[idx].uploading
                                    "
                                  >
                                    <i class="fas fa-spinner fa-spin me-1"></i>
                                    Uploading
                                    <small class="ms-2"
                                      >{{
                                        requirementFiles[idx].progress || 0
                                      }}%</small
                                    >
                                  </template>
                                  <template
                                    v-else-if="
                                      requirementFiles[idx] &&
                                      requirementFiles[idx].uploaded
                                    "
                                  >
                                    <i
                                      class="fas fa-check-circle me-1 text-success"
                                    ></i>
                                    Uploaded
                                  </template>
                                  <template v-else>
                                    <i class="fas fa-upload me-1"></i>
                                    Choose file
                                  </template>
                                </button>
                                <div
                                  v-if="requirementFiles[idx]"
                                  class="mt-1 small text-muted d-flex align-items-center"
                                >
                                  <span class="me-2">{{
                                    requirementFiles[idx].name
                                  }}</span>
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger p-0"
                                    style="line-height: 1; padding: 0 6px"
                                    @click="removeRequirementFile(idx)"
                                  >
                                    &times;
                                  </button>
                                </div>
                                <div
                                  v-if="
                                    requirementFiles[idx] &&
                                    requirementFiles[idx].uploading
                                  "
                                  class="mt-1"
                                >
                                  <div class="progress" style="height: 6px">
                                    <div
                                      class="progress-bar"
                                      role="progressbar"
                                      :style="{
                                        width:
                                          (requirementFiles[idx].progress ||
                                            0) + '%',
                                      }"
                                      :aria-valuenow="
                                        requirementFiles[idx].progress || 0
                                      "
                                      aria-valuemin="0"
                                      aria-valuemax="100"
                                    ></div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-12 mb-2">
                          <label class="font-weight-semibold"
                            >Additional Notes</label
                          >
                          <textarea
                            v-model="applicant.additional_notes"
                            class="form-control form-control-sm"
                            rows="3"
                          ></textarea>
                        </div>

                        <div class="col-12 d-flex justify-content-between mt-2">
                          <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="prevStep"
                          >
                            Back
                          </button>
                          <div class="d-flex" style="gap: 0.75rem">
                            <button
                              type="button"
                              class="btn btn-outline-secondary btn-sm"
                              @click="cancelApply"
                            >
                              Cancel
                            </button>
                            <button
                              type="submit"
                              class="btn btn-success btn-sm"
                            >
                              Submit Application
                            </button>
                          </div>
                        </div>
                      </template>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </template>
        </div>
      </template>
      <!-- ── END FULL CONTENT ──────────────────────────────────────────── -->
    </div>
  </div>

  <!-- ── PAYMENT MODAL ────────────────────────────────────────────────── -->
  <teleport to="body">
    <transition name="pay-modal">
      <div
        v-if="payModalOpen"
        class="pay-modal-backdrop"
        @click.self="closePayModal"
      >
        <div class="pay-modal-card" role="dialog" aria-modal="true">
          <!-- Header -->
          <div class="pay-modal-header">
            <div class="pay-modal-title">
              <i class="fas fa-lock mr-2 text-success"></i>
              Complete Payment
            </div>
            <button
              class="pay-modal-close"
              :disabled="payLoading"
              @click="closePayModal"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Order summary -->
          <div class="pay-modal-summary">
            <div class="pay-summary-label">{{ payModalContext.label }}</div>
            <div class="pay-summary-amount">
              <span class="pay-summary-currency">KES</span>
              {{
                Number(payModalContext.amount).toLocaleString("en-KE", {
                  minimumFractionDigits: 2,
                })
              }}
            </div>
          </div>

          <!-- Waiting / polling state -->
          <div v-if="pollState === 'waiting'" class="pay-modal-polling">
            <div class="pay-polling-spinner">
              <div class="pay-polling-ring"></div>
              <div class="pay-polling-inner">
                <img
                  src="/images/mpesa-logo.png"
                  alt="M-Pesa"
                  class="pay-polling-logo"
                  onerror="this.style.display='none'"
                />
              </div>
            </div>
            <h6 class="font-weight-bold mt-4 mb-1">Waiting for Payment</h6>
            <p class="text-muted small mb-1">
              A prompt was sent to <strong>{{ payPhone }}</strong
              >.<br />
              Enter your M-Pesa PIN to confirm.
            </p>
            <p class="pay-poll-timer small text-secondary mb-3">
              <i class="fas fa-clock mr-1"></i>{{ pollSeconds }}s elapsed
            </p>
            <div class="pay-poll-dots">
              <span></span><span></span><span></span>
            </div>
            <p
              class="text-muted"
              style="font-size: 0.75rem; margin-top: 0.75rem"
            >
              Do NOT close this window. Page will refresh automatically on
              success.
            </p>
          </div>

          <!-- Success / paid state -->
          <div v-else-if="pollState === 'paid'" class="pay-modal-success">
            <div class="pay-success-icon">
              <i class="fas fa-check-circle text-success fa-3x"></i>
            </div>
            <h6 class="font-weight-bold mt-3 mb-2">Payment Confirmed!</h6>
            <p class="text-muted small mb-3">
              Your payment was successful. Refreshing page…
            </p>
            <div
              class="spinner-border spinner-border-sm text-success"
              role="status"
            ></div>
          </div>

          <!-- Failed / timeout state -->
          <div
            v-else-if="pollState === 'failed' || pollState === 'timeout'"
            class="pay-modal-failed"
          >
            <div class="pay-failed-icon">
              <i class="fas fa-times-circle text-danger fa-3x"></i>
            </div>
            <h6 class="font-weight-bold mt-3 mb-2">
              {{
                pollState === "timeout" ? "Request Timed Out" : "Payment Failed"
              }}
            </h6>
            <p class="text-muted small mb-3">{{ payError }}</p>
            <button
              class="btn btn-outline-primary btn-sm"
              @click="
                () => {
                  pollState = null;
                  payError = '';
                }
              "
            >
              <i class="fas fa-redo mr-1"></i> Try Again
            </button>
            <button
              class="btn btn-link btn-sm text-muted"
              @click="closePayModal"
            >
              Cancel
            </button>
          </div>

          <template v-else>
            <!-- Payment method selector -->
            <div class="pay-method-section">
              <p class="pay-section-label">Select Payment Method</p>
              <div class="pay-method-options">
                <!-- M-Pesa (default & active) -->
                <button
                  class="pay-method-btn"
                  :class="{ 'pay-method-btn--active': payMethod === 'mpesa' }"
                  @click="payMethod = 'mpesa'"
                >
                  <img
                    src="/images/mpesa-logo.png"
                    alt="M-Pesa"
                    class="pay-method-logo"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'"
                  />
                  <span style="display: none" class="pay-method-fallback">
                    <i class="fas fa-mobile-alt mr-1"></i> M-Pesa
                  </span>
                  <span class="pay-method-name">M-Pesa</span>
                  <span v-if="payMethod === 'mpesa'" class="pay-method-check">
                    <i class="fas fa-check-circle text-success"></i>
                  </span>
                </button>

                <!-- Placeholder for future methods -->
                <button
                  class="pay-method-btn pay-method-btn--disabled"
                  disabled
                  title="Coming soon"
                >
                  <i class="fas fa-credit-card fa-lg text-muted"></i>
                  <span class="pay-method-name text-muted">Card</span>
                  <span class="pay-method-soon">Soon</span>
                </button>
                <button
                  class="pay-method-btn pay-method-btn--disabled"
                  disabled
                  title="Coming soon"
                >
                  <i class="fas fa-university fa-lg text-muted"></i>
                  <span class="pay-method-name text-muted">Bank</span>
                  <span class="pay-method-soon">Soon</span>
                </button>
              </div>
            </div>

            <!-- M-Pesa form -->
            <div v-if="payMethod === 'mpesa'" class="pay-form-section">
              <p class="pay-section-label">M-Pesa Details</p>
              <div class="form-group mb-1">
                <label class="small font-weight-bold mb-1">Phone Number</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">
                      <i class="fas fa-mobile-alt text-success"></i>
                    </span>
                  </div>
                  <input
                    v-model="payPhone"
                    type="tel"
                    class="form-control"
                    placeholder="e.g. 0712 345 678"
                    :disabled="payLoading"
                    @keyup.enter="submitPayment"
                  />
                </div>
                <small class="text-muted"
                  >You will receive an STK push on this number.</small
                >
              </div>
              <div v-if="payError" class="alert alert-danger py-2 mt-2 small">
                <i class="fas fa-exclamation-circle mr-1"></i>{{ payError }}
              </div>
            </div>

            <!-- Footer actions -->
            <div class="pay-modal-footer">
              <button
                class="btn btn-light px-4"
                :disabled="payLoading"
                @click="closePayModal"
              >
                Cancel
              </button>
              <button
                class="btn btn-success px-4"
                :disabled="payLoading"
                @click="submitPayment"
              >
                <span v-if="payLoading">
                  <i class="fas fa-spinner fa-spin mr-2"></i>Sending…
                </span>
                <span v-else>
                  <i class="fas fa-paper-plane mr-2"></i>Send Payment Request
                </span>
              </button>
            </div>

            <!-- Trust badges -->
            <div class="pay-modal-trust">
              <i class="fas fa-shield-alt text-success mr-1"></i>
              Secure &amp; Encrypted
              <span class="mx-2">·</span>
              <i class="fas fa-undo text-success mr-1"></i>
              Verified by Safaricom
            </div>
          </template>
        </div>
      </div>
    </transition>
  </teleport>
  <!-- ── END PAYMENT MODAL ─────────────────────────────────────────────── -->
</template>

<style scoped>
.plans-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.25rem;
}
.plan-card {
  position: relative;
  background: #fff;
  border: 1.5px solid #e4ebfb;
  border-radius: 1rem;
  padding: 1.75rem 1.5rem 1.5rem;
  flex: 1 1 160px;
  max-width: 200px;
  min-width: 140px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: box-shadow 0.18s, transform 0.18s, border-color 0.18s;
  cursor: pointer;
}
.plan-card:hover {
  box-shadow: 0 6px 24px rgba(9, 23, 111, 0.14);
  transform: translateY(-3px);
  border-color: var(--brand-primary);
}
.plan-card--featured {
  border-color: var(--brand-primary);
  background: var(--brand-surface-blue);
  box-shadow: 0 4px 18px rgba(9, 23, 111, 0.13);
}
.plan-popular-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--brand-primary);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 2px 12px;
  border-radius: 20px;
  white-space: nowrap;
}
.plan-icon {
  font-size: 1.5rem;
  color: var(--brand-primary);
}
.plan-card--featured .plan-icon {
  color: #155724;
}
.plan-name {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.3rem;
}
.plan-duration {
  font-size: 0.78rem;
  color: #6c757d;
  margin-bottom: 0.75rem;
}
.plan-price {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--brand-primary);
  margin-bottom: 0.5rem;
  line-height: 1.2;
}
.plan-currency {
  font-size: 0.75rem;
  font-weight: 600;
  vertical-align: super;
  margin-right: 2px;
  color: #555;
}
.plan-desc {
  font-size: 0.73rem;
  color: #888;
  margin-bottom: 0.75rem;
  flex-grow: 1;
}
.plan-btn {
  width: 100%;
  padding: 0.45rem 0;
  border: 1.5px solid var(--brand-primary);
  border-radius: 50px;
  background: transparent;
  color: var(--brand-primary);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  margin-top: auto;
}
.plan-btn:hover,
.plan-btn--featured {
  background: var(--brand-primary);
  color: #fff;
}
.plan-btn--featured:hover {
  background: #1e7e34;
  border-color: #1e7e34;
}
.brand-logo-full {
  height: 42px;
  width: auto;
  display: block;
}
.mobile-nav-toggler {
  display: none;
  border-color: rgba(9, 23, 111, 0.4);
  color: var(--brand-secondary-dark);
  align-items: center;
  justify-content: center;
}

.mobile-nav-toggler i {
  font-size: 1.2rem;
}

.nav-mobile-collapse {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: 1 1 auto;
}

.auth-actions {
  margin-left: auto;
}

.nav-main-menu {
  gap: 0.15rem;
}

.nav-main-menu .nav-link {
  color: #2f3e46;
  font-weight: 600;
  padding: 0.45rem 0.75rem;
  border-radius: 0.35rem;
}

.nav-main-menu .nav-link:hover {
  color: var(--brand-secondary-dark);
  background-color: rgba(9, 23, 111, 0.08);
}

.nav-item-dropdown {
  position: relative;
}

.dropdown-menu-custom {
  position: absolute;
  top: calc(100% + 0.35rem);
  left: 0;
  z-index: 1100;
  min-width: 190px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0.5rem;
  box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.12);
  display: none;
  padding: 0.35rem 0;
}

.nav-item-dropdown:hover .dropdown-menu-custom {
  display: block;
}

.nav-item-dropdown.is-open .dropdown-menu-custom {
  display: block;
}

.dropdown-item {
  color: #2f3e46;
}

.dropdown-item:hover {
  background-color: rgba(9, 23, 111, 0.08);
  color: var(--brand-secondary-dark);
}

@media (max-width: 767.98px) {
  .mobile-nav-toggler {
    display: inline-flex;
  }

  .nav-mobile-collapse {
    display: none;
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    padding-bottom: 0.5rem;
  }

  .nav-mobile-collapse.is-open {
    display: flex;
  }

  .nav-main-menu {
    width: 100%;
    flex-direction: column !important;
    justify-content: flex-start !important;
    margin-top: 0.35rem;
    margin-bottom: 0.2rem;
  }

  .nav-main-menu .nav-item {
    width: 100%;
  }

  .nav-main-menu .nav-link {
    padding: 0.6rem 0.75rem;
  }

  .dropdown-menu-custom {
    position: static;
    min-width: 100%;
    border-radius: 0.35rem;
    box-shadow: none;
    margin: 0.2rem 0 0.35rem;
  }

  .nav-item-dropdown:hover .dropdown-menu-custom {
    display: none;
  }

  .nav-item-dropdown.is-open .dropdown-menu-custom {
    display: block;
  }

  .auth-actions {
    width: 100%;
    justify-content: flex-start !important;
    margin-left: 0;
    padding-top: 0.25rem !important;
  }

  .navbar .btn {
    min-width: 90px;
    margin-left: 0 !important;
    margin-right: 0.5rem;
  }

  .brand-logo-full {
    height: 36px;
  }
}
.tender-hero {
  background: #ffffff;
  border: 1px solid var(--brand-primary-border) !important;
  border-left: 5px solid var(--brand-primary) !important;
  border-radius: 0.5rem;
  box-shadow: 0 2px 12px rgba(9, 23, 111, 0.08) !important;
}

.hero-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a3326;
  line-height: 1.4;
  letter-spacing: -0.01em;
  margin-bottom: 0;
}

@media (max-width: 576px) {
  .hero-title {
    font-size: 1rem;
  }
}

.hero-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 0;
}

.hero-meta-item {
  display: flex;
  flex-direction: column;
  margin-right: 2.5rem;
  margin-bottom: 0.25rem;
}

.hero-meta-sep {
  width: 1px;
  height: 36px;
  background: #fff4d6;
  margin-right: 2.5rem;
  align-self: center;
}

.hero-meta-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #6c757d;
  font-weight: 600;
  margin-bottom: 2px;
}

.hero-meta-value {
  font-size: 0.95rem;
  color: #1a3326;
  font-weight: 600;
}

.hero-status-badge {
  font-size: 0.75rem;
  padding: 0.35em 0.75em;
  font-weight: 600;
}

.hero-industry-badge {
  font-size: 0.75rem;
  padding: 0.35em 0.75em;
  background: rgba(9, 23, 111, 0.1);
  color: var(--brand-secondary-dark);
  font-weight: 600;
}

.content-html {
  color: #495057;
  line-height: 1.65;
}

.content-html :deep(p:last-child) {
  margin-bottom: 0;
}

.institution-logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 0.5rem;
  border: 1px solid var(--brand-primary-border);
  background: #fff;
  padding: 4px;
}

.institution-logo-placeholder {
  width: 64px;
  height: 64px;
  border-radius: 0.5rem;
  background: var(--brand-primary);
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-success-light {
  background: rgba(9, 23, 111, 0.12);
  color: var(--brand-secondary-dark);
  font-weight: 600;
}

.font-weight-semibold {
  font-weight: 600;
}

.choose-file-btn {
  background: transparent;
  border: 1px solid var(--brand-primary);
  color: var(--brand-secondary-dark);
  padding: 0.25rem 0.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 0.35rem;
}
.choose-file-btn i {
  font-size: 0.85rem;
}
.choose-file-btn:hover {
  background: var(--brand-primary);
  color: #fff;
  border-color: var(--brand-secondary-dark);
}

/* ── Payment Modal ───────────────────────────────────────────────────── */
.pay-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.pay-modal-card {
  background: #fff;
  border-radius: 1rem;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
  overflow: hidden;
}
.pay-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem 0.85rem;
  border-bottom: 1px solid #e9ecef;
}
.pay-modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a1a;
}
.pay-modal-close {
  background: none;
  border: none;
  font-size: 1rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.15s;
}
.pay-modal-close:hover {
  background: #f1f1f1;
  color: #333;
}
.pay-modal-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.25rem;
  background: var(--brand-surface-neutral);
  border-bottom: 1px solid #e9ecef;
}
.pay-summary-label {
  font-size: 0.85rem;
  color: #555;
  font-weight: 500;
}
.pay-summary-amount {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--brand-primary);
}
.pay-summary-currency {
  font-size: 0.7rem;
  font-weight: 600;
  vertical-align: super;
  margin-right: 2px;
  color: #555;
}
.pay-method-section,
.pay-form-section {
  padding: 1rem 1.25rem 0;
}
.pay-section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #6c757d;
  margin-bottom: 0.6rem;
}
.pay-method-options {
  display: flex;
  gap: 0.65rem;
}
.pay-method-btn {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 0.7rem 0.5rem;
  border: 1.5px solid #dee2e6;
  border-radius: 0.6rem;
  background: #fff;
  cursor: pointer;
  font-size: 0.78rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.pay-method-btn:hover:not(:disabled) {
  border-color: var(--brand-primary);
}
.pay-method-btn--active {
  border-color: var(--brand-primary) !important;
  background: var(--brand-surface-blue);
  box-shadow: 0 0 0 2px rgba(9, 23, 111, 0.15);
}
.pay-method-btn--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pay-method-logo {
  height: 28px;
  width: auto;
  object-fit: contain;
}
.pay-method-name {
  font-size: 0.72rem;
  font-weight: 600;
  color: #333;
}
.pay-method-check {
  position: absolute;
  top: 4px;
  right: 6px;
  font-size: 0.7rem;
}
.pay-method-soon {
  position: absolute;
  top: 4px;
  right: 4px;
  font-size: 0.55rem;
  background: #dee2e6;
  color: #6c757d;
  border-radius: 4px;
  padding: 1px 4px;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.pay-modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1rem 1.25rem 0.75rem;
}
.pay-modal-trust {
  text-align: center;
  font-size: 0.72rem;
  color: #adb5bd;
  padding: 0 1.25rem 1rem;
}
.pay-modal-success {
  text-align: center;
  padding: 2rem 1.5rem;
}

/* Polling / waiting state */
.pay-modal-polling {
  text-align: center;
  padding: 2rem 1.5rem;
}
.pay-polling-spinner {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto;
}
.pay-polling-ring {
  position: absolute;
  inset: 0;
  border: 4px solid #e4ebfb;
  border-top-color: var(--brand-primary);
  border-radius: 50%;
  animation: pay-spin 1s linear infinite;
}
.pay-polling-inner {
  position: absolute;
  inset: 12px;
  background: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pay-polling-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
}
@keyframes pay-spin {
  to {
    transform: rotate(360deg);
  }
}
.pay-poll-timer {
  font-variant-numeric: tabular-nums;
}
.pay-poll-dots {
  display: flex;
  justify-content: center;
  gap: 6px;
}
.pay-poll-dots span {
  width: 8px;
  height: 8px;
  background: var(--brand-primary);
  border-radius: 50%;
  animation: pay-dot-bounce 1.2s infinite ease-in-out both;
}
.pay-poll-dots span:nth-child(1) {
  animation-delay: -0.32s;
}
.pay-poll-dots span:nth-child(2) {
  animation-delay: -0.16s;
}
@keyframes pay-dot-bounce {
  0%,
  80%,
  100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}

/* Failed state */
.pay-modal-failed {
  text-align: center;
  padding: 2rem 1.5rem;
}
.pay-failed-icon {
  animation: pay-shake 0.4s ease;
}
@keyframes pay-shake {
  0%,
  100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-6px);
  }
  75% {
    transform: translateX(6px);
  }
}

/* Modal transition */
.pay-modal-enter-active,
.pay-modal-leave-active {
  transition: opacity 0.2s;
}
.pay-modal-enter-from,
.pay-modal-leave-to {
  opacity: 0;
}
.pay-modal-enter-active .pay-modal-card,
.pay-modal-leave-active .pay-modal-card {
  transition: transform 0.2s;
}
.pay-modal-enter-from .pay-modal-card,
.pay-modal-leave-to .pay-modal-card {
  transform: translateY(16px) scale(0.97);
}
</style>
