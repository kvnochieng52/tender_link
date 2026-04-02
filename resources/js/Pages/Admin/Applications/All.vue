<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  applications: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const formatDate = (v) => (v ? new Date(v).toLocaleString() : "—");

const search = ref(props.filters.q ?? "");

let timer = null;
watch(search, () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(
      route("admin.applications.all"),
      { q: search.value || undefined },
      { preserveState: true, replace: true }
    );
  }, 350);
});
</script>

<template>
  <DashboardLayout>
    <Head title="All Applications — Admin" />

    <div class="card border-0 shadow-sm mb-4">
      <div
        class="card-header bg-white d-flex justify-content-between align-items-center"
      >
        <div class="d-flex align-items-center" style="gap: 0.75rem; flex: 1">
          <h5 class="mb-0">All Submitted Applications</h5>
          <div>
            <input
              v-model="search"
              type="search"
              class="form-control form-control-sm"
              placeholder="Search company, rep, phone or email"
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
          No applications found.
        </div>
        <div v-else class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Tender</th>
                <th>Company</th>
                <th>Representative</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Docs</th>
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
                    :href="
                      route('admin.tenders.applications.index', {
                        encryptedId: app.tender.encrypted_id,
                      })
                    "
                    class="text-success"
                  >
                    {{ app.tender.tender_no || app.tender.title }}
                  </Link>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>{{ app.company_name }}</td>
                <td>{{ app.representative_name }}</td>
                <td>
                  {{ app.representative_telephone || app.telephone || "—" }}
                </td>
                <td>{{ app.email || "—" }}</td>
                <td>
                  <span class="badge badge-info">
                    {{ app.files ? app.files.length : 0 }}
                  </span>
                </td>
                <td>{{ formatDate(app.created_at) }}</td>
                <td class="text-right">
                  <Link
                    :href="
                      route('admin.applications.show', {
                        encryptedAppId: app.encrypted_id,
                      })
                    "
                    class="btn btn-sm btn-success"
                  >
                    View
                  </Link>
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
