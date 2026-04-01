<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  tenders: { type: Object, required: true },
  statuses: { type: Array, default: () => [] },
  industries: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? "");
const statusId = ref(props.filters.status_id ?? "");
const industryId = ref(props.filters.industry_id ?? "");
const countyId = ref(props.filters.county_id ?? "");

const statusOptions = computed(() =>
  props.statuses.map((s) => ({ label: s.name, value: String(s.id) }))
);

const industryOptions = computed(() =>
  props.industries.map((i) => ({ label: i.name, value: String(i.id) }))
);

const countyOptions = computed(() =>
  props.counties.map((c) => ({ label: c.name, value: String(c.id) }))
);

const selectedStatusOption = computed({
  get: () =>
    statusOptions.value.find(
      (option) => option.value === String(statusId.value)
    ) || null,
  set: (option) => {
    statusId.value = option?.value ?? "";
  },
});

const selectedIndustryOption = computed({
  get: () =>
    industryOptions.value.find(
      (option) => option.value === String(industryId.value)
    ) || null,
  set: (option) => {
    industryId.value = option?.value ?? "";
  },
});

const selectedCountyOption = computed({
  get: () =>
    countyOptions.value.find(
      (option) => option.value === String(countyId.value)
    ) || null,
  set: (option) => {
    countyId.value = option?.value ?? "";
  },
});

let searchTimer = null;

const applyFilters = () => {
  router.get(
    route("tenders.index"),
    {
      search: search.value || undefined,
      status_id: statusId.value || undefined,
      industry_id: industryId.value || undefined,
      county_id: countyId.value || undefined,
    },
    { preserveState: true, replace: true }
  );
};

watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 400);
});

watch([statusId, industryId, countyId], applyFilters);

const resetFilters = () => {
  search.value = "";
  statusId.value = "";
  industryId.value = "";
  countyId.value = "";
};

const statusBadgeClass = (name) => {
  const map = {
    Active: "badge-success",
    Closed: "badge-secondary",
    Cancelled: "badge-danger",
  };
  return map[name] ?? "badge-light";
};

const showFilesModal = ref(false);
const selectedTender = ref(null);

const openFilesModal = (tender) => {
  selectedTender.value = tender;
  showFilesModal.value = true;
};

const closeFilesModal = () => {
  showFilesModal.value = false;
  selectedTender.value = null;
};

const fileDownloadUrl = (filepath) => {
  if (!filepath) return "#";
  return `/storage/${filepath}`;
};

const formatDateTime = (val) => {
  if (!val) return "—";
  return new Date(val).toLocaleString("en-KE", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>

<template>
  <Head title="Tender List" />

  <DashboardLayout>
    <!-- Hero -->
    <div class="card border-0 shadow-sm tender-page-hero mb-4">
      <div
        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between"
      >
        <div class="mb-3 mb-md-0">
          <h1 class="h5 font-weight-bold mb-1 text-white">Tender List</h1>
          <p class="mb-0 text-white-50">
            Browse, search and manage all registered tenders.
          </p>
        </div>
        <Link
          :href="route('tenders.create')"
          class="btn btn-light font-weight-semibold"
        >
          <i class="fas fa-plus mr-1"></i> New Tender
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4 filters-card">
      <div class="card-body pb-2">
        <div class="row">
          <div class="col-md-4 col-sm-6 mb-2">
            <div class="input-group input-group-sm">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white"
                  ><i class="fas fa-search text-muted"></i
                ></span>
              </div>
              <input
                v-model="search"
                type="text"
                class="form-control"
                placeholder="Search title or tender no…"
              />
            </div>
          </div>

          <div class="col-md-2 col-sm-6 mb-2">
            <v-select
              v-model="selectedStatusOption"
              :options="statusOptions"
              label="label"
              :reduce="(option) => option"
              placeholder="All Statuses"
              class="select2-like"
              :clearable="true"
              append-to-body
            />
          </div>

          <div class="col-md-3 col-sm-6 mb-2">
            <v-select
              v-model="selectedIndustryOption"
              :options="industryOptions"
              label="label"
              :reduce="(option) => option"
              placeholder="All Industries"
              class="select2-like"
              :clearable="true"
              append-to-body
            />
          </div>

          <div class="col-md-2 col-sm-6 mb-2">
            <v-select
              v-model="selectedCountyOption"
              :options="countyOptions"
              label="label"
              :reduce="(option) => option"
              placeholder="All Counties"
              class="select2-like"
              :clearable="true"
              append-to-body
            />
          </div>

          <div class="col-md-1 mb-2">
            <button
              v-if="search || statusId || industryId || countyId"
              class="btn btn-sm btn-outline-secondary w-100"
              title="Clear filters"
              @click="resetFilters"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm table-card">
      <div class="card-body p-0">
        <div
          v-if="tenders.data.length === 0"
          class="text-center py-5 text-muted"
        >
          <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
          No tenders found.
          <Link :href="route('tenders.create')" class="text-success"
            >Register one now.</Link
          >
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover mb-0 tender-table">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Tender No</th>
                <th>Title</th>
                <th>Institution</th>
                <th>Status</th>
                <th>Closing</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tender, idx) in tenders.data" :key="tender.id">
                <td class="text-muted small">
                  {{ (tenders.current_page - 1) * tenders.per_page + idx + 1 }}
                </td>
                <td class="font-weight-semibold small text-nowrap">
                  <div class="d-flex align-items-center gap-2">
                    <span>{{ tender.tender_no }}</span>
                    <span
                      v-if="tender.tender_link_process"
                      class="badge badge-success d-flex align-items-center"
                      style="gap: 4px"
                    >
                      <i class="fas fa-check"></i>
                      TLprocess
                    </span>
                  </div>
                </td>
                <td>
                  <div class="font-weight-semibold tender-item-title">
                    {{ tender.title }}
                  </div>
                  <small class="text-muted">{{ tender.county?.name }}</small>
                </td>
                <td class="small">
                  {{ tender.institution?.institution_name ?? "—" }}
                </td>
                <td>
                  <span
                    class="badge"
                    :class="statusBadgeClass(tender.status?.name)"
                    >{{ tender.status?.name ?? "—" }}</span
                  >
                </td>
                <td class="small">
                  <div class="text-nowrap">
                    {{ formatDateTime(tender.closing_date_and_time) }}
                  </div>
                  <div class="text-nowrap text-muted">
                    Exp: {{ formatDateTime(tender.expiry_date) }}
                  </div>
                </td>
                <td class="text-right">
                  <div class="btn-group btn-group-sm ml-auto">
                    <a
                      :href="
                        route('tenders.public.show', { slug: tender.slug })
                      "
                      class="btn btn-outline-info"
                      title="Public details"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <i class="fas fa-eye"></i>
                    </a>
                    <button
                      type="button"
                      class="btn btn-outline-success"
                      title="Download files"
                      @click="openFilesModal(tender)"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                    <Link
                      :href="
                        route('admin.tenders.applications.index', {
                          encryptedId: tender.encrypted_id,
                        })
                      "
                      class="btn btn-outline-secondary"
                      title="View applications"
                    >
                      <i class="fas fa-inbox"></i>
                    </Link>
                    <Link
                      :href="
                        route('tenders.edit', {
                          encryptedId: tender.encrypted_id,
                        })
                      "
                      class="btn btn-outline-primary"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="tenders.last_page > 1"
        class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap"
      >
        <small class="text-muted mb-1">
          Showing {{ tenders.from }}–{{ tenders.to }} of
          {{ tenders.total }} tenders
        </small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !tenders.prev_page_url }">
              <Link class="page-link" :href="tenders.prev_page_url ?? '#'"
                >‹</Link
              >
            </li>
            <li
              v-for="link in tenders.links.slice(1, -1)"
              :key="link.label"
              class="page-item"
              :class="{ active: link.active }"
            >
              <Link class="page-link" :href="link.url ?? '#'">
                {{ link.label }}
              </Link>
            </li>
            <li class="page-item" :class="{ disabled: !tenders.next_page_url }">
              <Link class="page-link" :href="tenders.next_page_url ?? '#'"
                >›</Link
              >
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <div
      v-if="showFilesModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title font-weight-bold mb-0">Download Files</h5>
            <button
              type="button"
              class="close"
              aria-label="Close"
              @click="closeFilesModal"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <p class="small text-muted mb-3">
              {{ selectedTender?.title }}
            </p>

            <div
              v-if="!selectedTender?.files || selectedTender.files.length === 0"
              class="alert alert-light border mb-0"
            >
              No files available for this tender.
            </div>

            <div v-else class="list-group">
              <a
                v-for="file in selectedTender.files"
                :key="file.id"
                :href="fileDownloadUrl(file.filepath)"
                :download="file.file_name"
                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between"
                target="_blank"
                rel="noopener noreferrer"
              >
                <span class="small text-truncate pr-3">{{
                  file.file_name
                }}</span>
                <i class="fas fa-download text-success"></i>
              </a>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="closeFilesModal"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
    <div
      v-if="showFilesModal"
      class="modal-backdrop fade show"
      @click="closeFilesModal"
    ></div>
  </DashboardLayout>
</template>

<style scoped>
.tender-page-hero {
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
}

.filters-card {
  position: relative;
  z-index: 5000;
  overflow: visible;
}

.filters-card .card-body,
.filters-card .row,
.filters-card .col-md-2,
.filters-card .col-md-3,
.filters-card .col-sm-6 {
  overflow: visible;
}

.table-card {
  position: relative;
  z-index: 1;
}

.tender-table th {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.tender-table td {
  vertical-align: middle;
}

.font-weight-semibold {
  font-weight: 600;
}

.tender-item-title {
  font-size: 0.84rem;
}

:deep(.select2-like .vs__dropdown-toggle) {
  min-height: 31px;
  border-color: #ced4da;
  cursor: pointer;
}

:deep(.select2-like .vs__selected-options) {
  padding-left: 2px;
}

:deep(.select2-like .vs__search),
:deep(.select2-like .vs__selected) {
  font-size: 0.875rem;
  cursor: pointer;
}

:deep(.select2-like .vs__actions) {
  padding-right: 6px;
}

:deep(.select2-like .vs__open-indicator),
:deep(.select2-like .vs__clear) {
  cursor: pointer;
}

:deep(.select2-like.vs--open) {
  z-index: 9000;
}

:deep(.select2-like .vs__dropdown-menu) {
  font-size: 0.875rem;
  border-color: #d7e3db;
  z-index: 9999;
}

:global(body .vs__dropdown-menu) {
  z-index: 25000 !important;
}

:global(body .vs__dropdown-option) {
  position: relative;
  z-index: 25001;
}
</style>
