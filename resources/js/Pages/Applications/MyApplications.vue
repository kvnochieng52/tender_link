<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import axios from "axios";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  applications: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const toast = useToast();

const formatDate = (v) => (v ? new Date(v).toLocaleString() : "—");

const canAmend = (app) => {
  if (!app.tender?.closing_date_and_time) return false;
  return new Date(app.tender.closing_date_and_time) > new Date();
};

const unsubmittingId = ref(null);

const unsubmit = async (app) => {
  if (!canAmend(app)) return;
  if (
    !window.confirm(
      "Withdraw this submission so you can amend it? Your submitted data will become an editable draft on the tender page. You must re-submit before the deadline."
    )
  ) {
    return;
  }

  unsubmittingId.value = app.id;
  try {
    const res = await axios.post(route("applications.unsubmit", { id: app.id }));
    if (res?.data?.success) {
      toast.success(res.data.message || "Submission withdrawn.");
      if (res.data.redirect_url) {
        window.location.href = res.data.redirect_url;
      } else {
        router.reload();
      }
    } else {
      toast.error(res?.data?.message || "Could not withdraw submission.");
    }
  } catch (e) {
    toast.error(
      e?.response?.data?.message ||
        "Could not withdraw submission. Please try again."
    );
  } finally {
    unsubmittingId.value = null;
  }
};

const search = ref(props.filters.q ?? "");

let timer = null;
watch(search, () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(
      route("my.applications"),
      { q: search.value || undefined },
      { preserveState: true, replace: true }
    );
  }, 350);
});
</script>

<template>
  <DashboardLayout>
    <Head title="My Applications" />

    <div class="card border-0 shadow-sm mb-4">
      <div
        class="card-header bg-white d-flex justify-content-between align-items-center"
      >
        <div class="d-flex align-items-center" style="gap: 0.75rem; flex: 1">
          <h5 class="mb-0">My Applications</h5>
          <div>
            <input
              v-model="search"
              type="search"
              class="form-control form-control-sm"
              placeholder="Search tender or company name"
              style="min-width: 220px"
            />
          </div>
        </div>
        <span class="badge badge-secondary">
          {{ applications.total }} total
        </span>
      </div>

      <div class="card-body p-0">
        <div
          v-if="applications.data.length === 0"
          class="p-4 text-center text-muted"
        >
          You have not submitted any applications yet.
        </div>
        <div v-else class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Tender</th>
                <th>Category</th>
                <th>Company</th>
                <th>Docs</th>
                <th>Closing Date</th>
                <th>Submitted</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(app, idx) in applications.data" :key="app.id">
                <td>
                  {{
                    (applications.current_page - 1) * applications.per_page +
                    idx +
                    1
                  }}
                </td>
                <td>
                  <Link
                    v-if="app.tender"
                    :href="route('tenders.public.show', app.tender.slug)"
                    class="text-success"
                  >
                    {{ app.tender.tender_no || app.tender.title }}
                  </Link>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span v-if="app.tender_category" class="small">
                    <strong>{{ app.tender_category.tender_no }}</strong>
                    <span class="text-muted d-block">
                      {{ app.tender_category.title }}
                    </span>
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td>{{ app.company_name }}</td>
                <td>
                  <span class="badge badge-info">
                    {{ app.files ? app.files.length : 0 }}
                  </span>
                </td>
                <td>
                  <div>
                    {{
                      app.tender
                        ? formatDate(app.tender.closing_date_and_time)
                        : "—"
                    }}
                  </div>
                  <small v-if="canAmend(app)" class="text-success">
                    <i class="fas fa-unlock-alt me-1"></i>Editable until deadline
                  </small>
                  <small v-else class="text-muted">
                    <i class="fas fa-lock me-1"></i>Closed
                  </small>
                </td>
                <td>{{ formatDate(app.created_at) }}</td>
                <td class="text-right">
                  <div
                    class="d-flex justify-content-end flex-wrap"
                    style="gap: 0.35rem"
                  >
                    <Link
                      v-if="app.tender"
                      :href="route('tenders.public.show', app.tender.slug)"
                      class="btn btn-sm btn-outline-success"
                    >
                      View Tender
                    </Link>
                    <button
                      v-if="canAmend(app)"
                      type="button"
                      class="btn btn-sm btn-outline-warning"
                      :disabled="unsubmittingId === app.id"
                      @click="unsubmit(app)"
                    >
                      <i
                        :class="
                          unsubmittingId === app.id
                            ? 'fas fa-spinner fa-spin me-1'
                            : 'fas fa-pen me-1'
                        "
                      ></i>
                      {{
                        unsubmittingId === app.id
                          ? "Withdrawing…"
                          : "Unsubmit &amp; Amend"
                      }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="applications.last_page > 1" class="card-footer bg-white">
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li
              class="page-item"
              :class="{ disabled: !applications.prev_page_url }"
            >
              <Link class="page-link" :href="applications.prev_page_url ?? '#'"
                >‹</Link
              >
            </li>
            <li
              v-for="link in applications.links.slice(1, -1)"
              :key="link.label"
              class="page-item"
              :class="{ active: link.active }"
            >
              <Link class="page-link" :href="link.url ?? '#'">{{
                link.label
              }}</Link>
            </li>
            <li
              class="page-item"
              :class="{ disabled: !applications.next_page_url }"
            >
              <Link class="page-link" :href="applications.next_page_url ?? '#'"
                >›</Link
              >
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </DashboardLayout>
</template>
