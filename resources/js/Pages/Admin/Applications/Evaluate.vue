<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  tender: { type: Object, required: true },
  applications: { type: Array, required: true },
  stats: { type: Object, required: true },
  applicationStatuses: { type: Array, default: () => [] },
  processStatuses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

// ─── Process status ───────────────────────────────────────────────
const updatingProcessStatus = ref(false);

function changeProcessStatus(statusId) {
  if (updatingProcessStatus.value) return;
  updatingProcessStatus.value = true;
  router.patch(
    route("admin.tenders.process-status", {
      encryptedId: props.tender.encrypted_id,
    }),
    { application_process_status_id: statusId || null },
    {
      preserveScroll: true,
      onFinish: () => {
        updatingProcessStatus.value = false;
      },
    }
  );
}

// ─── Filter ───────────────────────────────────────────────────────
const filterStatus = ref(props.filters.status_id ?? "");
const filterSearch = ref(props.filters.q ?? "");
let filterTimer = null;

function applyFilters() {
  router.get(
    route("admin.tenders.evaluate", { encryptedId: props.tender.encrypted_id }),
    {
      q: filterSearch.value || undefined,
      status_id: filterStatus.value || undefined,
    },
    { preserveState: true, replace: true }
  );
}

function onSearchInput() {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(applyFilters, 350);
}

// ─── Inline status update ─────────────────────────────────────────
const pendingStatusUpdate = ref({});

function updateAppStatus(app, statusId) {
  pendingStatusUpdate.value[app.encrypted_id] = true;
  router.patch(
    route("admin.applications.update-status", {
      encryptedAppId: app.encrypted_id,
    }),
    { application_status_id: statusId || null },
    {
      preserveScroll: true,
      onFinish: () => {
        delete pendingStatusUpdate.value[app.encrypted_id];
      },
    }
  );
}

// ─── Inline rating ────────────────────────────────────────────────
const ratingDraft = ref({}); // { [appId]: { rating } }
const savingRating = ref({});

function initRating(app) {
  if (!ratingDraft.value[app.encrypted_id]) {
    ratingDraft.value[app.encrypted_id] = {
      rating: app.rating ?? "",
    };
  }
}

function saveRating(app) {
  savingRating.value[app.encrypted_id] = true;
  router.patch(
    route("admin.applications.update-rating", {
      encryptedAppId: app.encrypted_id,
    }),
    ratingDraft.value[app.encrypted_id],
    {
      preserveScroll: true,
      onFinish: () => {
        savingRating.value[app.encrypted_id] = false;
      },
    }
  );
}

// ─── CRM Notes ───────────────────────────────────────────────────
const newNote = ref({}); // { [appId]: string }
const savingNote = ref({});

function addNote(app) {
  const text = (newNote.value[app.encrypted_id] ?? "").trim();
  if (!text) return;
  savingNote.value[app.encrypted_id] = true;
  router.post(
    route("admin.applications.add-note", { encryptedAppId: app.encrypted_id }),
    { note: text },
    {
      preserveScroll: true,
      only: ["applications"],
      onSuccess: () => {
        newNote.value[app.encrypted_id] = "";
      },
      onFinish: () => {
        savingNote.value[app.encrypted_id] = false;
      },
    }
  );
}

function formatNoteDate(d) {
  if (!d) return "";
  return new Date(d).toLocaleString("en-KE", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// ─── Expand notes panel ───────────────────────────────────────────
const expandedApp = ref(null);

function toggleExpand(app) {
  if (expandedApp.value === app.encrypted_id) {
    expandedApp.value = null;
  } else {
    expandedApp.value = app.encrypted_id;
    initRating(app);
  }
}

// ─── Helpers ──────────────────────────────────────────────────────
const processStatusMap = computed(() => {
  const m = {};
  props.processStatuses.forEach((s) => {
    m[s.id] = s;
  });
  return m;
});

const appStatusMap = computed(() => {
  const m = {};
  props.applicationStatuses.forEach((s) => {
    m[s.id] = s;
  });
  return m;
});

function getStatusColor(status) {
  if (!status) return "#6c757d";
  return status.color || "#6c757d";
}

function getStatusName(status) {
  return status ? status.name : "Pending";
}

function complianceBg(percent) {
  if (percent >= 100) return "#198754";
  if (percent >= 60) return "#fd7e14";
  return "#dc3545";
}

function statusBadgeStyle(status) {
  const color = getStatusColor(status);
  return { backgroundColor: color, color: "#fff", borderColor: color };
}

const processStatusCurrent = computed(() =>
  props.tender.application_process_status
    ? props.tender.application_process_status
    : null
);

// ─── Application detail modal ─────────────────────────────────────
const modalAppId = ref(null); // tracks by encrypted_id

// always reads the live object from props so partial-reloads are reflected
const modalApp = computed(() =>
  modalAppId.value
    ? props.applications.find((a) => a.encrypted_id === modalAppId.value) ??
      null
    : null
);

function openModal(app) {
  modalAppId.value = app.encrypted_id;
  initRating(app);
  document.body.style.overflow = "hidden";
}

function closeModal() {
  modalAppId.value = null;
  document.body.style.overflow = "";
}

function fileUrl(p) {
  return p ? `/storage/${p}` : "#";
}

function modalCompliancePct(app) {
  if (!app.compliance) return 100;
  return app.compliance.percent;
}
</script>

<template>
  <DashboardLayout>
    <Head :title="`Evaluate: ${tender.title}`" />

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div
      class="card border-0 shadow-sm mb-3"
      style="background: var(--brand-primary)"
    >
      <div class="card-body">
        <div
          class="d-flex flex-wrap align-items-start justify-content-between"
          style="gap: 1rem"
        >
          <div>
            <div class="d-flex align-items-center mb-1" style="gap: 0.5rem">
              <Link
                :href="route('tenders.index')"
                class="text-white-50 small"
                style="text-decoration: none"
              >
                <i class="fas fa-arrow-left mr-1"></i> Tenders
              </Link>
              <span class="text-white-50">/</span>
              <span class="text-white-50 small">Evaluation</span>
            </div>
            <h4 class="text-white mb-1 font-weight-bold">{{ tender.title }}</h4>
            <div
              class="d-flex flex-wrap align-items-center"
              style="gap: 0.5rem"
            >
              <span class="badge badge-light text-dark">{{
                tender.tender_no
              }}</span>
              <span class="text-white-50 small">{{
                tender.institution?.institution_name
              }}</span>
            </div>
          </div>

          <!-- Process status stepper -->
          <div class="d-flex align-items-center flex-wrap" style="gap: 0.4rem">
            <span class="text-white-50 small mr-1">Process:</span>
            <button
              v-for="ps in processStatuses"
              :key="ps.id"
              class="btn btn-sm"
              :style="{
                backgroundColor:
                  processStatusCurrent?.id === ps.id
                    ? ps.color
                    : 'rgba(255,255,255,0.15)',
                color: '#fff',
                border:
                  processStatusCurrent?.id === ps.id
                    ? `2px solid ${ps.color}`
                    : '2px solid rgba(255,255,255,0.3)',
                fontWeight: processStatusCurrent?.id === ps.id ? '700' : '400',
                opacity: updatingProcessStatus ? 0.6 : 1,
              }"
              :disabled="updatingProcessStatus"
              @click="changeProcessStatus(ps.id)"
            >
              {{ ps.name }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Stats row ──────────────────────────────────────────── -->
    <div class="row mb-3">
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0 text-dark">
            {{ stats.total }}
          </div>
          <div class="small text-muted">Total</div>
        </div>
      </div>
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0 text-secondary">
            {{ stats.pending }}
          </div>
          <div class="small text-muted">Pending</div>
        </div>
      </div>
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0 text-primary">
            {{ stats.shortlisted }}
          </div>
          <div class="small text-muted">Shortlisted</div>
        </div>
      </div>
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0 text-danger">
            {{ stats.rejected }}
          </div>
          <div class="small text-muted">Rejected</div>
        </div>
      </div>
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0 text-success">
            {{ stats.confirmed }}
          </div>
          <div class="small text-muted">Confirmed</div>
        </div>
      </div>
      <div class="col-6 col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center p-3">
          <div class="h4 font-weight-bold mb-0" style="color: #198754">
            {{ stats.complete }}
          </div>
          <div class="small text-muted">Complete Docs</div>
        </div>
      </div>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-3">
      <div
        class="card-body py-2 d-flex flex-wrap align-items-center"
        style="gap: 0.75rem"
      >
        <div class="input-group input-group-sm" style="max-width: 240px">
          <div class="input-group-prepend">
            <span class="input-group-text bg-white"
              ><i class="fas fa-search text-muted"></i
            ></span>
          </div>
          <input
            v-model="filterSearch"
            type="search"
            class="form-control"
            placeholder="Search company, rep, email…"
            @input="onSearchInput"
          />
        </div>
        <div class="d-flex align-items-center" style="gap: 0.4rem">
          <span class="small text-muted">Status:</span>
          <button
            class="btn btn-sm"
            :class="filterStatus === '' ? 'btn-dark' : 'btn-outline-secondary'"
            @click="
              filterStatus = '';
              applyFilters();
            "
          >
            All
          </button>
          <button
            v-for="s in applicationStatuses"
            :key="s.id"
            class="btn btn-sm"
            :style="{
              backgroundColor:
                String(filterStatus) === String(s.id) ? s.color : 'transparent',
              color: String(filterStatus) === String(s.id) ? '#fff' : s.color,
              border: `1px solid ${s.color}`,
            }"
            @click="
              filterStatus = String(s.id);
              applyFilters();
            "
          >
            {{ s.name }}
          </button>
        </div>
        <div class="ml-auto d-flex" style="gap: 0.5rem">
          <Link
            :href="
              route('admin.tenders.applications.index', {
                encryptedId: tender.encrypted_id,
              })
            "
            class="btn btn-sm btn-outline-secondary"
          >
            <i class="fas fa-list mr-1"></i> List View
          </Link>
        </div>
      </div>
    </div>

    <!-- ── Applications table ─────────────────────────────────── -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div
          v-if="applications.length === 0"
          class="p-5 text-center text-muted"
        >
          <i class="fas fa-inbox fa-2x d-block mb-2"></i>
          No applications match the current filter.
        </div>
        <div v-else>
          <div v-for="app in applications" :key="app.id" class="border-bottom">
            <!-- Main row -->
            <div
              class="d-flex flex-wrap align-items-center p-3"
              style="gap: 0.75rem; cursor: pointer"
              @click="toggleExpand(app)"
            >
              <!-- Expand chevron -->
              <div style="width: 20px; flex-shrink: 0; color: #aaa">
                <i
                  :class="
                    expandedApp === app.encrypted_id
                      ? 'fas fa-chevron-down'
                      : 'fas fa-chevron-right'
                  "
                ></i>
              </div>

              <!-- Company + rep -->
              <div style="flex: 1; min-width: 160px">
                <div class="font-weight-bold">{{ app.company_name }}</div>
                <div class="small text-muted">
                  {{ app.representative_name }} ·
                  {{ app.email || app.telephone }}
                </div>
              </div>

              <!-- Compliance bar -->
              <div style="min-width: 130px">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted">Docs</span>
                  <span
                    :style="{
                      color: complianceBg(app.compliance.percent),
                      fontWeight: '600',
                    }"
                  >
                    {{ app.compliance.mandatory_satisfied }}/{{
                      app.compliance.mandatory_total
                    }}
                    mandatory
                  </span>
                </div>
                <div class="progress" style="height: 6px; border-radius: 4px">
                  <div
                    class="progress-bar"
                    :style="{
                      width: app.compliance.percent + '%',
                      backgroundColor: complianceBg(app.compliance.percent),
                    }"
                  ></div>
                </div>
                <div
                  v-if="!app.compliance.is_complete"
                  class="small text-danger mt-1"
                >
                  <i class="fas fa-exclamation-triangle mr-1"></i>Incomplete
                </div>
                <div v-else class="small text-success mt-1">
                  <i class="fas fa-check-circle mr-1"></i>Complete
                </div>
              </div>

              <!-- Rating badge -->
              <div style="min-width: 70px; text-align: center">
                <div
                  v-if="app.rating !== null && app.rating !== ''"
                  class="font-weight-bold"
                  style="font-size: 1.1rem"
                >
                  {{ parseFloat(app.rating).toFixed(1) }}
                  <span class="small text-muted">/100</span>
                </div>
                <div v-else class="small text-muted">—</div>
                <div class="small text-muted">Score</div>
              </div>

              <!-- Status dropdown -->
              <div style="min-width: 140px" @click.stop>
                <select
                  class="form-control form-control-sm"
                  :disabled="pendingStatusUpdate[app.encrypted_id]"
                  :value="app.application_status_id ?? ''"
                  :style="{
                    color: getStatusColor(app.applicationStatus),
                    fontWeight: '600',
                    borderColor: getStatusColor(app.applicationStatus),
                  }"
                  @change="updateAppStatus(app, $event.target.value || null)"
                >
                  <option value="">Pending</option>
                  <option
                    v-for="s in applicationStatuses"
                    :key="s.id"
                    :value="s.id"
                  >
                    {{ s.name }}
                  </option>
                </select>
              </div>

              <!-- Action buttons -->
              <div class="d-flex" style="gap: 0.4rem" @click.stop>
                <button
                  class="btn btn-sm btn-outline-info"
                  title="View full application"
                  @click="openModal(app)"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <!-- Quick shortlist -->
                <button
                  v-if="getStatusName(app.applicationStatus) !== 'Shortlisted'"
                  class="btn btn-sm btn-outline-primary"
                  title="Shortlist"
                  :disabled="pendingStatusUpdate[app.encrypted_id]"
                  @click="
                    updateAppStatus(
                      app,
                      applicationStatuses.find((s) => s.name === 'Shortlisted')
                        ?.id
                    )
                  "
                >
                  <i class="fas fa-star"></i>
                </button>
                <!-- Quick reject -->
                <button
                  v-if="getStatusName(app.applicationStatus) !== 'Rejected'"
                  class="btn btn-sm btn-outline-danger"
                  title="Reject"
                  :disabled="pendingStatusUpdate[app.encrypted_id]"
                  @click="
                    updateAppStatus(
                      app,
                      applicationStatuses.find((s) => s.name === 'Rejected')?.id
                    )
                  "
                >
                  <i class="fas fa-times"></i>
                </button>
                <!-- Quick confirm -->
                <button
                  v-if="getStatusName(app.applicationStatus) !== 'Confirmed'"
                  class="btn btn-sm btn-outline-success"
                  title="Confirm / Award"
                  :disabled="pendingStatusUpdate[app.encrypted_id]"
                  @click="
                    updateAppStatus(
                      app,
                      applicationStatuses.find((s) => s.name === 'Confirmed')
                        ?.id
                    )
                  "
                >
                  <i class="fas fa-check"></i>
                </button>
              </div>
            </div>

            <!-- Expanded evaluation panel -->
            <div
              v-if="expandedApp === app.encrypted_id"
              class="px-4 pb-4 pt-2"
              style="background: #f8f9fa; border-top: 1px solid #e9ecef"
            >
              <div class="row">
                <!-- Left: missing docs -->
                <div class="col-md-5 mb-3">
                  <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-file-alt mr-1 text-muted"></i>
                      Document Compliance
                    </h6>
                    <div class="d-flex mb-2" style="gap: 1rem">
                      <div class="text-center">
                        <div class="font-weight-bold">
                          {{ app.compliance.docs_submitted }}
                        </div>
                        <div class="small text-muted">Uploaded</div>
                      </div>
                      <div class="text-center">
                        <div class="font-weight-bold">
                          {{ app.compliance.mandatory_satisfied }}/{{
                            app.compliance.mandatory_total
                          }}
                        </div>
                        <div class="small text-muted">Mandatory met</div>
                      </div>
                      <div class="text-center">
                        <strong
                          :style="{
                            color: complianceBg(app.compliance.percent),
                          }"
                          >{{ app.compliance.percent }}%</strong
                        >
                        <div class="small text-muted">Complete</div>
                      </div>
                    </div>
                    <div
                      v-if="app.compliance.missing_req_ids.length"
                      class="mt-2"
                    >
                      <div class="small text-danger font-weight-bold mb-1">
                        Missing mandatory documents:
                      </div>
                      <div
                        v-for="reqId in app.compliance.missing_req_ids"
                        :key="reqId"
                        class="small text-danger"
                      >
                        <i class="fas fa-times-circle mr-1"></i>
                        Requirement #{{ reqId }}
                      </div>
                    </div>
                    <div v-else class="small text-success mt-2">
                      <i class="fas fa-check-circle mr-1"></i> All mandatory
                      documents submitted.
                    </div>
                  </div>
                </div>

                <!-- Right: score + notes -->
                <div class="col-md-7 mb-3">
                  <div class="card border-0 shadow-sm p-3 h-100">
                    <!-- Score row -->
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-star mr-1 text-muted"></i> Score
                    </h6>
                    <div
                      v-if="ratingDraft[app.encrypted_id]"
                      class="d-flex align-items-center mb-3"
                      style="gap: 0.5rem"
                    >
                      <input
                        v-model="ratingDraft[app.encrypted_id].rating"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        class="form-control form-control-sm"
                        placeholder="0–100"
                        style="max-width: 110px"
                      />
                      <span class="small text-muted">/ 100</span>
                      <button
                        class="btn btn-sm btn-outline-success"
                        :disabled="savingRating[app.encrypted_id]"
                        @click="saveRating(app)"
                      >
                        <i class="fas fa-save mr-1"></i>
                        {{
                          savingRating[app.encrypted_id]
                            ? "Saving…"
                            : "Save Score"
                        }}
                      </button>
                    </div>

                    <!-- CRM Notes -->
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-comments mr-1 text-muted"></i> Notes
                      History
                    </h6>

                    <!-- Existing notes -->
                    <div
                      v-if="app.notes && app.notes.length"
                      class="mb-2"
                      style="max-height: 160px; overflow-y: auto"
                    >
                      <div
                        v-for="n in app.notes"
                        :key="n.id"
                        class="p-2 mb-1 rounded"
                        style="background: #fff; border: 1px solid #e9ecef"
                      >
                        <div
                          class="d-flex justify-content-between align-items-start"
                        >
                          <span class="small font-weight-bold text-dark">{{
                            n.user?.name ?? "Admin"
                          }}</span>
                          <span class="small text-muted">{{
                            formatNoteDate(n.created_at)
                          }}</span>
                        </div>
                        <p
                          class="mb-0 small mt-1"
                          style="white-space: pre-line"
                        >
                          {{ n.note }}
                        </p>
                      </div>
                    </div>
                    <p v-else class="small text-muted mb-2">No notes yet.</p>

                    <!-- New note input -->
                    <textarea
                      v-model="newNote[app.encrypted_id]"
                      class="form-control form-control-sm mb-2"
                      rows="2"
                      placeholder="Add a note…"
                    ></textarea>
                    <button
                      class="btn btn-sm btn-primary"
                      :disabled="
                        savingNote[app.encrypted_id] ||
                        !(newNote[app.encrypted_id] ?? '').trim()
                      "
                      @click="addNote(app)"
                    >
                      <i class="fas fa-plus mr-1"></i>
                      {{
                        savingNote[app.encrypted_id] ? "Saving…" : "Add Note"
                      }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>

  <!-- ── Application Detail Modal ───────────────────────────────── -->
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="modalApp"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.55); overflow-y: auto"
        @mousedown.self="closeModal"
      >
        <div
          class="modal-dialog modal-xl modal-dialog-scrollable"
          style="max-width: 1100px; margin: 2rem auto"
        >
          <div class="modal-content border-0 shadow">
            <!-- Modal header -->
            <div class="modal-header" style="background: #1a3c5e">
              <div>
                <h5 class="modal-title text-white mb-0 font-weight-bold">
                  {{ modalApp.company_name }}
                </h5>
                <small class="text-white-50">
                  Submitted:
                  {{ new Date(modalApp.created_at).toLocaleString() }}
                </small>
              </div>
              <div
                class="ml-auto d-flex align-items-center"
                style="gap: 0.5rem"
              >
                <span
                  v-if="modalApp.applicationStatus"
                  class="badge"
                  :style="{
                    backgroundColor: modalApp.applicationStatus.color,
                    color: '#fff',
                  }"
                  >{{ modalApp.applicationStatus.name }}</span
                >
                <span v-else class="badge badge-secondary">Pending</span>
                <button
                  type="button"
                  class="close text-white ml-2"
                  style="opacity: 1"
                  @click="closeModal"
                >
                  <span style="font-size: 1.4rem">&times;</span>
                </button>
              </div>
            </div>

            <!-- Modal body -->
            <div class="modal-body" style="background: #f8f9fa">
              <!-- Row 1: Contact / Rep / Stats -->
              <div class="row mb-3">
                <div class="col-md-4 mb-3">
                  <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-phone mr-1 text-muted"></i> Contact
                      Details
                    </h6>
                    <p class="mb-1">
                      <strong>Company:</strong> {{ modalApp.company_name }}
                    </p>
                    <p class="mb-1">
                      <strong>Tel:</strong> {{ modalApp.telephone || "—" }}
                    </p>
                    <p class="mb-1">
                      <strong>Email:</strong> {{ modalApp.email || "—" }}
                    </p>
                    <p class="mb-1">
                      <strong>Website:</strong>
                      <a
                        v-if="modalApp.website"
                        :href="modalApp.website"
                        target="_blank"
                        rel="noopener"
                        >{{ modalApp.website }}</a
                      >
                      <span v-else>—</span>
                    </p>
                    <p class="mb-0">
                      <strong>Address:</strong> {{ modalApp.address || "—" }}
                    </p>
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-user mr-1 text-muted"></i> Representative
                    </h6>
                    <p class="mb-1">
                      <strong>Name:</strong>
                      {{ modalApp.representative_name || "—" }}
                    </p>
                    <p class="mb-1">
                      <strong>Position:</strong>
                      {{ modalApp.representative_position || "—" }}
                    </p>
                    <p class="mb-1">
                      <strong>Tel:</strong>
                      {{ modalApp.representative_telephone || "—" }}
                    </p>
                    <p class="mb-0">
                      <strong>Email:</strong>
                      {{ modalApp.representative_email || "—" }}
                    </p>
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <div class="card border-0 shadow-sm p-3 h-100">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-chart-bar mr-1 text-muted"></i> Document
                      Compliance
                    </h6>
                    <div class="d-flex justify-content-between mb-2">
                      <div class="text-center">
                        <div class="h5 font-weight-bold mb-0">
                          {{ modalApp.compliance?.docs_submitted ?? "—" }}
                        </div>
                        <div class="small text-muted">Uploaded</div>
                      </div>
                      <div class="text-center">
                        <div class="h5 font-weight-bold mb-0">
                          {{
                            modalApp.compliance?.mandatory_satisfied ?? "—"
                          }}/{{ modalApp.compliance?.mandatory_total ?? "—" }}
                        </div>
                        <div class="small text-muted">Mandatory met</div>
                      </div>
                      <div class="text-center">
                        <div
                          class="h5 font-weight-bold mb-0"
                          :style="{
                            color: complianceBg(modalCompliancePct(modalApp)),
                          }"
                        >
                          {{ modalCompliancePct(modalApp) }}%
                        </div>
                        <div class="small text-muted">Complete</div>
                      </div>
                    </div>
                    <div
                      class="progress mb-2"
                      style="height: 8px; border-radius: 4px"
                    >
                      <div
                        class="progress-bar"
                        :style="{
                          width: modalCompliancePct(modalApp) + '%',
                          backgroundColor: complianceBg(
                            modalCompliancePct(modalApp)
                          ),
                        }"
                      ></div>
                    </div>
                    <div
                      v-if="
                        modalApp.compliance && !modalApp.compliance.is_complete
                      "
                      class="small text-danger"
                    >
                      <i class="fas fa-exclamation-triangle mr-1"></i>Missing
                      {{ modalApp.compliance.missing_req_ids?.length }}
                      mandatory doc(s)
                    </div>
                    <div v-else class="small text-success">
                      <i class="fas fa-check-circle mr-1"></i>All mandatory
                      documents submitted
                    </div>
                    <div
                      v-if="modalApp.rating !== null && modalApp.rating !== ''"
                      class="mt-2"
                    >
                      <strong>Score:</strong>
                      <span class="badge badge-primary ml-1"
                        >{{ parseFloat(modalApp.rating).toFixed(1) }} /
                        100</span
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Row 2: Files + Notes -->
              <div class="row">
                <div class="col-md-8 mb-3">
                  <div class="card border-0 shadow-sm p-3">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-file-alt mr-1 text-muted"></i> Submitted
                      Files
                    </h6>
                    <div
                      v-if="!modalApp.files || modalApp.files.length === 0"
                      class="text-muted"
                    >
                      No files submitted.
                    </div>
                    <ul v-else class="list-group list-group-flush">
                      <li
                        v-for="f in modalApp.files"
                        :key="f.id"
                        class="list-group-item d-flex justify-content-between align-items-start px-0"
                      >
                        <div>
                          <div
                            class="d-flex align-items-center"
                            style="gap: 0.4rem"
                          >
                            <i class="fas fa-paperclip text-secondary"></i>
                            <span class="font-weight-semibold">{{
                              f.requirement?.title || "Document"
                            }}</span>
                            <span
                              v-if="f.requirement?.mandatory"
                              class="badge badge-danger"
                              >Mandatory</span
                            >
                            <span v-else class="badge badge-secondary"
                              >Optional</span
                            >
                          </div>
                          <div class="text-muted small mt-1">
                            {{ f.file_name }}
                          </div>
                        </div>
                        <a
                          :href="fileUrl(f.filepath)"
                          target="_blank"
                          rel="noopener"
                          class="btn btn-sm btn-outline-success ml-3 flex-shrink-0"
                        >
                          <i class="fas fa-download mr-1"></i>Download
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <div class="card border-0 shadow-sm p-3 mb-3">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-sticky-note mr-1 text-muted"></i>
                      Applicant Notes
                    </h6>
                    <p class="mb-0 text-muted" style="white-space: pre-line">
                      {{ modalApp.additional_notes || "—" }}
                    </p>
                  </div>

                  <!-- Score card -->
                  <div class="card border-0 shadow-sm p-3 mb-3">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-star mr-1 text-muted"></i> Score
                    </h6>
                    <div
                      v-if="ratingDraft[modalApp.encrypted_id]"
                      class="d-flex align-items-center"
                      style="gap: 0.5rem"
                    >
                      <input
                        v-model="ratingDraft[modalApp.encrypted_id].rating"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        class="form-control form-control-sm"
                        placeholder="0–100"
                        style="max-width: 100px"
                      />
                      <span class="small text-muted">/ 100</span>
                      <button
                        class="btn btn-sm btn-outline-success"
                        :disabled="savingRating[modalApp.encrypted_id]"
                        @click="saveRating(modalApp)"
                      >
                        <i class="fas fa-save"></i>
                        {{
                          savingRating[modalApp.encrypted_id]
                            ? "Saving…"
                            : "Save"
                        }}
                      </button>
                    </div>
                  </div>

                  <!-- CRM Notes -->
                  <div class="card border-0 shadow-sm p-3">
                    <h6 class="font-weight-bold mb-2">
                      <i class="fas fa-comments mr-1 text-muted"></i> Notes
                      History
                    </h6>
                    <div
                      v-if="modalApp.notes && modalApp.notes.length"
                      class="mb-2"
                      style="max-height: 200px; overflow-y: auto"
                    >
                      <div
                        v-for="n in modalApp.notes"
                        :key="n.id"
                        class="p-2 mb-1 rounded"
                        style="background: #f8f9fa; border: 1px solid #e9ecef"
                      >
                        <div
                          class="d-flex justify-content-between align-items-start"
                        >
                          <span class="small font-weight-bold text-dark">{{
                            n.user?.name ?? "Admin"
                          }}</span>
                          <span class="small text-muted">{{
                            formatNoteDate(n.created_at)
                          }}</span>
                        </div>
                        <p
                          class="mb-0 small mt-1"
                          style="white-space: pre-line"
                        >
                          {{ n.note }}
                        </p>
                      </div>
                    </div>
                    <p v-else class="small text-muted mb-2">No notes yet.</p>
                    <textarea
                      v-model="newNote[modalApp.encrypted_id]"
                      class="form-control form-control-sm mb-2"
                      rows="3"
                      placeholder="Add a note…"
                    ></textarea>
                    <button
                      class="btn btn-sm btn-primary w-100"
                      :disabled="
                        savingNote[modalApp.encrypted_id] ||
                        !(newNote[modalApp.encrypted_id] ?? '').trim()
                      "
                      @click="addNote(modalApp)"
                    >
                      <i class="fas fa-plus mr-1"></i>
                      {{
                        savingNote[modalApp.encrypted_id]
                          ? "Saving…"
                          : "Add Note"
                      }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer bg-white">
              <div
                class="d-flex flex-wrap align-items-center justify-content-between w-100"
                style="gap: 0.5rem"
              >
                <div class="d-flex" style="gap: 0.4rem">
                  <button
                    v-if="
                      getStatusName(modalApp.applicationStatus) !==
                      'Shortlisted'
                    "
                    class="btn btn-sm btn-primary"
                    :disabled="pendingStatusUpdate[modalApp.encrypted_id]"
                    @click="
                      updateAppStatus(
                        modalApp,
                        applicationStatuses.find(
                          (s) => s.name === 'Shortlisted'
                        )?.id
                      )
                    "
                  >
                    <i class="fas fa-star mr-1"></i>Shortlist
                  </button>
                  <button
                    v-if="
                      getStatusName(modalApp.applicationStatus) !== 'Confirmed'
                    "
                    class="btn btn-sm btn-success"
                    :disabled="pendingStatusUpdate[modalApp.encrypted_id]"
                    @click="
                      updateAppStatus(
                        modalApp,
                        applicationStatuses.find((s) => s.name === 'Confirmed')
                          ?.id
                      )
                    "
                  >
                    <i class="fas fa-check mr-1"></i>Confirm
                  </button>
                  <button
                    v-if="
                      getStatusName(modalApp.applicationStatus) !== 'Rejected'
                    "
                    class="btn btn-sm btn-danger"
                    :disabled="pendingStatusUpdate[modalApp.encrypted_id]"
                    @click="
                      updateAppStatus(
                        modalApp,
                        applicationStatuses.find((s) => s.name === 'Rejected')
                          ?.id
                      )
                    "
                  >
                    <i class="fas fa-times mr-1"></i>Reject
                  </button>
                </div>
                <div class="d-flex" style="gap: 0.4rem">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="closeModal"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
