<script setup>
import { ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  transactions: { type: Object, required: true },
  transactionStatuses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? "");
const statusId = ref(props.filters.status_id ?? "");
const type = ref(props.filters.type ?? "");

function applyFilters() {
  router.get(
    route("admin.transactions.index"),
    {
      q: search.value || undefined,
      status_id: statusId.value || undefined,
      type: type.value || undefined,
    },
    { preserveState: true, replace: true }
  );
}

let timer = null;
watch(search, () => {
  clearTimeout(timer);
  timer = setTimeout(applyFilters, 350);
});
watch([statusId, type], applyFilters);

const formatDate = (v) =>
  v
    ? new Date(v).toLocaleString("en-KE", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    : "—";

const formatAmount = (v) =>
  v !== null && v !== undefined
    ? "KES " + Number(v).toLocaleString("en-KE", { minimumFractionDigits: 2 })
    : "—";

function statusBadgeClass(colorCode) {
  const map = {
    warning: "badge-warning",
    success: "badge-success",
    danger: "badge-danger",
    info: "badge-info",
    secondary: "badge-secondary",
    primary: "badge-primary",
    dark: "badge-dark",
  };
  return map[colorCode] ?? "badge-secondary";
}
</script>

<template>
  <DashboardLayout>
    <Head title="Transactions — Admin" />

    <div class="card border-0 shadow-sm mb-4">
      <!-- Header -->
      <div
        class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap"
        style="gap: 0.5rem"
      >
        <h5 class="mb-0">
          <i class="fas fa-exchange-alt mr-2 text-success"></i> Transactions
        </h5>

        <!-- Filters -->
        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem">
          <input
            v-model="search"
            type="search"
            class="form-control form-control-sm"
            placeholder="Search ref, receipt, phone or user…"
            style="min-width: 220px"
          />

          <select
            v-model="statusId"
            class="form-control form-control-sm"
            style="min-width: 150px"
          >
            <option value="">All Statuses</option>
            <option v-for="s in transactionStatuses" :key="s.id" :value="s.id">
              {{ s.trans_status_name }}
            </option>
          </select>

          <select
            v-model="type"
            class="form-control form-control-sm"
            style="min-width: 140px"
          >
            <option value="">All Types</option>
            <option value="plan">Plan Payment</option>
            <option value="tender">Tender Payment</option>
          </select>
        </div>

        <span class="badge badge-secondary">
          {{ transactions.total }} total
        </span>
      </div>

      <!-- Table -->
      <div class="card-body p-0">
        <div
          v-if="transactions.data.length === 0"
          class="p-4 text-center text-muted"
        >
          No transactions found.
        </div>

        <div v-else class="table-responsive">
          <table class="table mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>User</th>
                <th>Phone</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Reference</th>
                <th>M-Pesa Receipt</th>
                <th>Status</th>
                <th>Message</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tx, idx) in transactions.data" :key="tx.id">
                <td>
                  {{
                    (transactions.current_page - 1) * transactions.per_page +
                    idx +
                    1
                  }}
                </td>
                <td class="text-nowrap small">
                  {{ formatDate(tx.created_at) }}
                </td>
                <td>
                  <div class="small">{{ tx.user?.name ?? "—" }}</div>
                  <div class="small text-muted">{{ tx.user?.email ?? "" }}</div>
                </td>
                <td class="small">{{ tx.phone || "—" }}</td>
                <td class="text-nowrap font-weight-bold">
                  {{ formatAmount(tx.amount) }}
                </td>
                <td>
                  <span
                    v-if="tx.payment_type === 'plan'"
                    class="badge badge-primary"
                  >
                    Plan<span v-if="tx.plan"> — {{ tx.plan.plan_name }}</span>
                  </span>
                  <span
                    v-else-if="tx.payment_type === 'tender'"
                    class="badge badge-info"
                  >
                    Tender<span v-if="tx.tender">
                      — {{ tx.tender.tender_no || tx.tender.title }}</span
                    >
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="small text-break" style="max-width: 180px">
                  {{ tx.trans_ref || "—" }}
                </td>
                <td class="small">
                  <span
                    v-if="tx.mpesa_receipt_number"
                    class="font-weight-bold text-success"
                  >
                    {{ tx.mpesa_receipt_number }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <span
                    v-if="tx.transactionStatus"
                    :class="[
                      'badge',
                      statusBadgeClass(
                        tx.transactionStatus.trans_status_color_code
                      ),
                    ]"
                  >
                    {{ tx.transactionStatus.trans_status_name }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="small text-muted" style="max-width: 200px">
                  {{ tx.trans_message || "—" }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="transactions.last_page > 1"
        class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap"
        style="gap: 0.5rem"
      >
        <small class="text-muted">
          Showing {{ transactions.from }}–{{ transactions.to }} of
          {{ transactions.total }}
        </small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li
              v-for="link in transactions.links"
              :key="link.label"
              :class="[
                'page-item',
                { active: link.active, disabled: !link.url },
              ]"
            >
              <button
                v-if="link.url"
                class="page-link"
                @click="router.get(link.url, {}, { preserveState: true })"
                v-html="link.label"
              ></button>
              <span v-else class="page-link" v-html="link.label"></span>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </DashboardLayout>
</template>
