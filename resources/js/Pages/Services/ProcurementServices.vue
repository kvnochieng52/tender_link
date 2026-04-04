<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

defineProps({ canLogin: { type: Boolean }, canRegister: { type: Boolean } });
const currentYear = new Date().getFullYear();

const activeTab = ref("discovery");

const tabs = [
  { id: "discovery", icon: "fas fa-search", label: "Tender Discovery" },
  {
    id: "application",
    icon: "fas fa-file-upload",
    label: "Online Application",
  },
  {
    id: "evaluation",
    icon: "fas fa-clipboard-check",
    label: "Evaluation & Scoring",
  },
  { id: "award", icon: "fas fa-award", label: "Award & Notification" },
];

const tabContent = {
  discovery: {
    heading: "Tender Discovery & Alerts",
    colour: "#28a745",
    bg: "rgba(40,167,69,0.07)",
    icon: "fas fa-search",
    intro:
      "Our database is refreshed multiple times daily, pulling tenders from government portals, county procurement offices, NGOs, and private institutions across Kenya — all in one searchable place.",
    points: [
      {
        icon: "fas fa-bolt",
        label: "Real-time feed updated throughout the day",
      },
      { icon: "fas fa-bell", label: "Keyword & category email/SMS alerts" },
      {
        icon: "fas fa-filter",
        label: "County, industry, and deadline filters",
      },
      {
        icon: "fas fa-mobile-alt",
        label: "Fully responsive — browse on any device",
      },
      {
        icon: "fas fa-clock",
        label: "Closing-date reminders so nothing slips through",
      },
      {
        icon: "fas fa-search",
        label: "Full-text search across all tender fields",
      },
    ],
    cta: "Browse Tenders",
    ctaRoute: "tenders.search",
  },
  application: {
    heading: "Online Application & Document Upload",
    colour: "#1a7ec9",
    bg: "rgba(26,126,201,0.07)",
    icon: "fas fa-file-upload",
    intro:
      "Submit tender applications directly on the platform. Upload all required documents, track your submission status in real time, and receive instant confirmation — no printing, no couriering.",
    points: [
      { icon: "fas fa-upload", label: "Multi-file document upload per tender" },
      { icon: "fas fa-tasks", label: "Live submission tracking dashboard" },
      {
        icon: "fas fa-envelope-open-text",
        label: "Instant email confirmation on submit",
      },
      { icon: "fas fa-lock", label: "Encrypted document storage" },
      { icon: "fas fa-history", label: "Full application history per user" },
      {
        icon: "fas fa-check-double",
        label: "Pre-submission checklist validation",
      },
    ],
    cta: "Register Free",
    ctaRoute: "register",
  },
  evaluation: {
    heading: "Evaluation & Scoring",
    colour: "#6b46c1",
    bg: "rgba(107,70,193,0.07)",
    icon: "fas fa-clipboard-check",
    intro:
      "A structured, transparent evaluation workflow for procurement officers and institutions — configurable scoring rubrics, side-by-side applicant comparisons, and a full audit trail for compliance.",
    points: [
      {
        icon: "fas fa-sliders-h",
        label: "Configurable scoring rubrics per tender",
      },
      { icon: "fas fa-columns", label: "Side-by-side applicant comparison" },
      { icon: "fas fa-users", label: "Multi-evaluator parallel scoring" },
      { icon: "fas fa-trophy", label: "Automatic ranking and shortlisting" },
      { icon: "fas fa-file-pdf", label: "PDF & Excel export of results" },
      { icon: "fas fa-shield-alt", label: "Full audit trail — 100% compliant" },
    ],
    cta: "Learn More",
    ctaRoute: "register",
  },
  award: {
    heading: "Award & Notification",
    colour: "#ea7a20",
    bg: "rgba(234,122,32,0.07)",
    icon: "fas fa-award",
    intro:
      "Close the loop professionally. Auto-generate award letters, bulk-notify all applicants of outcomes, and maintain compliance documentation — all from within the platform.",
    points: [
      { icon: "fas fa-file-signature", label: "Auto-generated award letters" },
      {
        icon: "fas fa-envelope",
        label: "Bulk email notifications to all applicants",
      },
      {
        icon: "fas fa-archive",
        label: "Compliance documentation stored securely",
      },
      { icon: "fas fa-handshake", label: "Digital acceptance confirmation" },
      {
        icon: "fas fa-calendar-check",
        label: "Contract start date scheduling",
      },
      { icon: "fas fa-chart-bar", label: "Post-award analytics and reporting" },
    ],
    cta: "Get Started",
    ctaRoute: "register",
  },
};

const addons = [
  {
    icon: "fas fa-chart-bar",
    title: "Analytics & Reporting",
    text: "Dashboards showing tender performance, application volumes, popular categories, and revenue insights for administrators.",
  },
  {
    icon: "fas fa-users-cog",
    title: "Supplier Management",
    text: "Manage supplier profiles, verify credentials, and maintain a pre-qualified supplier register for repeat procurement.",
  },
  {
    icon: "fas fa-credit-card",
    title: "Subscriptions & Billing",
    text: "Flexible monthly, quarterly, and annual plans with M-Pesa and card payment channels for fast onboarding.",
  },
  {
    icon: "fas fa-shield-alt",
    title: "Compliance & Security",
    text: "Role-based access, encrypted document storage, and anti-scraping measures keep your data and processes safe.",
  },
  {
    icon: "fas fa-headset",
    title: "Dedicated Support",
    text: "Chat, email, and phone support with dedicated account managers for institutional clients.",
  },
  {
    icon: "fas fa-code",
    title: "API Integrations",
    text: "Connect Tender Plug to your ERP, accounting system, or government procurement portal via our RESTful API.",
  },
];
</script>

<template>
  <Head title="End-to-End Procurement Services – Tender Plug" />
  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="services"
    />

    <main>
      <!-- ── Hero ───────────────────────────────────────── -->
      <section class="proc-hero">
        <div class="proc-hero-overlay">
          <div class="container text-center py-5">
            <nav aria-label="breadcrumb" class="mb-3">
              <ol
                class="breadcrumb justify-content-center breadcrumb-dark mb-0"
              >
                <li class="breadcrumb-item">
                  <Link :href="route('services')" class="text-white-50"
                    >Services</Link
                  >
                </li>
                <li class="breadcrumb-item active text-white">
                  End-to-End Procurement
                </li>
              </ol>
            </nav>
            <h1 class="proc-hero-title mb-3">
              End-to-End Procurement Services
            </h1>
            <p class="proc-hero-sub mx-auto mb-4">
              One platform. Four stages. From discovery to award — Tender Plug
              handles the full procurement cycle so your team can focus on
              winning.
            </p>
            <div
              class="d-flex justify-content-center flex-wrap"
              style="gap: 0.75rem"
            >
              <Link
                :href="route('register')"
                class="btn btn-success btn-lg px-5"
                ><i class="fas fa-rocket mr-2"></i>Get Started Free</Link
              >
              <Link
                :href="route('contact')"
                class="btn btn-outline-light btn-lg px-4"
                ><i class="fas fa-envelope mr-2"></i>Talk to Us</Link
              >
            </div>
          </div>
        </div>
      </section>

      <!-- ── Stage tabs ──────────────────────────────────── -->
      <section class="py-5 bg-white">
        <div class="container">
          <!-- Tab buttons -->
          <div class="stage-tab-bar mb-0">
            <button
              v-for="t in tabs"
              :key="t.id"
              :class="['stage-tab-btn', activeTab === t.id ? 'active' : '']"
              @click="activeTab = t.id"
            >
              <i :class="t.icon + ' mr-2'"></i>{{ t.label }}
            </button>
          </div>

          <!-- Tab panel -->
          <div
            v-for="t in tabs"
            v-show="activeTab === t.id"
            :key="t.id"
            class="tab-panel"
            :style="{ borderTopColor: tabContent[t.id].colour }"
          >
            <div class="row align-items-start">
              <div class="col-lg-5 mb-4 mb-lg-0">
                <div
                  class="tab-icon-block mb-3"
                  :style="{ background: tabContent[t.id].bg }"
                >
                  <i
                    :class="tabContent[t.id].icon"
                    :style="{ color: tabContent[t.id].colour }"
                  ></i>
                </div>
                <h2
                  class="font-weight-bold mb-3 tab-heading"
                  :style="{ color: tabContent[t.id].colour }"
                >
                  {{ tabContent[t.id].heading }}
                </h2>
                <p class="text-muted mb-4">{{ tabContent[t.id].intro }}</p>
                <Link
                  :href="route(tabContent[t.id].ctaRoute)"
                  class="btn btn-lg px-5 tab-cta-btn"
                  :style="{ background: tabContent[t.id].colour }"
                >
                  <i class="fas fa-arrow-right mr-2"></i
                  >{{ tabContent[t.id].cta }}
                </Link>
              </div>
              <div class="col-lg-7">
                <div class="row">
                  <div
                    v-for="p in tabContent[t.id].points"
                    :key="p.label"
                    class="col-sm-6 mb-3"
                  >
                    <div
                      class="point-item d-flex align-items-start"
                      style="gap: 0.75rem"
                    >
                      <div
                        class="point-icon"
                        :style="{
                          background: tabContent[t.id].bg,
                          color: tabContent[t.id].colour,
                        }"
                      >
                        <i :class="p.icon"></i>
                      </div>
                      <span class="small text-dark font-weight-600">{{
                        p.label
                      }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Pipeline strip ──────────────────────────────── -->
      <section class="pipeline-strip py-4">
        <div class="container">
          <div
            class="d-flex flex-wrap align-items-center justify-content-center"
            style="gap: 0"
          >
            <div
              v-for="(stage, i) in ['Discover', 'Apply', 'Evaluate', 'Award']"
              :key="stage"
              class="pipeline-node"
            >
              <div class="pipeline-circle">{{ i + 1 }}</div>
              <div class="pipeline-label">{{ stage }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── More Tools to Grow With ──────────────────────── -->
      <section
        class="py-5"
        style="background: linear-gradient(160deg, #f0faf4 0%, #e8f8ee 100%)"
      >
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-plus-circle mr-1"></i>Add-on Services</span
            >
            <h2 class="font-weight-bold" style="color: #1a3a22">
              More Tools to Grow With
            </h2>
            <p class="text-muted mx-auto" style="max-width: 520px">
              Extend your capabilities with these supplementary services,
              available on selected plans.
            </p>
          </div>
          <div class="row">
            <div
              v-for="addon in addons"
              :key="addon.title"
              class="col-sm-6 col-lg-4 mb-4"
            >
              <div class="addon-card h-100 d-flex" style="gap: 1rem">
                <div class="addon-icon-wrap flex-shrink-0">
                  <i :class="addon.icon"></i>
                </div>
                <div>
                  <h6 class="font-weight-bold mb-1">{{ addon.title }}</h6>
                  <p class="text-muted small mb-0">{{ addon.text }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA ─────────────────────────────────────────── -->
      <section class="proc-cta py-5">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-8 mb-3 mb-lg-0">
              <h2 class="font-weight-bold text-white mb-2">
                Ready to transform your procurement?
              </h2>
              <p class="text-white-50 mb-0">
                Join hundreds of institutions and suppliers already using Tender
                Plug.
              </p>
            </div>
            <div class="col-lg-4 text-lg-right">
              <Link
                :href="route('register')"
                class="btn btn-light btn-lg px-5 font-weight-bold"
                style="color: #1a3a22"
                ><i class="fas fa-rocket mr-2"></i>Register Free</Link
              >
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="landing-footer border-top">
      <div class="container">
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-md-center"
        >
          <img
            src="/images/tender-link-logo.svg"
            alt="Tender Plug"
            class="footer-logo mb-3 mb-md-0"
          />
          <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem">
            <a href="#" class="footer-link mr-3">Terms and Conditions</a>
            <Link :href="route('contact')" class="footer-link">Contact Us</Link>
          </div>
        </div>
        <div class="footer-bottom mt-3 pt-3">
          <small class="text-muted"
            >© {{ currentYear }} Tender Plug. All rights reserved.</small
          >
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.landing-page {
  overflow-x: hidden;
}
.footer-logo {
  height: 34px;
  width: auto;
}
.landing-footer {
  background: #fff;
  border-color: #dfe5e1 !important;
  padding: 1.15rem 0 1rem;
}
.footer-link {
  color: #1f8f53;
  font-size: 0.94rem;
  font-weight: 600;
  text-decoration: none;
}
.footer-link:hover {
  text-decoration: underline;
}
.footer-bottom {
  border-top: 1px solid #ebf1ed;
}

/* Hero */
.proc-hero {
  background: url("https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1400&q=80")
    center/cover no-repeat;
  min-height: 340px;
  display: flex;
  align-items: center;
}
.proc-hero-overlay {
  width: 100%;
  background: rgba(10, 40, 20, 0.65);
  min-height: 340px;
  display: flex;
  align-items: center;
}
.proc-hero-title {
  font-size: 2.4rem;
  font-weight: 800;
  color: #fff;
}
.proc-hero-sub {
  font-size: 1.05rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 600px;
}
.breadcrumb-dark {
  background: transparent;
  padding: 0;
}
.breadcrumb-dark .breadcrumb-item + .breadcrumb-item::before {
  color: rgba(255, 255, 255, 0.4);
}

/* Stage tabs */
.stage-tab-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  border-bottom: none;
}
.stage-tab-btn {
  flex: 1;
  min-width: 160px;
  padding: 0.85rem 1rem;
  border: 1px solid #e0e0e0;
  border-bottom: none;
  background: #f8f9fa;
  color: #6c757d;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s;
  outline: none;
}
.stage-tab-btn:first-child {
  border-radius: 0.5rem 0 0 0;
}
.stage-tab-btn:last-child {
  border-radius: 0 0.5rem 0 0;
}
.stage-tab-btn.active {
  background: #fff;
  color: #1a3a22;
  border-bottom: 3px solid #28a745;
}
.stage-tab-btn:hover:not(.active) {
  background: #fff;
  color: #333;
}
.tab-panel {
  border: 1px solid #e0e0e0;
  border-top: 3px solid #28a745;
  border-radius: 0 0 0.75rem 0.75rem;
  padding: 2rem 2rem 1.5rem;
  background: #fff;
}
.tab-icon-block {
  width: 64px;
  height: 64px;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.7rem;
}
.tab-heading {
  font-size: 1.6rem;
}
.tab-cta-btn {
  color: #fff;
  border: none;
  border-radius: 0.5rem;
  font-weight: 700;
}
.tab-cta-btn:hover {
  opacity: 0.88;
  color: #fff;
}
.point-item {
  padding: 0.6rem 0.75rem;
  border-radius: 0.5rem;
  background: #f8fdf9;
}
.point-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.font-weight-600 {
  font-weight: 600;
}

/* Pipeline strip */
.pipeline-strip {
  background: #1a3a22;
}
.pipeline-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  padding: 0 2rem;
}
.pipeline-node:not(:last-child)::after {
  content: "→";
  position: absolute;
  right: -0.5rem;
  top: 0.6rem;
  color: rgba(255, 255, 255, 0.4);
  font-size: 1.4rem;
}
.pipeline-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 2px solid #28a745;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.1rem;
}
.pipeline-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.78rem;
  font-weight: 600;
  margin-top: 0.4rem;
  letter-spacing: 0.04em;
}

/* Add-ons */
.addon-card {
  background: #fff;
  border: 1px solid #e9f5ed;
  border-radius: 0.75rem;
  padding: 1.25rem;
  transition: box-shadow 0.2s;
}
.addon-card:hover {
  box-shadow: 0 4px 16px rgba(40, 167, 69, 0.1);
}
.addon-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(40, 167, 69, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #28a745;
  margin-top: 0.1rem;
}
.badge-success-soft {
  background: rgba(40, 167, 69, 0.12);
  color: #1a3a22;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}

/* CTA */
.proc-cta {
  background: linear-gradient(135deg, #1a3a22 0%, #28a745 100%);
}
</style>
