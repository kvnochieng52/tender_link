<script setup>
import { computed, ref, watch, reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  encryptedId: { type: String, required: true },
  tender: { type: Object, required: true },
  applicationStatuses: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
  evaluationCriteria: { type: Array, default: () => [] },
  applications: { type: Array, default: () => [] },
  submittedDocuments: { type: Array, default: () => [] },
  communications: { type: Array, default: () => [] },
  sentCommunications: { type: Array, default: () => [] },
  communicationTemplates: { type: Array, default: () => [] },
  recipientGroupCounts: { type: Object, default: () => ({}) },
  clarifications: { type: Array, default: () => [] },
  award: { type: Object, default: null },
  audit: { type: Array, default: () => [] },
  counters: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
});

const toast = useToast();

const sections = [
  { key: "details", label: "Tender Details", icon: "fas fa-file-invoice" },
  { key: "documents", label: "Tender Documents", icon: "fas fa-folder-open" },
  { key: "criteria", label: "Evaluation Criteria", icon: "fas fa-list-check" },
  { key: "applications", label: "All Applications", icon: "fas fa-users" },
  { key: "submitted", label: "Submitted Documents", icon: "fas fa-file-upload" },
  { key: "compliance", label: "Compliance Evaluation", icon: "fas fa-clipboard-check" },
  { key: "technical", label: "Technical Evaluation", icon: "fas fa-cogs" },
  { key: "financial", label: "Financial Evaluation", icon: "fas fa-money-bill-wave" },
  { key: "diligence", label: "Due Diligence", icon: "fas fa-search-dollar" },
  { key: "communications", label: "Communications", icon: "fas fa-comments" },
  { key: "clarifications", label: "Clarifications", icon: "fas fa-question-circle" },
  { key: "shortlist", label: "Shortlisted Bidders", icon: "fas fa-star" },
  { key: "ranking", label: "Final Ranking", icon: "fas fa-medal" },
  { key: "recommendation", label: "Recommendation", icon: "fas fa-thumbs-up" },
  { key: "award", label: "Award Information", icon: "fas fa-trophy" },
  { key: "audit", label: "Audit Trail", icon: "fas fa-history" },
];

const activeSection = ref("details");

const cards = computed(() => [
  { label: "Applications", value: props.counters.applications ?? 0, tone: "primary" },
  { label: "Submitted", value: props.counters.submitted ?? 0, tone: "info" },
  { label: "Under Review", value: props.counters.under_review ?? 0, tone: "secondary" },
  { label: "Compliant", value: props.counters.compliant ?? 0, tone: "success" },
  { label: "Non-Compliant", value: props.counters.non_compliant ?? 0, tone: "danger" },
  { label: "Shortlisted", value: props.counters.shortlisted ?? 0, tone: "warning" },
  { label: "Due Diligence", value: props.counters.due_diligence ?? 0, tone: "info" },
  { label: "Recommended", value: props.counters.recommended ?? 0, tone: "success" },
]);

const formatDate = (v) => (v ? new Date(v).toLocaleDateString() : "—");
const formatDateTime = (v) => (v ? new Date(v).toLocaleString() : "—");
const scoreLabel = (v) => (v === null || v === undefined ? "—" : Number(v).toFixed(2));

const statusPillClass = (s) => {
  if (!s) return "badge-secondary";
  if (s === "Recommended") return "badge-success";
  if (s === "Eligible") return "badge-info";
  if (s === "Disqualified") return "badge-danger";
  if (s === "Shortlisted" || s === "Confirmed" || s === "Pass") return "badge-success";
  if (s === "Rejected" || s === "Fail") return "badge-danger";
  if (s === "Pending") return "badge-warning";
  return "badge-info";
};

const criteriaByCategory = computed(() => ({
  compliance: props.evaluationCriteria.filter((c) => c.category === "compliance"),
  technical: props.evaluationCriteria.filter((c) => c.category === "technical"),
  financial: props.evaluationCriteria.filter((c) => c.category === "financial"),
}));

// ── Applications tab: filters ────────────────────────────────────────
const appFilters = ref({
  q: props.filters.q ?? "",
  application_status_id: props.filters.application_status_id ?? "",
  category_id: props.filters.category_id ?? "",
  date_from: props.filters.date_from ?? "",
  date_to: props.filters.date_to ?? "",
  compliance_status: props.filters.compliance_status ?? "",
  technical_status: props.filters.technical_status ?? "",
  due_diligence_status: props.filters.due_diligence_status ?? "",
  min_score: props.filters.min_score ?? "",
  min_rank: props.filters.min_rank ?? "",
  shortlisted: props.filters.shortlisted ?? "",
  recommended: props.filters.recommended ?? "",
  county_id: props.filters.county_id ?? "",
  certification: props.filters.certification ?? "",
  min_years: props.filters.min_years ?? "",
  min_turnover: props.filters.min_turnover ?? "",
  max_price: props.filters.max_price ?? "",
});

let searchTimer = null;
watch(
  appFilters,
  (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      const clean = Object.fromEntries(
        Object.entries(val).filter(([, v]) => v !== "" && v !== null)
      );
      router.get(
        route("admin.tenders.workspace", { encryptedId: props.encryptedId }),
        clean,
        { preserveState: true, preserveScroll: true, replace: true }
      );
    }, 350);
  },
  { deep: true }
);
const resetFilters = () => {
  appFilters.value = Object.fromEntries(
    Object.keys(appFilters.value).map((k) => [k, ""])
  );
};

// ── Criteria tab: add/edit form ──────────────────────────────────────
const criteriaCategoryTab = ref("compliance");
const criterionForm = useForm({
  id: null,
  category: "compliance",
  title: "",
  notes: "",
  max_score: 100,
  weight: 1,
  is_mandatory: false,
  scoring_method: "percentage",
});

// Section weights form (Compliance / Technical / Financial split, must sum to 100)
const weightsForm = useForm({
  compliance_weight: Number(props.tender.compliance_weight ?? 0),
  technical_weight:  Number(props.tender.technical_weight ?? 70),
  financial_weight:  Number(props.tender.financial_weight ?? 30),
});
const weightsTotal = computed(() =>
  Number(weightsForm.compliance_weight || 0)
  + Number(weightsForm.technical_weight || 0)
  + Number(weightsForm.financial_weight || 0)
);
const saveWeights = () => {
  weightsForm.patch(
    route("admin.tenders.weights.save", { encryptedId: props.encryptedId }),
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Section weights saved."),
    }
  );
};

const criteriaLocked = computed(() => !!props.tender.criteria_locked_at);

// ── Auto-recommend top-ranked bidder ─────────────────────────────────
const autoRecommendTop = () => {
  if (!window.confirm("Auto-recommend the top-ranked eligible bidder based on the current evaluation?")) return;
  router.post(
    route("admin.tenders.recommend_top", { encryptedId: props.encryptedId }),
    {},
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Top-ranked bidder recommended."),
    }
  );
};

// ── Bidder comparison ────────────────────────────────────────────────
const compareIds = ref([]);
const toggleCompare = (id) => {
  const i = compareIds.value.indexOf(id);
  if (i >= 0) compareIds.value.splice(i, 1);
  else if (compareIds.value.length < 10) compareIds.value.push(id);
  else toast.info("You can compare up to 10 bidders at a time.");
};
const compareApps = computed(() =>
  props.applications.filter((a) => compareIds.value.includes(a.id))
);
const showCompare = ref(false);
const openCriterionForm = (category, existing = null) => {
  criteriaCategoryTab.value = category;
  if (existing) {
    criterionForm.id = existing.id;
    criterionForm.category = existing.category;
    criterionForm.title = existing.title;
    criterionForm.notes = existing.notes || "";
    criterionForm.max_score = existing.max_score;
    criterionForm.weight = existing.weight;
    criterionForm.is_mandatory = !!existing.is_mandatory;
    criterionForm.scoring_method = existing.scoring_method || "percentage";
  } else {
    criterionForm.reset();
    criterionForm.category = category;
    criterionForm.max_score = 100;
    criterionForm.weight = 1;
    criterionForm.is_mandatory = false;
    criterionForm.scoring_method = "percentage";
  }
};
const saveCriterion = () => {
  if (criterionForm.id) {
    criterionForm.patch(
      route("admin.tenders.criteria.update", {
        encryptedId: props.encryptedId,
        criterionId: criterionForm.id,
      }),
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success("Criterion updated.");
          criterionForm.reset();
        },
      }
    );
  } else {
    criterionForm.post(
      route("admin.tenders.criteria.store", { encryptedId: props.encryptedId }),
      {
        preserveScroll: true,
        onSuccess: () => {
          toast.success("Criterion added.");
          criterionForm.reset();
          criterionForm.category = criteriaCategoryTab.value;
          criterionForm.max_score = 100;
          criterionForm.weight = 1;
        },
      }
    );
  }
};
const deleteCriterion = (c) => {
  if (!window.confirm(`Delete criterion "${c.title}"?`)) return;
  router.delete(
    route("admin.tenders.criteria.destroy", {
      encryptedId: props.encryptedId,
      criterionId: c.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Criterion removed."),
    }
  );
};

// ── Scoring grid state (per section) ─────────────────────────────────
const scoringSection = ref("compliance");
const scoreDraft = reactive({});
props.applications.forEach((app) => {
  scoreDraft[app.id] = {};
  (app.evaluation_scores || []).forEach((s) => {
    scoreDraft[app.id][s.criterion_id] = s.score;
  });
});
const criteriaForSection = computed(() =>
  criteriaByCategory.value[scoringSection.value] || []
);

const saveScores = () => {
  const scores = [];
  criteriaForSection.value.forEach((c) => {
    props.applications.forEach((a) => {
      const v = scoreDraft[a.id]?.[c.id];
      if (v !== "" && v !== undefined) {
        scores.push({
          application_id: a.id,
          criterion_id: c.id,
          score: v === null ? null : Number(v),
        });
      }
    });
  });
  if (!scores.length) {
    toast.info("No score values to save.");
    return;
  }
  router.post(
    route("admin.tenders.scores.save", { encryptedId: props.encryptedId }),
    { scores },
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Scores saved."),
    }
  );
};

// ── Due Diligence ────────────────────────────────────────────────────
const ddForm = useForm({
  due_diligence_status: "",
  due_diligence_notes: "",
});
const activeDdApp = ref(null);
const openDdEditor = (app) => {
  activeDdApp.value = app;
  ddForm.due_diligence_status = app.due_diligence_status || "pending";
  ddForm.due_diligence_notes = app.due_diligence_notes || "";
};
const saveDd = () => {
  if (!activeDdApp.value) return;
  ddForm.patch(
    route("admin.tenders.applications.dd", {
      encryptedId: props.encryptedId,
      applicationId: activeDdApp.value.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Due diligence updated.");
        activeDdApp.value = null;
      },
    }
  );
};

// ── Disqualify (explicit) ────────────────────────────────────────────
const activeDqApp = ref(null);
const dqForm = useForm({ reason: "" });
const openDisqualify = (app) => {
  activeDqApp.value = app;
  dqForm.reason = "";
};
const confirmDisqualify = () => {
  if (!activeDqApp.value) return;
  if (!dqForm.reason.trim()) {
    toast.error("A reason is required to disqualify a bidder.");
    return;
  }
  dqForm.post(
    route("admin.tenders.applications.disqualify", {
      encryptedId: props.encryptedId,
      applicationId: activeDqApp.value.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Bidder disqualified.");
        activeDqApp.value = null;
      },
    }
  );
};

// ── Recommendation ───────────────────────────────────────────────────
const activeRecApp = ref(null);
const recForm = useForm({ recommended: true, recommendation_note: "" });
const openRecEditor = (app, recommended) => {
  activeRecApp.value = app;
  recForm.recommended = recommended;
  recForm.recommendation_note = app.recommendation_note || "";
};
const saveRecommendation = () => {
  if (!activeRecApp.value) return;
  recForm.patch(
    route("admin.tenders.applications.recommend", {
      encryptedId: props.encryptedId,
      applicationId: activeRecApp.value.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Recommendation saved.");
        activeRecApp.value = null;
      },
    }
  );
};

// ── Shortlist ────────────────────────────────────────────────────────
const toggleShortlist = (app) => {
  router.post(
    route("admin.tenders.applications.shortlist", {
      encryptedId: props.encryptedId,
      applicationId: app.id,
    }),
    {},
    {
      preserveScroll: true,
      onSuccess: () =>
        toast.success(
          app.shortlisted ? "Removed from shortlist." : "Added to shortlist."
        ),
    }
  );
};

// ── Tender-level communications (send email to bidders) ─────────────
const commForm = useForm({
  category: "clarification_request",
  recipients: "specific",
  application_id: "",
  subject: "",
  body: "",
});

const applyTemplate = (categoryKey) => {
  const tpl = props.communicationTemplates.find((t) => t.key === categoryKey);
  if (!tpl) return;
  commForm.category = categoryKey;
  commForm.subject = tpl.subject;
  commForm.body = tpl.body;
};

// Seed with the default category on mount.
applyTemplate(commForm.category);

const recipientCount = computed(() => {
  const c = props.recipientGroupCounts || {};
  switch (commForm.recipients) {
    case "all":         return c.all ?? 0;
    case "shortlisted": return c.shortlisted ?? 0;
    case "recommended": return c.recommended ?? 0;
    case "awarded":     return c.awarded ?? 0;
    case "specific":    return commForm.application_id ? 1 : 0;
    default:            return 0;
  }
});

const sendCommunication = () => {
  if (!recipientCount.value) {
    toast.error("No recipients selected.");
    return;
  }
  commForm.post(
    route("admin.tenders.communications.send", {
      encryptedId: props.encryptedId,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Communication sent.");
        // Reset just the body/subject/application; keep category+recipients pane
        // so the officer can send follow-ups without re-picking.
        commForm.application_id = "";
      },
    }
  );
};

// ── Clarifications ───────────────────────────────────────────────────
const clarForm = useForm({ question: "", application_id: "" });
const askClarification = () => {
  clarForm.post(
    route("admin.tenders.clarifications.store", {
      encryptedId: props.encryptedId,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Clarification recorded.");
        clarForm.reset();
      },
    }
  );
};
const activeAnswerId = ref(null);
const answerText = ref("");
const startAnswer = (c) => {
  activeAnswerId.value = c.id;
  answerText.value = c.answer || "";
};
const submitAnswer = () => {
  if (!activeAnswerId.value) return;
  router.patch(
    route("admin.tenders.clarifications.answer", {
      encryptedId: props.encryptedId,
      clarificationId: activeAnswerId.value,
    }),
    { answer: answerText.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Answer saved.");
        activeAnswerId.value = null;
        answerText.value = "";
      },
    }
  );
};

// ── Award ────────────────────────────────────────────────────────────
const awardForm = useForm({
  application_id: props.award?.application_id ?? "",
  contract_value: props.award?.contract_value ?? "",
  reference_no: props.award?.reference_no ?? "",
  awarded_at: props.award?.awarded_at ? String(props.award.awarded_at).slice(0, 10) : "",
  notes: props.award?.notes ?? "",
});
const saveAward = () => {
  awardForm.post(
    route("admin.tenders.award.save", { encryptedId: props.encryptedId }),
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Award recorded."),
    }
  );
};
const removeAward = () => {
  if (!window.confirm("Remove the recorded award for this tender?")) return;
  router.delete(
    route("admin.tenders.award.destroy", { encryptedId: props.encryptedId }),
    {
      preserveScroll: true,
      onSuccess: () => toast.success("Award removed."),
    }
  );
};

// Ranking-tab helper (sorted, filtered from same list)
const rankedApplications = computed(() =>
  [...props.applications]
    .filter((a) => a.rank !== null)
    .sort((a, b) => a.rank - b.rank)
);

const shortlistedApps = computed(() =>
  props.applications.filter((a) => a.shortlisted)
);
</script>

<template>
  <Head :title="`Workspace · ${tender.tender_no || tender.title}`" />

  <DashboardLayout>
    <!-- Hero -->
    <div class="card border-0 shadow-sm mb-3 workspace-hero">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
          <div class="mr-3" style="min-width: 0; flex: 1">
            <small class="text-muted d-block">Tender Workspace</small>
            <h4 class="mb-1 text-truncate">{{ tender.title }}</h4>
            <div class="text-muted small">
              <span v-if="tender.tender_no" class="mr-3">
                <i class="fas fa-hashtag me-1"></i>{{ tender.tender_no }}
              </span>
              <span v-if="tender.institution" class="mr-3">
                <i class="far fa-building me-1"></i>
                {{ tender.institution.institution_name }}
              </span>
              <span v-if="tender.status" class="mr-3">
                <i class="fas fa-flag me-1"></i>{{ tender.status.name }}
              </span>
              <span v-if="tender.closing_date_and_time">
                <i class="far fa-calendar-alt me-1"></i>
                Closes {{ formatDateTime(tender.closing_date_and_time) }}
              </span>
            </div>
          </div>
          <div class="d-flex flex-wrap" style="gap: 0.5rem">
            <Link
              :href="route('tenders.public.show', { slug: tender.slug })"
              class="btn btn-sm btn-outline-secondary"
            >
              <i class="fas fa-external-link-alt me-1"></i> Public View
            </Link>
            <Link
              :href="route('tenders.edit', { encryptedId })"
              class="btn btn-sm btn-outline-success"
            >
              <i class="fas fa-pen me-1"></i> Edit Tender
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Counters -->
    <div class="row workspace-stats mb-3">
      <div v-for="c in cards" :key="c.label" class="col-6 col-md-3 mb-2">
        <div class="card stat-card border-0 shadow-sm h-100" :class="`tone-${c.tone}`">
          <div class="card-body p-3">
            <div class="stat-label">{{ c.label }}</div>
            <div class="stat-value">{{ c.value }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row workspace-body">
      <!-- Sidebar -->
      <div class="col-lg-3 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="list-group list-group-flush workspace-nav">
            <button
              v-for="s in sections"
              :key="s.key"
              type="button"
              class="list-group-item list-group-item-action d-flex align-items-center"
              :class="{ active: activeSection === s.key }"
              @click="activeSection = s.key"
            >
              <i :class="[s.icon, 'me-2']" style="width: 18px"></i>
              <span class="flex-grow-1 text-truncate">{{ s.label }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Section body -->
      <div class="col-lg-9">
        <!-- ── DETAILS ─────────────────────────────────────────────── -->
        <div v-if="activeSection === 'details'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Tender Details</h5>
            <div class="row small">
              <div class="col-md-6 mb-3">
                <div class="text-muted">Title</div>
                <div class="font-weight-semibold">{{ tender.title }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Tender No.</div>
                <div class="font-weight-semibold">{{ tender.tender_no || "—" }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Institution</div>
                <div class="font-weight-semibold">{{ tender.institution?.institution_name || "—" }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Industry</div>
                <div class="font-weight-semibold">{{ tender.industry?.name || "—" }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">County</div>
                <div class="font-weight-semibold">{{ tender.county?.name || "—" }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Status</div>
                <div class="font-weight-semibold">{{ tender.status?.name || "—" }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Closing Date</div>
                <div class="font-weight-semibold">{{ formatDateTime(tender.closing_date_and_time) }}</div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="text-muted">Expiry Date</div>
                <div class="font-weight-semibold">{{ formatDateTime(tender.expiry_date) }}</div>
              </div>
              <div v-if="categories.length" class="col-12 mb-3">
                <div class="text-muted">Categories ({{ categories.length }})</div>
                <ul class="mb-0 pl-3">
                  <li v-for="c in categories" :key="c.id">
                    <strong>{{ c.tender_no }}</strong> — {{ c.title }}
                  </li>
                </ul>
              </div>
              <div v-if="tender.description" class="col-12">
                <div class="text-muted mb-1">Description</div>
                <div v-html="tender.description" class="content-html"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── DOCUMENTS ───────────────────────────────────────────── -->
        <div v-else-if="activeSection === 'documents'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Tender Documents</h5>
            <div class="mb-3">
              <div class="small text-muted mb-1">Advert & Templates</div>
              <div class="d-flex flex-wrap" style="gap: 0.5rem">
                <a v-if="tender.advert_file_path" :href="`/storage/${tender.advert_file_path}`" target="_blank" class="btn btn-sm btn-outline-success">
                  <i class="fas fa-file-alt me-1"></i>{{ tender.advert_file_name || "Tender Advert" }}
                </a>
                <a v-if="tender.self_declaration_file_path" :href="`/storage/${tender.self_declaration_file_path}`" target="_blank" class="btn btn-sm btn-outline-success">
                  <i class="fas fa-file-signature me-1"></i>{{ tender.self_declaration_file_name || "Self Declaration Form" }}
                </a>
                <a v-if="tender.confidential_questionnaire_file_path" :href="`/storage/${tender.confidential_questionnaire_file_path}`" target="_blank" class="btn btn-sm btn-outline-success">
                  <i class="fas fa-clipboard-list me-1"></i>{{ tender.confidential_questionnaire_file_name || "Confidential Business Questionnaire" }}
                </a>
                <span v-if="!tender.advert_file_path && !tender.self_declaration_file_path && !tender.confidential_questionnaire_file_path" class="text-muted small">No template documents attached.</span>
              </div>
            </div>
            <div>
              <div class="small text-muted mb-1">Attached Files ({{ tender.files?.length || 0 }})</div>
              <div v-if="!tender.files?.length" class="text-muted small py-2">No additional files attached.</div>
              <ul v-else class="list-unstyled mb-0">
                <li v-for="f in tender.files" :key="f.id" class="d-flex align-items-center border rounded p-2 mb-2">
                  <i class="fas fa-file text-success me-2"></i>
                  <span class="flex-grow-1 text-truncate">{{ f.file_name }}</span>
                  <a :href="`/storage/${f.filepath}`" target="_blank" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-download"></i></a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- ── EVALUATION CRITERIA ─────────────────────────────────── -->
        <div v-else-if="activeSection === 'criteria'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">
              Evaluation Criteria
              <span
                v-if="criteriaLocked"
                class="badge badge-danger ms-2"
                title="Criteria are locked because the tender has closed"
              >
                <i class="fas fa-lock me-1"></i> Locked
              </span>
            </h5>
            <p class="text-muted small">
              Define what evaluators score bidders on. Each criterion is grouped
              by section (Compliance / Technical / Financial). Section-level
              weights (below) decide how each section contributes to the total.
              Mandatory Pass/Fail criteria disqualify bidders that fail them.
            </p>

            <!-- ── Section weights ─────────────────────────────────── -->
            <div class="border rounded p-3 mb-3 bg-light">
              <h6 class="mb-2">Section weights</h6>
              <form @submit.prevent="saveWeights">
                <div class="row small align-items-end">
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Compliance %</label>
                    <input
                      v-model="weightsForm.compliance_weight"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="form-control form-control-sm"
                      :disabled="criteriaLocked"
                    />
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Technical %</label>
                    <input
                      v-model="weightsForm.technical_weight"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="form-control form-control-sm"
                      :disabled="criteriaLocked"
                    />
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Financial %</label>
                    <input
                      v-model="weightsForm.financial_weight"
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      class="form-control form-control-sm"
                      :disabled="criteriaLocked"
                    />
                  </div>
                  <div class="col-md-3 mb-2 d-flex align-items-center" style="gap: 0.5rem">
                    <span
                      class="badge"
                      :class="Math.abs(weightsTotal - 100) < 0.01 ? 'badge-success' : 'badge-warning'"
                      style="font-size: 0.875rem"
                    >
                      Total: {{ weightsTotal }}%
                    </span>
                    <button
                      type="submit"
                      class="btn btn-sm btn-success"
                      :disabled="criteriaLocked || weightsForm.processing || Math.abs(weightsTotal - 100) >= 0.01"
                    >
                      Save weights
                    </button>
                  </div>
                </div>
                <small class="text-muted">
                  Weights must add up to exactly 100.
                  Total (%) = compliance% × compliance weight + technical% × technical weight + financial% × financial weight.
                </small>
              </form>
            </div>

            <ul class="nav nav-pills mb-3" style="gap: 0.5rem">
              <li v-for="cat in ['compliance', 'technical', 'financial']" :key="cat" class="nav-item">
                <button
                  type="button"
                  class="nav-link btn-sm"
                  :class="{ active: criteriaCategoryTab === cat }"
                  @click="criteriaCategoryTab = cat"
                >
                  {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
                  <span class="badge badge-light ms-1">
                    {{ criteriaByCategory[cat].length }}
                  </span>
                </button>
              </li>
            </ul>

            <table class="table table-sm">
              <thead>
                <tr>
                  <th style="width: 35%">Criterion</th>
                  <th>Method</th>
                  <th class="text-center">Max</th>
                  <th class="text-center">Weight</th>
                  <th class="text-center">Mandatory</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!criteriaByCategory[criteriaCategoryTab].length">
                  <td colspan="6" class="text-muted small text-center py-3">
                    No {{ criteriaCategoryTab }} criteria yet.
                  </td>
                </tr>
                <tr v-for="c in criteriaByCategory[criteriaCategoryTab]" :key="c.id">
                  <td>
                    <div>{{ c.title }}</div>
                    <small v-if="c.notes" class="text-muted d-block">{{ c.notes }}</small>
                  </td>
                  <td class="small text-muted">{{ (c.scoring_method || "percentage").replace("_", " ") }}</td>
                  <td class="text-center">{{ c.max_score }}</td>
                  <td class="text-center">{{ c.weight }}</td>
                  <td class="text-center">
                    <span v-if="c.is_mandatory" class="badge badge-danger">Mandatory</span>
                    <span v-else class="text-muted small">—</span>
                  </td>
                  <td class="text-right">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary me-1"
                      :disabled="criteriaLocked"
                      @click="openCriterionForm(c.category, c)"
                    >
                      <i class="fas fa-pen"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-danger"
                      :disabled="criteriaLocked"
                      @click="deleteCriterion(c)"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="criteriaLocked" class="alert alert-warning small mb-3">
              <i class="fas fa-lock me-1"></i>
              This tender has closed. Evaluation criteria are locked to preserve
              the integrity of the evaluation. Reopen or amend the tender to
              change criteria.
            </div>

            <div v-else class="border rounded p-3 bg-light">
              <h6 class="mb-2">
                {{ criterionForm.id ? "Edit criterion" : "Add criterion" }}
              </h6>
              <form @submit.prevent="saveCriterion">
                <div class="row small">
                  <div class="col-md-6 mb-2">
                    <label class="small mb-1">Title <span class="text-danger">*</span></label>
                    <input v-model="criterionForm.title" type="text" class="form-control form-control-sm" required />
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Scoring method</label>
                    <select v-model="criterionForm.scoring_method" class="form-control form-control-sm">
                      <option value="pass_fail">Pass / Fail</option>
                      <option value="rating">Rating scale (0–max)</option>
                      <option value="numeric">Numeric</option>
                      <option value="percentage">Percentage (0–100)</option>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2 d-flex align-items-end">
                    <div class="form-check">
                      <input
                        v-model="criterionForm.is_mandatory"
                        type="checkbox"
                        class="form-check-input"
                        id="crit-mandatory"
                      />
                      <label for="crit-mandatory" class="form-check-label small">
                        Mandatory (fail = disqualified)
                      </label>
                    </div>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Max score</label>
                    <input v-model="criterionForm.max_score" type="number" step="0.01" min="0.01" class="form-control form-control-sm" required />
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="small mb-1">Weight</label>
                    <input v-model="criterionForm.weight" type="number" step="0.01" min="0.01" class="form-control form-control-sm" required />
                    <small class="text-muted">Ignored for mandatory Pass/Fail criteria.</small>
                  </div>
                  <div class="col-md-6 mb-2"></div>
                  <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-success w-100" :disabled="criterionForm.processing">
                      {{ criterionForm.id ? "Update" : "Add" }}
                    </button>
                  </div>
                  <div class="col-12 mb-2">
                    <label class="small mb-1">Notes</label>
                    <textarea v-model="criterionForm.notes" class="form-control form-control-sm" rows="2"></textarea>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- ── APPLICATIONS ────────────────────────────────────────── -->
        <div v-else-if="activeSection === 'applications'" class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 0.5rem">
              <h5 class="mb-0">
                Applications
                <span class="text-muted small ms-2">{{ applications.length }} bidders on this tender</span>
              </h5>
              <div class="d-flex flex-wrap" style="gap: 0.25rem">
                <button
                  v-if="compareIds.length >= 2"
                  type="button"
                  class="btn btn-sm btn-outline-info"
                  @click="showCompare = !showCompare"
                >
                  <i class="fas fa-columns me-1"></i>
                  Compare {{ compareIds.length }} bidders
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-success"
                  @click="autoRecommendTop"
                >
                  <i class="fas fa-magic me-1"></i> Auto-recommend top
                </button>
                <a
                  :href="route('admin.tenders.reports.applications_csv', { encryptedId })"
                  class="btn btn-sm btn-outline-secondary"
                >
                  <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <a
                  :href="route('admin.tenders.reports.evaluation_html', { encryptedId })"
                  target="_blank"
                  class="btn btn-sm btn-outline-secondary"
                >
                  <i class="fas fa-file-pdf me-1"></i> Evaluation Report
                </a>
              </div>
            </div>

            <!-- Bidder comparison matrix -->
            <div v-if="showCompare && compareApps.length >= 2" class="border rounded p-3 mb-3 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Bidder comparison</h6>
                <div>
                  <button
                    type="button"
                    class="btn btn-sm btn-link text-muted"
                    @click="compareIds = []; showCompare = false"
                  >
                    Clear selection
                  </button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Criterion</th>
                      <th v-for="a in compareApps" :key="a.id">
                        <div class="small font-weight-semibold">{{ a.company_name }}</div>
                        <code class="text-success" style="font-size: 0.7rem">{{ a.application_no }}</code>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Compliance</td>
                      <td v-for="a in compareApps" :key="a.id">
                        <span class="badge" :class="statusPillClass(a.compliance_status)">
                          {{ a.compliance_status }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td>Technical score</td>
                      <td v-for="a in compareApps" :key="a.id">{{ scoreLabel(a.technical_score) }}</td>
                    </tr>
                    <tr>
                      <td>Financial score</td>
                      <td v-for="a in compareApps" :key="a.id">{{ scoreLabel(a.financial_score) }}</td>
                    </tr>
                    <tr>
                      <td><strong>Total</strong></td>
                      <td v-for="a in compareApps" :key="a.id">
                        <strong>{{ scoreLabel(a.total_score) }}</strong>
                      </td>
                    </tr>
                    <tr>
                      <td>Rank</td>
                      <td v-for="a in compareApps" :key="a.id">{{ a.rank ?? "—" }}</td>
                    </tr>
                    <tr>
                      <td>Bid amount (KES)</td>
                      <td v-for="a in compareApps" :key="a.id">
                        {{ a.bid_amount !== null ? Number(a.bid_amount).toLocaleString() : "—" }}
                      </td>
                    </tr>
                    <tr>
                      <td>Years exp</td>
                      <td v-for="a in compareApps" :key="a.id">{{ a.years_of_experience ?? "—" }}</td>
                    </tr>
                    <tr>
                      <td>Turnover (KES)</td>
                      <td v-for="a in compareApps" :key="a.id">
                        {{ a.annual_turnover !== null ? Number(a.annual_turnover).toLocaleString() : "—" }}
                      </td>
                    </tr>
                    <tr>
                      <td>Certifications</td>
                      <td v-for="a in compareApps" :key="a.id" class="small">
                        {{ a.certifications || "—" }}
                      </td>
                    </tr>
                    <tr>
                      <td>Status</td>
                      <td v-for="a in compareApps" :key="a.id">
                        <span class="badge" :class="statusPillClass(a.overall_status || a.status)">
                          {{ a.overall_status || a.status || "Under Review" }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Filters -->
            <div class="border rounded p-3 mb-3 bg-light">
              <div class="row small">
                <div class="col-md-3 mb-2">
                  <label class="font-weight-semibold small mb-1">Search</label>
                  <input
                    v-model="appFilters.q"
                    type="search"
                    class="form-control form-control-sm"
                    placeholder="Company · Registration # · App # · Bidder ID · Email · Phone"
                  />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Status</label>
                  <select v-model="appFilters.application_status_id" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option v-for="s in applicationStatuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                  </select>
                </div>
                <div class="col-md-3 mb-2">
                  <label class="font-weight-semibold small mb-1">Category</label>
                  <select v-model="appFilters.category_id" class="form-control form-control-sm" :disabled="!categories.length">
                    <option value="">{{ categories.length ? "All categories" : "No categories" }}</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.tender_no }} — {{ c.title }}</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Location</label>
                  <select v-model="appFilters.county_id" class="form-control form-control-sm" :disabled="!counties.length">
                    <option value="">
                      {{ counties.length ? "All counties" : "No county on bidders" }}
                    </option>
                    <option v-for="c in counties" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Date from</label>
                  <input v-model="appFilters.date_from" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Date to</label>
                  <input v-model="appFilters.date_to" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Compliance</label>
                  <select v-model="appFilters.compliance_status" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="Pass">Pass</option>
                    <option value="Fail">Fail</option>
                    <option value="Pending">Pending</option>
                    <option value="Not Configured">Not Configured</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Technical</label>
                  <select v-model="appFilters.technical_status" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="Pass">Pass</option>
                    <option value="Fail">Fail</option>
                    <option value="Pending">Pending</option>
                    <option value="Not Configured">Not Configured</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Due Diligence</label>
                  <select v-model="appFilters.due_diligence_status" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Total score ≥</label>
                  <input v-model="appFilters.min_score" type="number" min="0" max="100" step="0.01" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Rank ≤</label>
                  <input v-model="appFilters.min_rank" type="number" min="1" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Shortlisted</label>
                  <select v-model="appFilters.shortlisted" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="1">Shortlisted only</option>
                  </select>
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Recommended</label>
                  <select v-model="appFilters.recommended" class="form-control form-control-sm">
                    <option value="">Any</option>
                    <option value="1">Recommended only</option>
                  </select>
                </div>
                <div class="col-md-3 mb-2">
                  <label class="font-weight-semibold small mb-1">Certification</label>
                  <input
                    v-model="appFilters.certification"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="e.g. ISO 9001"
                  />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Years exp ≥</label>
                  <input
                    v-model="appFilters.min_years"
                    type="number"
                    min="0"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Turnover ≥</label>
                  <input
                    v-model="appFilters.min_turnover"
                    type="number"
                    min="0"
                    step="0.01"
                    class="form-control form-control-sm"
                    placeholder="KES"
                  />
                </div>
                <div class="col-md-2 mb-2">
                  <label class="font-weight-semibold small mb-1">Price ≤</label>
                  <input
                    v-model="appFilters.max_price"
                    type="number"
                    min="0"
                    step="0.01"
                    class="form-control form-control-sm"
                    placeholder="KES"
                  />
                </div>
              </div>
              <div class="mt-1 d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-link text-muted" @click="resetFilters">Clear filters</button>
              </div>
            </div>

            <div v-if="!applications.length" class="text-muted small p-3 text-center">No applications match your filters.</div>
            <div v-else class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th style="width: 30px" title="Select up to 10 bidders to compare">
                      <i class="fas fa-columns text-muted"></i>
                    </th>
                    <th>Rank</th>
                    <th>Company</th>
                    <th>Application Date</th>
                    <th class="text-center">Compliance</th>
                    <th class="text-center">Technical</th>
                    <th class="text-center">Financial</th>
                    <th class="text-center">Total</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="a in applications"
                    :key="a.id"
                    :class="{ 'table-danger': a.disqualified }"
                  >
                    <td class="text-center">
                      <input
                        type="checkbox"
                        :checked="compareIds.includes(a.id)"
                        @change="toggleCompare(a.id)"
                      />
                    </td>
                    <td class="text-muted">{{ a.rank ?? "—" }}</td>
                    <td>
                      <div class="font-weight-semibold">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.75rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                      <small
                        v-if="a.disqualified && a.disqualification_reason"
                        class="d-block text-danger"
                      >
                        <i class="fas fa-ban me-1"></i>
                        Mandatory failed: {{ a.disqualification_reason }}
                      </small>
                      <small class="d-block">
                        <code class="text-success">{{ a.application_no || `App #${a.id}` }}</code>
                        <span v-if="a.bidder_user" class="text-muted"> · Bidder #{{ a.bidder_user.id }}</span>
                      </small>
                      <small v-if="a.category" class="text-muted d-block">
                        {{ a.category.tender_no }} — {{ a.category.title }}
                      </small>
                      <small v-if="a.county" class="text-muted d-block">
                        <i class="fas fa-map-marker-alt me-1"></i>{{ a.county.name }}
                      </small>
                      <small
                        v-if="
                          a.bid_amount !== null ||
                          a.years_of_experience !== null ||
                          a.annual_turnover !== null
                        "
                        class="text-muted d-block"
                      >
                        <span v-if="a.bid_amount !== null" class="me-2">
                          <i class="fas fa-tag me-1"></i>KES {{
                            Number(a.bid_amount).toLocaleString()
                          }}
                        </span>
                        <span v-if="a.years_of_experience !== null" class="me-2">
                          <i class="far fa-clock me-1"></i>{{ a.years_of_experience }} yrs
                        </span>
                        <span v-if="a.annual_turnover !== null" class="me-2">
                          <i class="fas fa-chart-line me-1"></i>KES {{
                            Number(a.annual_turnover).toLocaleString()
                          }}/yr
                        </span>
                      </small>
                    </td>
                    <td>{{ formatDate(a.application_date) }}</td>
                    <td class="text-center">
                      <span class="badge" :class="statusPillClass(a.compliance_status)">
                        {{ a.compliance_status === "Not Configured" ? "—" : a.compliance_status }}
                      </span>
                    </td>
                    <td class="text-center">{{ scoreLabel(a.technical_score) }}</td>
                    <td class="text-center">{{ scoreLabel(a.financial_score) }}</td>
                    <td class="text-center font-weight-semibold">{{ scoreLabel(a.total_score) }}</td>
                    <td>
                      <span
                        class="badge"
                        :class="statusPillClass(a.overall_status || a.status)"
                        :title="a.disqualification_reason || ''"
                      >
                        {{ a.overall_status || a.status || "Under Review" }}
                      </span>
                    </td>
                    <td class="text-right">
                      <div class="d-flex justify-content-end" style="gap: 0.25rem">
                        <Link
                          :href="route('admin.applications.show', { encryptedAppId: a.encrypted_id })"
                          class="btn btn-sm btn-outline-success"
                        >
                          Open
                        </Link>
                        <button
                          v-if="a.status !== 'Rejected'"
                          type="button"
                          class="btn btn-sm btn-outline-danger"
                          title="Disqualify with reason"
                          @click="openDisqualify(a)"
                        >
                          <i class="fas fa-ban"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Disqualify inline form -->
            <div v-if="activeDqApp" class="border rounded p-3 mt-3 bg-light">
              <h6 class="mb-2">
                Disqualify — {{ activeDqApp.company_name }}
                <code v-if="activeDqApp.application_no" class="text-danger ms-2" style="font-size: 0.75rem">
                  {{ activeDqApp.application_no }}
                </code>
              </h6>
              <label class="small mb-1">
                Reason <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="dqForm.reason"
                rows="3"
                class="form-control form-control-sm mb-2"
                :class="{ 'is-invalid': dqForm.errors.reason }"
                placeholder="e.g. Failed compliance: missing valid Tax Compliance Certificate."
              ></textarea>
              <small v-if="dqForm.errors.reason" class="text-danger d-block mb-2">
                {{ dqForm.errors.reason }}
              </small>
              <button
                type="button"
                class="btn btn-sm btn-danger me-1"
                :disabled="dqForm.processing"
                @click="confirmDisqualify"
              >
                <i class="fas fa-ban me-1"></i> Confirm disqualification
              </button>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="activeDqApp = null"
              >
                Cancel
              </button>
              <small class="text-muted d-block mt-2">
                This sets the application status to <strong>Rejected</strong>,
                stores the reason on the application, adds a note to the
                Communications tab, and records an audit entry.
              </small>
            </div>
          </div>
        </div>

        <!-- ── SUBMITTED DOCUMENTS ─────────────────────────────────── -->
        <div v-else-if="activeSection === 'submitted'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Submitted Documents <span class="text-muted small ms-2">{{ submittedDocuments.length }} files</span></h5>
            <p class="text-muted small">Every file is auto-associated with its Tender No. and Bidder Name for traceability.</p>
            <div v-if="!submittedDocuments.length" class="text-muted small p-3 text-center">No documents have been submitted yet.</div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>File</th><th>Requirement</th><th>Application #</th><th>Tender No.</th><th>Bidder</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                  <tr v-for="d in submittedDocuments" :key="d.id">
                    <td class="text-truncate" style="max-width: 240px"><i class="fas fa-file text-success me-2"></i>{{ d.file_name }}</td>
                    <td>{{ d.requirement || "—" }}</td>
                    <td><code class="small text-success">{{ d.application_no || "—" }}</code></td>
                    <td><span class="badge badge-light text-dark">{{ d.tender_no || "—" }}</span></td>
                    <td>
                      <div class="font-weight-semibold small">{{ d.company_name || "—" }}</div>
                      <small v-if="d.bidder_name" class="text-muted">{{ d.bidder_name }}</small>
                    </td>
                    <td>{{ formatDateTime(d.uploaded_at) }}</td>
                    <td class="text-right"><a :href="`/storage/${d.filepath}`" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i></a></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── SCORING TABS (compliance / technical / financial) ─── -->
        <div
          v-else-if="['compliance', 'technical', 'financial'].includes(activeSection)"
          class="card border-0 shadow-sm"
        >
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 0.5rem">
              <h5 class="mb-0 text-capitalize">{{ activeSection }} Evaluation</h5>
              <div class="d-flex" style="gap: 0.5rem">
                <select v-model="scoringSection" class="form-control form-control-sm" style="max-width: 200px">
                  <option value="compliance">Compliance</option>
                  <option value="technical">Technical</option>
                  <option value="financial">Financial</option>
                </select>
                <button type="button" class="btn btn-sm btn-success" @click="saveScores">
                  <i class="fas fa-save me-1"></i> Save scores
                </button>
              </div>
            </div>

            <div v-if="!criteriaForSection.length" class="alert alert-warning py-2 px-3 small">
              No {{ scoringSection }} criteria defined. Add criteria in the
              <button type="button" class="btn btn-link btn-sm p-0" @click="activeSection = 'criteria'">Evaluation Criteria</button>
              tab first.
            </div>

            <div v-else-if="!applications.length" class="text-muted small p-3 text-center">
              No applications to score yet.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-sm table-bordered mb-0 scoring-grid">
                <thead class="thead-light">
                  <tr>
                    <th style="min-width: 180px">Bidder</th>
                    <th v-for="c in criteriaForSection" :key="c.id" class="text-center" style="min-width: 120px">
                      <div class="small">{{ c.title }}</div>
                      <small class="text-muted">max {{ c.max_score }} · w{{ c.weight }}</small>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in applications" :key="a.id">
                    <td>
                      <div class="font-weight-semibold small">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.7rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                      <small class="text-muted">Rank {{ a.rank ?? "—" }}</small>
                    </td>
                    <td v-for="c in criteriaForSection" :key="c.id" class="p-1">
                      <input
                        v-model.number="scoreDraft[a.id][c.id]"
                        type="number"
                        step="0.01"
                        :min="0"
                        :max="c.max_score"
                        class="form-control form-control-sm text-center"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
              <p class="text-muted small mt-2 mb-0">
                Section pass threshold: 50% (weighted average). Leave blank to
                clear a score. Save applies all changes and re-ranks bidders.
              </p>
            </div>
          </div>
        </div>

        <!-- ── DUE DILIGENCE ───────────────────────────────────────── -->
        <div v-else-if="activeSection === 'diligence'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Due Diligence</h5>
            <p class="text-muted small">Track background checks per bidder. Completing or failing DD is timestamped.</p>

            <div v-if="!applications.length" class="text-muted small p-3 text-center">No applications yet.</div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr><th>Bidder</th><th>Status</th><th>Completed</th><th>Notes</th><th></th></tr>
                </thead>
                <tbody>
                  <tr v-for="a in applications" :key="a.id">
                    <td>
                      <div class="font-weight-semibold small">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.7rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                      <small v-if="a.bidder_user" class="text-muted">{{ a.bidder_user.name }}</small>
                    </td>
                    <td>
                      <span v-if="a.due_diligence_status" class="badge" :class="{
                        'badge-secondary': a.due_diligence_status === 'pending',
                        'badge-info': a.due_diligence_status === 'in_progress',
                        'badge-success': a.due_diligence_status === 'completed',
                        'badge-danger': a.due_diligence_status === 'failed',
                      }">
                        {{ a.due_diligence_status.replace("_", " ") }}
                      </span>
                      <span v-else class="text-muted small">Not started</span>
                    </td>
                    <td>{{ formatDate(a.due_diligence_completed_at) }}</td>
                    <td class="text-truncate small text-muted" style="max-width: 260px">
                      {{ a.due_diligence_notes || "—" }}
                    </td>
                    <td class="text-right">
                      <button type="button" class="btn btn-sm btn-outline-success" @click="openDdEditor(a)">
                        <i class="fas fa-pen me-1"></i> Edit
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Inline editor -->
            <div v-if="activeDdApp" class="border rounded p-3 mt-3 bg-light">
              <h6 class="mb-2">
                Update DD — {{ activeDdApp.company_name }}
                <code v-if="activeDdApp.application_no" class="text-success ms-1" style="font-size: 0.75rem">
                  {{ activeDdApp.application_no }}
                </code>
              </h6>
              <div class="row small">
                <div class="col-md-4 mb-2">
                  <label class="small mb-1">Status</label>
                  <select v-model="ddForm.due_diligence_status" class="form-control form-control-sm">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
                <div class="col-md-8 mb-2">
                  <label class="small mb-1">Notes</label>
                  <textarea v-model="ddForm.due_diligence_notes" class="form-control form-control-sm" rows="2"></textarea>
                </div>
              </div>
              <div class="d-flex" style="gap: 0.5rem">
                <button type="button" class="btn btn-sm btn-success" :disabled="ddForm.processing" @click="saveDd">Save</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="activeDdApp = null">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── COMMUNICATIONS ──────────────────────────────────────── -->
        <div v-else-if="activeSection === 'communications'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Communications</h5>
            <p class="text-muted small">
              Send tender-scoped emails to bidders and review all sent + received
              communications. Every message is auto-linked to this tender and,
              where applicable, the specific bidder application.
            </p>

            <!-- ─ Send form ────────────────────────────────────────── -->
            <form @submit.prevent="sendCommunication" class="border rounded p-3 mb-3 bg-light">
              <h6 class="mb-2">Send communication</h6>

              <div class="row small">
                <div class="col-md-4 mb-2">
                  <label class="small mb-1">Template</label>
                  <select
                    v-model="commForm.category"
                    class="form-control form-control-sm"
                    @change="applyTemplate(commForm.category)"
                  >
                    <option
                      v-for="t in communicationTemplates"
                      :key="t.key"
                      :value="t.key"
                    >
                      {{ t.label }}
                    </option>
                  </select>
                </div>

                <div class="col-md-4 mb-2">
                  <label class="small mb-1">Recipients</label>
                  <select v-model="commForm.recipients" class="form-control form-control-sm">
                    <option value="all">
                      All bidders ({{ recipientGroupCounts.all ?? 0 }})
                    </option>
                    <option value="shortlisted">
                      Shortlisted only ({{ recipientGroupCounts.shortlisted ?? 0 }})
                    </option>
                    <option value="recommended">
                      Recommended only ({{ recipientGroupCounts.recommended ?? 0 }})
                    </option>
                    <option value="awarded">
                      Awarded bidder ({{ recipientGroupCounts.awarded ?? 0 }})
                    </option>
                    <option value="specific">Specific bidder</option>
                  </select>
                </div>

                <div v-if="commForm.recipients === 'specific'" class="col-md-4 mb-2">
                  <label class="small mb-1">Bidder</label>
                  <select v-model="commForm.application_id" class="form-control form-control-sm">
                    <option value="">Select a bidder…</option>
                    <option v-for="a in applications" :key="a.id" :value="a.id">
                      {{ a.application_no ? a.application_no + " — " : "" }}{{ a.company_name }}
                    </option>
                  </select>
                </div>

                <div class="col-12 mb-2">
                  <label class="small mb-1">Subject <span class="text-danger">*</span></label>
                  <input
                    v-model="commForm.subject"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': commForm.errors.subject }"
                    required
                  />
                  <small
                    v-if="commForm.errors.subject"
                    class="text-danger"
                    >{{ commForm.errors.subject }}</small
                  >
                </div>

                <div class="col-12 mb-2">
                  <label class="small mb-1">Message <span class="text-danger">*</span></label>
                  <textarea
                    v-model="commForm.body"
                    rows="8"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': commForm.errors.body }"
                    required
                  ></textarea>
                  <small class="text-muted d-block mt-1">
                    Placeholders you can use:
                    <code v-pre>{{tender_title}}</code>,
                    <code v-pre>{{tender_no}}</code>,
                    <code v-pre>{{institution}}</code>,
                    <code v-pre>{{closing_date}}</code>,
                    <code v-pre>{{application_no}}</code>,
                    <code v-pre>{{company_name}}</code>.
                  </small>
                  <small
                    v-if="commForm.errors.body"
                    class="text-danger d-block"
                    >{{ commForm.errors.body }}</small
                  >
                </div>
              </div>

              <div class="d-flex align-items-center" style="gap: 0.5rem">
                <button
                  type="submit"
                  class="btn btn-sm btn-success"
                  :disabled="commForm.processing || !recipientCount"
                >
                  <i class="fas fa-paper-plane me-1"></i>
                  Send to {{ recipientCount }} recipient<span v-if="recipientCount !== 1">s</span>
                </button>
                <small
                  v-if="commForm.errors.recipients"
                  class="text-danger"
                  >{{ commForm.errors.recipients }}</small
                >
              </div>
            </form>

            <!-- ─ Sent communications history ─────────────────────── -->
            <h6 class="mb-2">
              Sent
              <span class="text-muted small ms-2">
                {{ sentCommunications.length }} sent from this workspace
              </span>
            </h6>
            <div
              v-if="!sentCommunications.length"
              class="text-muted small p-3 text-center border rounded mb-3"
            >
              No tender-level communications sent yet.
            </div>
            <ul v-else class="list-unstyled mb-3">
              <li
                v-for="c in sentCommunications"
                :key="c.id"
                class="border rounded p-3 mb-2"
              >
                <div class="d-flex justify-content-between align-items-start small mb-1 flex-wrap" style="gap: 0.5rem">
                  <span>
                    <span class="badge badge-info me-2">{{ c.category_label }}</span>
                    <strong>{{ c.subject }}</strong>
                  </span>
                  <span class="text-muted">{{ formatDateTime(c.sent_at || c.created_at) }}</span>
                </div>
                <div class="small text-muted mb-2">
                  <i class="far fa-envelope me-1"></i>
                  <strong>{{ c.recipient_name || "—" }}</strong>
                  &lt;{{ c.recipient_email }}&gt;
                  <code v-if="c.application_no" class="text-success ms-2">{{ c.application_no }}</code>
                  <span v-if="c.sent_by" class="ms-2">by {{ c.sent_by }}</span>
                  <span
                    v-if="c.error"
                    class="badge badge-danger ms-2"
                    :title="c.error"
                  >
                    delivery failed
                  </span>
                </div>
                <details>
                  <summary class="small text-muted" style="cursor: pointer">
                    View message
                  </summary>
                  <pre class="mt-2 small" style="white-space: pre-wrap; background:#f9fafb; padding:8px; border-radius:4px">{{ c.body }}</pre>
                </details>
              </li>
            </ul>

            <!-- ─ Application-level notes ─────────────────────────── -->
            <h6 class="mb-2">
              Notes on applications
              <span class="text-muted small ms-2">{{ communications.length }} notes</span>
            </h6>
            <div v-if="!communications.length" class="text-muted small p-3 text-center border rounded">
              No application notes yet.
            </div>
            <ul v-else class="list-unstyled mb-0">
              <li v-for="n in communications" :key="n.id" class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span>
                    <strong>{{ n.company_name || "—" }}</strong>
                    <code v-if="n.application_no" class="text-success ms-2">{{ n.application_no }}</code>
                    <span v-if="n.author" class="ms-2">by {{ n.author }}</span>
                  </span>
                  <span>{{ formatDateTime(n.created_at) }}</span>
                </div>
                <div>{{ n.note }}</div>
              </li>
            </ul>
          </div>
        </div>

        <!-- ── CLARIFICATIONS ──────────────────────────────────────── -->
        <div v-else-if="activeSection === 'clarifications'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Clarifications</h5>

            <form @submit.prevent="askClarification" class="border rounded p-3 mb-3 bg-light">
              <div class="row small">
                <div class="col-md-4 mb-2">
                  <label class="small mb-1">Bidder (optional)</label>
                  <select v-model="clarForm.application_id" class="form-control form-control-sm">
                    <option value="">Broadcast / general</option>
                    <option v-for="a in applications" :key="a.id" :value="a.id">
                      {{ a.application_no ? a.application_no + " — " : "" }}{{ a.company_name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-8 mb-2">
                  <label class="small mb-1">Question / clarification <span class="text-danger">*</span></label>
                  <textarea v-model="clarForm.question" class="form-control form-control-sm" rows="2" required></textarea>
                </div>
              </div>
              <button type="submit" class="btn btn-sm btn-success" :disabled="clarForm.processing">
                Record clarification
              </button>
            </form>

            <div v-if="!clarifications.length" class="text-muted small p-3 text-center">
              No clarifications recorded yet.
            </div>

            <ul v-else class="list-unstyled mb-0">
              <li v-for="c in clarifications" :key="c.id" class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span>
                    <strong>{{ c.company_name || "General" }}</strong>
                    <code v-if="c.application_no" class="text-success ms-2">{{ c.application_no }}</code>
                    <span v-if="c.asked_by" class="ms-2">by {{ c.asked_by }}</span>
                  </span>
                  <span>{{ formatDateTime(c.created_at) }}</span>
                </div>
                <div class="mb-2"><strong>Q:</strong> {{ c.question }}</div>
                <div v-if="c.answer" class="border-top pt-2 small">
                  <strong>A:</strong> {{ c.answer }}
                  <div class="text-muted mt-1">
                    Answered by {{ c.answered_by || "—" }} · {{ formatDateTime(c.answered_at) }}
                  </div>
                </div>
                <div v-else>
                  <div v-if="activeAnswerId === c.id">
                    <textarea v-model="answerText" class="form-control form-control-sm mb-2" rows="2"></textarea>
                    <button type="button" class="btn btn-sm btn-success me-1" @click="submitAnswer">Save answer</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="activeAnswerId = null">Cancel</button>
                  </div>
                  <button v-else type="button" class="btn btn-sm btn-outline-success" @click="startAnswer(c)">
                    <i class="fas fa-reply me-1"></i> Answer
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- ── SHORTLIST ───────────────────────────────────────────── -->
        <div v-else-if="activeSection === 'shortlist'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Shortlisted Bidders <span class="text-muted small ms-2">{{ shortlistedApps.length }} shortlisted</span></h5>
            <p class="text-muted small">Toggle bidders in and out of the shortlist. Shortlisted status is reflected in the applicant's status column.</p>

            <div v-if="!applications.length" class="text-muted small p-3 text-center">No applications yet.</div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th></th><th>Bidder</th><th>Rank</th><th class="text-center">Total</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in applications" :key="a.id" :class="{ 'table-success': a.shortlisted }">
                    <td class="text-center">
                      <input type="checkbox" :checked="a.shortlisted" @change="toggleShortlist(a)" />
                    </td>
                    <td>
                      <div class="font-weight-semibold small">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.7rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                      <small v-if="a.category" class="text-muted d-block">{{ a.category.tender_no }} — {{ a.category.title }}</small>
                    </td>
                    <td>{{ a.rank ?? "—" }}</td>
                    <td class="text-center font-weight-semibold">{{ scoreLabel(a.total_score) }}</td>
                    <td>
                      <span class="badge" :class="statusPillClass(a.status)">{{ a.status || "Under Review" }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── FINAL RANKING ───────────────────────────────────────── -->
        <div v-else-if="activeSection === 'ranking'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Final Ranking</h5>
            <p class="text-muted small">
              Ranked list of bidders based on total weighted score across
              Compliance / Technical / Financial sections. Bidders without a
              complete score do not receive a rank.
            </p>

            <div v-if="!rankedApplications.length" class="text-muted small p-3 text-center">
              No fully-scored bidders yet.
            </div>

            <div v-else class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Rank</th><th>Bidder</th>
                    <th class="text-center">Compliance</th>
                    <th class="text-center">Technical</th>
                    <th class="text-center">Financial</th>
                    <th class="text-center">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in rankedApplications" :key="a.id" :class="{ 'table-warning': a.rank === 1 }">
                    <td><span class="badge" :class="a.rank === 1 ? 'badge-warning' : 'badge-light text-dark'">#{{ a.rank }}</span></td>
                    <td>
                      <div class="font-weight-semibold">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.75rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                    </td>
                    <td class="text-center">{{ scoreLabel(a.compliance_score) }}</td>
                    <td class="text-center">{{ scoreLabel(a.technical_score) }}</td>
                    <td class="text-center">{{ scoreLabel(a.financial_score) }}</td>
                    <td class="text-center font-weight-semibold">{{ scoreLabel(a.total_score) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── RECOMMENDATION ──────────────────────────────────────── -->
        <div v-else-if="activeSection === 'recommendation'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Recommendation</h5>
            <p class="text-muted small">Mark bidders as recommended and record supporting notes. Multiple bidders can be recommended.</p>

            <div v-if="!applications.length" class="text-muted small p-3 text-center">No applications yet.</div>
            <div v-else class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr><th>Bidder</th><th>Rank</th><th class="text-center">Total</th><th>Recommended?</th><th>Note</th><th></th></tr>
                </thead>
                <tbody>
                  <tr v-for="a in applications" :key="a.id" :class="{ 'table-success': a.recommended }">
                    <td>
                      <div class="font-weight-semibold small">
                        {{ a.company_name }}
                        <code v-if="a.application_no" class="text-success ms-1" style="font-size: 0.7rem">
                          {{ a.application_no }}
                        </code>
                      </div>
                    </td>
                    <td>{{ a.rank ?? "—" }}</td>
                    <td class="text-center">{{ scoreLabel(a.total_score) }}</td>
                    <td>
                      <span v-if="a.recommended" class="badge badge-success">Recommended</span>
                      <span v-else class="text-muted small">—</span>
                    </td>
                    <td class="text-truncate small text-muted" style="max-width: 260px">{{ a.recommendation_note || "—" }}</td>
                    <td class="text-right">
                      <button v-if="!a.recommended" type="button" class="btn btn-sm btn-outline-success" @click="openRecEditor(a, true)">
                        <i class="fas fa-thumbs-up me-1"></i> Recommend
                      </button>
                      <button v-else type="button" class="btn btn-sm btn-outline-danger" @click="openRecEditor(a, false)">
                        Un-recommend
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="activeRecApp" class="border rounded p-3 mt-3 bg-light">
              <h6 class="mb-2">
                {{ recForm.recommended ? "Recommend" : "Remove recommendation for" }}
                — {{ activeRecApp.company_name }}
                <code v-if="activeRecApp.application_no" class="text-success ms-1" style="font-size: 0.75rem">
                  {{ activeRecApp.application_no }}
                </code>
              </h6>
              <label class="small mb-1">Supporting note</label>
              <textarea v-model="recForm.recommendation_note" class="form-control form-control-sm mb-2" rows="2"></textarea>
              <button type="button" class="btn btn-sm btn-success me-1" :disabled="recForm.processing" @click="saveRecommendation">Save</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" @click="activeRecApp = null">Cancel</button>
            </div>
          </div>
        </div>

        <!-- ── AWARD ───────────────────────────────────────────────── -->
        <div v-else-if="activeSection === 'award'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Award Information</h5>

            <div v-if="award" class="alert alert-success p-3 mb-3">
              <h6 class="mb-1"><i class="fas fa-trophy me-1"></i> Awarded</h6>
              <div>
                <strong>{{ award.company_name }}</strong>
                <code v-if="award.application_no" class="text-success ms-2">{{ award.application_no }}</code>
              </div>
              <div class="small">
                <span v-if="award.reference_no" class="mr-2">Ref: {{ award.reference_no }}</span>
                <span v-if="award.contract_value !== null" class="mr-2">Value: {{ award.contract_value }}</span>
                <span v-if="award.awarded_at" class="mr-2">Awarded {{ formatDate(award.awarded_at) }}</span>
                <span v-if="award.awarded_by">by {{ award.awarded_by }}</span>
              </div>
              <p v-if="award.notes" class="mt-2 mb-0 small">{{ award.notes }}</p>
            </div>

            <form @submit.prevent="saveAward" class="border rounded p-3 bg-light">
              <h6 class="mb-2">{{ award ? "Update award" : "Record award" }}</h6>
              <div class="row small">
                <div class="col-md-6 mb-2">
                  <label class="small mb-1">Winner <span class="text-danger">*</span></label>
                  <select v-model="awardForm.application_id" class="form-control form-control-sm" required>
                    <option value="">Select bidder…</option>
                    <option v-for="a in applications" :key="a.id" :value="a.id">
                      {{ a.application_no ? a.application_no + " — " : "" }}{{ a.company_name }}
                      <template v-if="a.rank">— Rank #{{ a.rank }}</template>
                    </option>
                  </select>
                </div>
                <div class="col-md-3 mb-2">
                  <label class="small mb-1">Contract value</label>
                  <input v-model="awardForm.contract_value" type="number" step="0.01" min="0" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3 mb-2">
                  <label class="small mb-1">Reference #</label>
                  <input v-model="awardForm.reference_no" type="text" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3 mb-2">
                  <label class="small mb-1">Awarded on</label>
                  <input v-model="awardForm.awarded_at" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-9 mb-2">
                  <label class="small mb-1">Notes</label>
                  <input v-model="awardForm.notes" type="text" class="form-control form-control-sm" />
                </div>
              </div>
              <button type="submit" class="btn btn-sm btn-success me-1" :disabled="awardForm.processing">
                {{ award ? "Update award" : "Record award" }}
              </button>
              <button v-if="award" type="button" class="btn btn-sm btn-outline-danger" @click="removeAward">
                Remove award
              </button>
            </form>
          </div>
        </div>

        <!-- ── AUDIT TRAIL ─────────────────────────────────────────── -->
        <div v-else-if="activeSection === 'audit'" class="card border-0 shadow-sm">
          <div class="card-body">
            <h5 class="mb-3">Audit Trail <span class="text-muted small ms-2">Latest 200 events</span></h5>
            <div v-if="!audit.length" class="text-muted small p-3 text-center">No workspace actions recorded yet.</div>
            <ul v-else class="list-unstyled mb-0">
              <li v-for="e in audit" :key="e.id" class="d-flex align-items-start py-2 border-bottom">
                <div class="me-3 text-muted small" style="min-width: 140px">
                  {{ formatDateTime(e.created_at) }}
                </div>
                <div class="flex-grow-1">
                  <div>{{ e.description }}</div>
                  <small class="text-muted">
                    <span class="badge badge-light text-muted mr-1">{{ e.action }}</span>
                    <span v-if="e.author">by {{ e.author }}</span>
                  </small>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.workspace-hero {
  background: linear-gradient(90deg, #ecfdf5 0%, #f0fdfa 100%);
}
.workspace-stats .stat-card { border-left: 3px solid transparent; }
.stat-card.tone-primary { border-left-color: #0d6efd; }
.stat-card.tone-info    { border-left-color: #0dcaf0; }
.stat-card.tone-success { border-left-color: #16a34a; }
.stat-card.tone-danger  { border-left-color: #dc2626; }
.stat-card.tone-warning { border-left-color: #d97706; }
.stat-card.tone-secondary { border-left-color: #6b7280; }
.stat-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; margin-bottom: 4px; }
.stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1; color: #111827; }

.workspace-nav .list-group-item { border: 0; border-radius: 0; padding: 0.6rem 0.9rem; font-size: 0.875rem; cursor: pointer; }
.workspace-nav .list-group-item.active { background: #ecfdf5; color: #065f46; font-weight: 600; }
.workspace-nav .list-group-item:hover:not(.active) { background: #f9fafb; }

.scoring-grid td, .scoring-grid th { vertical-align: middle; }
.scoring-grid input[type="number"] { max-width: 90px; margin: 0 auto; }

.content-html :deep(p:last-child) { margin-bottom: 0; }
</style>
