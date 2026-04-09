<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import DashboardLayout from "../Layouts/DashboardLayout.vue";

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
  adminStats: { type: Array, default: () => [] },
  recentTenders: { type: Array, default: () => [] },
  activePlan: { type: Object, default: null },
  plans: { type: Array, default: () => [] },
  myApplications: { type: Array, default: () => [] },
});

const user = computed(() => usePage().props.auth.user);

// ── Admin static data ──────────────────────────────────────────────────
const dashboardStats = computed(() => props.adminStats);

const recentTendersFormatted = computed(() =>
  props.recentTenders.map((t) => ({
    id: t.id,
    slug: t.slug,
    title: t.title,
    category: t.institution?.institution_name || "—",
    county: t.county?.name || "—",
    deadline: t.closing_date_and_time
      ? deadlineLabel(t.closing_date_and_time)
      : "—",
    status: t.status?.name || "—",
  }))
);

const deadlineLabel = (dateStr) => {
  const diff = Math.ceil(
    (new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24)
  );
  if (diff < 0) return "Closed";
  if (diff === 0) return "Closes today";
  return `${diff} day${diff !== 1 ? "s" : ""} left`;
};

// ── Plan helpers ──────────────────────────────────────────────────────
const planIcons = ["fas fa-tag", "fas fa-gem", "fas fa-crown"];

const daysLeft = computed(() => {
  if (!props.activePlan) return 0;
  const end = new Date(props.activePlan.end_date);
  const diff = Math.ceil((end - new Date()) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
});

const planExpired = computed(() => {
  if (!props.activePlan) return false;
  return new Date(props.activePlan.end_date) < new Date();
});

const formatDate = (d) => {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-KE", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

// ── Payment modal state ───────────────────────────────────────────────
const payModalOpen = ref(false);
const payModalContext = ref({ type: null, planId: null, amount: 0, label: "" });
const payMethod = ref("mpesa");
const payPhone = ref(user.value?.telephone || "");
const payLoading = ref(false);
const payError = ref("");

const pollCheckoutId = ref(null);
const pollState = ref(null);
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
      /* network hiccup — retry next tick */
    }
  };

  // First poll after 10s, then every 5s
  pollInitTimeout = setTimeout(() => {
    doPoll();
    pollInterval.value = setInterval(doPoll, 5000);
  }, 10000);
};

const openPayModal = (planId) => {
  payError.value = "";
  pollState.value = null;
  pollCheckoutId.value = null;
  pollMessage.value = "";
  pollSeconds.value = 0;
  stopPolling();
  payMethod.value = "mpesa";
  const plan = props.plans.find((p) => p.id === planId);
  payModalContext.value = {
    type: "plan",
    planId,
    amount: plan?.amount || 0,
    label: plan
      ? `${plan.plan_name} (${plan.period} days)`
      : "Subscription Plan",
  };
  payModalOpen.value = true;
};

const closePayModal = () => {
  if (payLoading.value || pollState.value === "waiting") return;
  stopPolling();
  pollState.value = null;
  payModalOpen.value = false;
};

const submitPayment = async () => {
  payError.value = "";
  if (!payPhone.value) {
    payError.value = "Please enter your M-Pesa phone number.";
    return;
  }
  payLoading.value = true;
  try {
    const ctx = payModalContext.value;
    const { data } = await axios.post(route("mpesa.stk_push"), {
      phone: payPhone.value,
      payment_type: "plan",
      plan_id: ctx.planId,
      tender_id: null,
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
</script>

<template>
  <Head title="Dashboard" />

  <DashboardLayout>
    <!-- ══════════════════ ADMIN VIEW ══════════════════ -->
    <template v-if="isAdmin">
      <div class="card border-0 shadow-sm dashboard-hero-card mb-4">
        <div
          class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between py-4"
        >
          <div class="mb-3 mb-md-0">
            <h2 class="h4 font-weight-bold mb-1 text-white">
              Tender Portal Control Center
            </h2>
            <p class="mb-0 text-white-50">
              Manage listings, shortlisting, evaluations, awards, and
              notifications from one place.
            </p>
          </div>
          <div class="d-flex flex-wrap">
            <Link
              :href="route('tenders.create')"
              class="btn btn-light mr-2 mb-2 mb-md-0"
            >
              <i class="fas fa-plus mr-1"></i> New Tender
            </Link>
            <button type="button" class="btn btn-outline-light mb-2 mb-md-0">
              <i class="fas fa-bell mr-1"></i> Notifications
            </button>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div
          v-for="item in dashboardStats"
          :key="item.title"
          class="col-md-6 col-xl-3 mb-3"
        >
          <div class="card border-0 shadow-sm h-100 stat-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <p class="text-muted small mb-1">{{ item.title }}</p>
                  <h3 class="font-weight-bold mb-1 text-dark">
                    {{ Number(item.value).toLocaleString() }}
                  </h3>
                  <p class="mb-0 small text-muted">{{ item.note }}</p>
                </div>
                <span class="stat-icon"><i :class="item.icon"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-lg-7 mb-3 mb-lg-0">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
              <h5 class="font-weight-bold mb-0">Recent Tenders</h5>
            </div>
            <div class="card-body">
              <div
                v-if="!recentTendersFormatted.length"
                class="text-center text-muted py-4"
              >
                <i class="fas fa-inbox d-block fa-2x mb-2 opacity-50"></i> No
                tenders yet.
              </div>
              <div
                v-for="tender in recentTendersFormatted"
                :key="tender.id"
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center py-2 border-bottom tender-row"
              >
                <div class="pr-md-3">
                  <Link
                    :href="
                      route('tenders.public.show', tender.slug || tender.id)
                    "
                    class="mb-1 font-weight-bold text-dark text-decoration-none d-block"
                    >{{ tender.title }}</Link
                  >
                  <p class="mb-0 text-muted small">
                    {{ tender.category }} · {{ tender.county }} ·
                    {{ tender.deadline }}
                  </p>
                </div>
                <span class="badge badge-pill tender-status mt-2 mt-md-0">{{
                  tender.status
                }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
              <h5 class="font-weight-bold mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-6 mb-3">
                  <button
                    class="btn btn-outline-success btn-block py-3"
                    type="button"
                  >
                    <i class="fas fa-bullhorn d-block mb-2"></i> Advertise
                  </button>
                </div>
                <div class="col-6 mb-3">
                  <button
                    class="btn btn-outline-success btn-block py-3"
                    type="button"
                  >
                    <i class="fas fa-filter d-block mb-2"></i> Shortlist
                  </button>
                </div>
                <div class="col-6">
                  <button
                    class="btn btn-outline-success btn-block py-3"
                    type="button"
                  >
                    <i class="fas fa-tasks d-block mb-2"></i> Evaluate
                  </button>
                </div>
                <div class="col-6">
                  <button
                    class="btn btn-outline-success btn-block py-3"
                    type="button"
                  >
                    <i class="fas fa-paper-plane d-block mb-2"></i> Notify
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ══════════════════ USER VIEW ══════════════════ -->
    <template v-else>
      <!-- Welcome hero -->
      <div class="card border-0 shadow-sm user-hero-card mb-4">
        <div class="card-body py-4 px-4">
          <h2 class="h4 font-weight-bold mb-1 text-white">
            Welcome back, {{ user?.name?.split(" ")[0] || "there" }} 👋
          </h2>
          <p class="mb-0 text-white-50">
            Manage your applications and subscription plan below.
          </p>
        </div>
      </div>

      <div class="row mb-4">
        <!-- Current plan card -->
        <div class="col-lg-5 mb-4 mb-lg-0">
          <div class="card border-0 shadow-sm h-100">
            <div
              class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center"
            >
              <h5 class="font-weight-bold mb-0">My Subscription</h5>
              <span
                v-if="activePlan && !planExpired"
                class="badge badge-success"
                >Active</span
              >
              <span
                v-else-if="activePlan && planExpired"
                class="badge badge-danger"
                >Expired</span
              >
              <span v-else class="badge badge-secondary">No Plan</span>
            </div>
            <div class="card-body">
              <template v-if="activePlan && !planExpired">
                <div class="current-plan-display">
                  <div class="current-plan-icon">
                    <i class="fas fa-gem"></i>
                  </div>
                  <div class="current-plan-name">
                    {{ activePlan.plan?.plan_name }}
                  </div>
                  <div class="current-plan-meta">
                    <span
                      ><i class="fas fa-calendar-check mr-1 text-success"></i
                      >Expires {{ formatDate(activePlan.end_date) }}</span
                    >
                    <span class="ml-3"
                      ><i class="fas fa-clock mr-1 text-success"></i
                      >{{ daysLeft }} day{{
                        daysLeft !== 1 ? "s" : ""
                      }}
                      left</span
                    >
                  </div>
                </div>
                <div class="progress mt-3 mb-1" style="height: 6px">
                  <div
                    class="progress-bar bg-success"
                    role="progressbar"
                    :style="{
                      width:
                        Math.min(
                          100,
                          (daysLeft / (activePlan.plan?.period || 30)) * 100
                        ) + '%',
                    }"
                  ></div>
                </div>
                <p class="text-muted small mb-3">
                  {{ activePlan.plan?.period }} day plan · started
                  {{ formatDate(activePlan.start_date) }}
                </p>
                <p class="small text-muted mb-0">
                  Want to extend? Choose a plan below to renew early.
                </p>
              </template>
              <template v-else>
                <div class="text-center py-3">
                  <div class="mb-3">
                    <span
                      class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
                      style="width: 64px; height: 64px"
                    >
                      <i class="fas fa-lock fa-2x text-secondary"></i>
                    </span>
                  </div>
                  <p class="font-weight-bold mb-1">
                    {{
                      activePlan && planExpired
                        ? "Your plan has expired"
                        : "No active subscription"
                    }}
                  </p>
                  <p class="text-muted small mb-0">
                    Subscribe to a plan to access tender details and features.
                  </p>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- My Applications -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <div
              class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center"
            >
              <h5 class="font-weight-bold mb-0">My Applications</h5>
              <span class="badge badge-light border"
                >{{ myApplications.length }} total</span
              >
            </div>
            <div class="card-body p-0">
              <div
                v-if="!myApplications.length"
                class="text-center text-muted py-5"
              >
                <i class="fas fa-file-alt fa-2x mb-2 d-block opacity-50"></i>
                You haven't submitted any applications yet.
              </div>
              <div v-else class="list-group list-group-flush">
                <div
                  v-for="app in myApplications"
                  :key="app.id"
                  class="list-group-item d-flex justify-content-between align-items-start px-3 py-2"
                >
                  <div class="app-title-col mr-2">
                    <Link
                      v-if="app.tender"
                      :href="
                        route(
                          'tenders.public.show',
                          app.tender.slug || app.tender.id
                        )
                      "
                      class="font-weight-semibold text-dark text-decoration-none d-block"
                      style="font-size: 0.9rem"
                      >{{ app.tender.title }}</Link
                    >
                    <span
                      v-else
                      class="font-weight-semibold text-dark"
                      style="font-size: 0.9rem"
                      >—</span
                    >
                    <small class="text-muted d-block">{{
                      app.company_name
                    }}</small>
                  </div>
                  <small class="text-muted flex-shrink-0">{{
                    formatDate(app.created_at)
                  }}</small>
                </div>
              </div>
            </div>
            <div
              v-if="myApplications.length"
              class="card-footer bg-white border-0 text-center py-2"
            >
              <Link
                :href="route('tenders.search')"
                class="btn btn-sm btn-outline-success"
              >
                <i class="fas fa-search mr-1"></i> Find More Tenders
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Plans section -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0">
          <h5 class="font-weight-bold mb-0">
            {{
              activePlan && !planExpired
                ? "Renew or Upgrade Plan"
                : "Choose a Subscription Plan"
            }}
          </h5>
        </div>
        <div class="card-body">
          <div class="plans-row">
            <div
              v-for="(plan, idx) in plans"
              :key="plan.id"
              class="plan-card"
              :class="idx === 1 ? 'plan-card--featured' : ''"
            >
              <div v-if="idx === 1" class="plan-popular-badge">
                Most Popular
              </div>
              <div class="plan-icon mb-2">
                <i :class="planIcons[Math.min(idx, planIcons.length - 1)]"></i>
              </div>
              <div class="plan-name">{{ plan.plan_name }}</div>
              <div class="plan-duration">
                <i class="fas fa-calendar-alt mr-1"></i>{{ plan.period }} day{{
                  plan.period !== 1 ? "s" : ""
                }}
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
                :class="idx === 1 ? 'plan-btn--featured' : ''"
                @click="openPayModal(plan.id)"
              >
                <i class="fas fa-check-circle mr-1"></i>
                {{
                  activePlan && !planExpired
                    ? "Renew with this Plan"
                    : "Choose Plan"
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </DashboardLayout>

  <!-- ── PAYMENT MODAL ───────────────────────────────────────────── -->
  <teleport to="body">
    <transition name="pay-modal">
      <div
        v-if="payModalOpen"
        class="pay-modal-backdrop"
        @click.self="closePayModal"
      >
        <div class="pay-modal-card" role="dialog" aria-modal="true">
          <div class="pay-modal-header">
            <div class="pay-modal-title">
              <i class="fas fa-lock mr-2 text-success"></i>Complete Payment
            </div>
            <button
              class="pay-modal-close"
              :disabled="payLoading"
              @click="closePayModal"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>

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

          <!-- Waiting for payment -->
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
              >.<br />Enter your M-Pesa PIN to confirm.
            </p>
            <p class="pay-poll-timer small text-secondary mb-3">
              <i class="fas fa-clock mr-1"></i>{{ pollSeconds }}s elapsed
            </p>
            <div class="pay-poll-dots">
              <span></span><span></span><span></span>
            </div>
            <p class="text-muted mt-3" style="font-size: 0.75rem">
              Do NOT close this window. Page will refresh automatically on
              success.
            </p>
          </div>

          <!-- Paid -->
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

          <!-- Failed / timeout -->
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
              class="btn btn-outline-primary btn-sm mr-2"
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
            <div class="pay-method-section">
              <p class="pay-section-label">Select Payment Method</p>
              <div class="pay-method-options">
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
                  <span style="display: none"
                    ><i class="fas fa-mobile-alt mr-1"></i> M-Pesa</span
                  >
                  <span class="pay-method-name">M-Pesa</span>
                  <span v-if="payMethod === 'mpesa'" class="pay-method-check"
                    ><i class="fas fa-check-circle text-success"></i
                  ></span>
                </button>
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

            <div v-if="payMethod === 'mpesa'" class="pay-form-section">
              <p class="pay-section-label">M-Pesa Details</p>
              <div class="form-group mb-1">
                <label class="small font-weight-bold mb-1">Phone Number</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"
                      ><i class="fas fa-mobile-alt text-success"></i
                    ></span>
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
                <span v-if="payLoading"
                  ><i class="fas fa-spinner fa-spin mr-2"></i>Sending…</span
                >
                <span v-else
                  ><i class="fas fa-paper-plane mr-2"></i>Send Payment
                  Request</span
                >
              </button>
            </div>

            <div class="pay-modal-trust">
              <i class="fas fa-shield-alt text-success mr-1"></i> Secure &amp;
              Encrypted
              <span class="mx-2">·</span>
              <i class="fas fa-undo text-success mr-1"></i> Verified by
              Safaricom
            </div>
          </template>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
/* ── Hero cards ──────────────────────────────────────────────────────── */
.dashboard-hero-card {
  background: var(--brand-primary);
  border-radius: 0.8rem;
}
.user-hero-card {
  background: var(--brand-secondary-dark);
  border-radius: 0.8rem;
}

/* ── Admin stat cards ────────────────────────────────────────────────── */
.stat-card {
  border-left: 3px solid rgba(9, 23, 111, 0.35);
}
.stat-icon {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: rgba(9, 23, 111, 0.14);
  color: var(--brand-secondary-dark);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.tender-row:last-child {
  border-bottom: 0 !important;
}
.tender-status {
  background: rgba(9, 23, 111, 0.14);
  color: var(--brand-secondary-dark);
  font-weight: 700;
}

/* ── Current plan display ────────────────────────────────────────────── */
.current-plan-display {
  text-align: center;
  padding: 1rem 0 0.5rem;
}
.current-plan-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 0.5rem;
  border-radius: 50%;
  background: rgba(9, 23, 111, 0.12);
  color: var(--brand-secondary-dark);
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.current-plan-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--brand-primary-dark);
}
.current-plan-meta {
  font-size: 0.82rem;
  color: #6c757d;
  margin-top: 0.35rem;
}
.app-title-col {
  min-width: 0;
}

/* ── Plan cards ──────────────────────────────────────────────────────── */
.plans-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1rem;
  margin-top: 0.5rem;
}
.plan-card {
  position: relative;
  background: #fff;
  border: 1.5px solid #e4ebfb;
  border-radius: 1rem;
  padding: 1.5rem 1.25rem;
  min-width: 180px;
  max-width: 220px;
  flex: 1 1 180px;
  text-align: center;
  transition: box-shadow 0.2s, transform 0.2s;
}
.plan-card:hover {
  box-shadow: 0 4px 24px rgba(9, 23, 111, 0.13);
  transform: translateY(-2px);
}
.plan-card--featured {
  border-color: var(--brand-primary);
  background: var(--brand-surface-blue);
  box-shadow: 0 4px 18px rgba(9, 23, 111, 0.18);
}
.plan-popular-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--brand-primary);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  border-radius: 999px;
  padding: 2px 12px;
  white-space: nowrap;
}
.plan-icon {
  font-size: 1.6rem;
  color: var(--brand-primary);
}
.plan-card--featured .plan-icon {
  color: var(--brand-secondary-dark);
}
.plan-name {
  font-size: 1rem;
  font-weight: 700;
  color: var(--brand-primary-dark);
}
.plan-duration {
  font-size: 0.8rem;
  color: #6c757d;
  margin: 0.2rem 0 0.5rem;
}
.plan-price {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--brand-primary-dark);
  margin-bottom: 0.5rem;
}
.plan-currency {
  font-size: 0.8rem;
  font-weight: 600;
  vertical-align: super;
  margin-right: 2px;
}
.plan-desc {
  font-size: 0.78rem;
  color: #6c757d;
  margin-bottom: 0.75rem;
}
.plan-btn {
  width: 100%;
  border: 2px solid var(--brand-primary);
  border-radius: 999px;
  background: #fff;
  color: var(--brand-primary);
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.45rem 1rem;
  cursor: pointer;
  transition: all 0.18s;
}
.plan-btn:hover,
.plan-btn--featured {
  background: var(--brand-primary);
  color: #fff;
}

/* ── Payment modal ───────────────────────────────────────────────────── */
.pay-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.52);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}
.pay-modal-card {
  background: #fff;
  border-radius: 1rem;
  width: 100%;
  max-width: 420px;
  max-height: 92vh;
  overflow-y: auto;
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.22);
}
.pay-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem 0.75rem;
  border-bottom: 1px solid #f0f0f0;
}
.pay-modal-title {
  font-weight: 700;
  font-size: 1rem;
}
.pay-modal-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0.2rem 0.4rem;
}
.pay-modal-summary {
  background: #f8faff;
  padding: 0.85rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e4ebfb;
}
.pay-summary-label {
  font-size: 0.82rem;
  color: #6c757d;
}
.pay-summary-amount {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--brand-primary-dark);
}
.pay-summary-currency {
  font-size: 0.72rem;
  vertical-align: super;
  margin-right: 2px;
  font-weight: 600;
}
.pay-method-section {
  padding: 0.9rem 1.25rem 0;
}
.pay-section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #7f889d;
  margin-bottom: 0.5rem;
}
.pay-method-options {
  display: flex;
  gap: 0.5rem;
}
.pay-method-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.6rem 0.4rem;
  border: 1.5px solid #dee2e6;
  border-radius: 0.65rem;
  background: #fff;
  cursor: pointer;
  font-size: 0.78rem;
  transition: all 0.15s;
  gap: 0.25rem;
}
.pay-method-btn--active {
  border-color: var(--brand-primary);
  background: var(--brand-surface-blue);
}
.pay-method-btn--disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.pay-method-logo {
  width: 28px;
  height: 20px;
  object-fit: contain;
}
.pay-method-name {
  font-size: 0.72rem;
  font-weight: 600;
}
.pay-method-check {
  color: var(--brand-primary);
  font-size: 0.8rem;
}
.pay-method-soon {
  font-size: 0.62rem;
  background: #e9ecef;
  padding: 1px 6px;
  border-radius: 999px;
}
.pay-form-section {
  padding: 0.9rem 1.25rem 0;
}
.pay-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #f0f0f0;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}
.pay-modal-trust {
  text-align: center;
  font-size: 0.72rem;
  color: #8b93a6;
  padding: 0 1.25rem 1rem;
}

/* Polling */
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

/* Success / Failed */
.pay-modal-success,
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

