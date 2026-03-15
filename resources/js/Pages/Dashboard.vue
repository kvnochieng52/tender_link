<script setup>
import { Head, Link } from "@inertiajs/vue3";
import DashboardLayout from "../Layouts/DashboardLayout.vue";

const dashboardStats = [
  {
    title: "Active Tenders",
    value: "148",
    note: "Open opportunities",
    icon: "fas fa-briefcase",
  },
  {
    title: "Applications",
    value: "1,276",
    note: "Submitted this month",
    icon: "fas fa-file-signature",
  },
  {
    title: "Evaluations",
    value: "92",
    note: "In progress",
    icon: "fas fa-clipboard-check",
  },
  {
    title: "Awards Issued",
    value: "34",
    note: "This quarter",
    icon: "fas fa-award",
  },
];

const recentTenders = [
  {
    title: "Supply of Medical Equipment",
    category: "Supply",
    county: "Nairobi",
    deadline: "5 days left",
    status: "Open",
  },
  {
    title: "County ICT Infrastructure Upgrade",
    category: "ICT",
    county: "Kisumu",
    deadline: "3 days left",
    status: "Evaluation",
  },
  {
    title: "Road Rehabilitation Works",
    category: "Construction",
    county: "Mombasa",
    deadline: "11 days left",
    status: "Shortlisting",
  },
  {
    title: "Water Pipeline Extension Project",
    category: "Infrastructure",
    county: "Kiambu",
    deadline: "14 days left",
    status: "Open",
  },
];
</script>

<template>
  <Head title="Tender Portal Dashboard" />

  <DashboardLayout>
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
                  {{ item.value }}
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
              v-for="tender in recentTenders"
              :key="tender.title"
              class="d-flex flex-column flex-md-row justify-content-between align-items-md-center py-2 border-bottom tender-row"
            >
              <div class="pr-md-3">
                <h6 class="mb-1 font-weight-bold text-dark">
                  {{ tender.title }}
                </h6>
                <p class="mb-0 text-muted small">
                  {{ tender.category }} • {{ tender.county }} •
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
                  <i class="fas fa-bullhorn d-block mb-2"></i>
                  Advertise
                </button>
              </div>
              <div class="col-6 mb-3">
                <button
                  class="btn btn-outline-success btn-block py-3"
                  type="button"
                >
                  <i class="fas fa-filter d-block mb-2"></i>
                  Shortlist
                </button>
              </div>
              <div class="col-6">
                <button
                  class="btn btn-outline-success btn-block py-3"
                  type="button"
                >
                  <i class="fas fa-tasks d-block mb-2"></i>
                  Evaluate
                </button>
              </div>
              <div class="col-6">
                <button
                  class="btn btn-outline-success btn-block py-3"
                  type="button"
                >
                  <i class="fas fa-paper-plane d-block mb-2"></i>
                  Notify
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.dashboard-hero-card {
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
  border-radius: 0.8rem;
}

.stat-card {
  border-left: 3px solid rgba(40, 167, 69, 0.35);
}

.stat-icon {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: rgba(40, 167, 69, 0.14);
  color: #1f8f53;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.tender-row:last-child {
  border-bottom: 0 !important;
}

.tender-status {
  background: rgba(40, 167, 69, 0.14);
  color: #1f8f53;
  font-weight: 700;
}
</style>
