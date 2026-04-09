<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
});

const currentYear = new Date().getFullYear();

const mainServices = [
  {
    step: "01",
    icon: "fas fa-search",
    title: "Tender Discovery & Alerts",
    routeName: "services.procurement",
    text: "Access a continuously updated database of government, NGO, and private-sector tenders. Set keyword and category alerts to never miss a relevant opportunity.",
    features: [
      "Real-time tender feed",
      "Email & digest notifications",
      "County and industry filters",
    ],
  },
  {
    step: "02",
    icon: "fas fa-file-upload",
    title: "Online Application & Document Upload",
    routeName: "services.procurement",
    text: "Submit applications directly on the platform. Upload all required documents, track submission status, and receive instant confirmation.",
    features: [
      "Multi-file document upload",
      "Submission tracking",
      "Instant email confirmation",
    ],
  },
  {
    step: "03",
    icon: "fas fa-clipboard-check",
    title: "Evaluation & Scoring",
    routeName: "services.procurement",
    text: "Our structured evaluation workflow helps institutions score, rank, and shortlist applicants with full audit trails and transparent criteria.",
    features: [
      "Configurable scoring rubrics",
      "Side-by-side comparison",
      "Full audit trail",
    ],
  },
  {
    step: "04",
    icon: "fas fa-award",
    title: "Award & Notification",
    routeName: null,
    text: "Automate award letters and notify all applicants of outcomes, ensuring a professional, compliant conclusion to every tender cycle.",
    features: [
      "Auto-generated award letters",
      "Bulk applicant notifications",
      "Compliance documentation",
    ],
  },
];

const addOnServices = [
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

const extraServices = [
  {
    id: "bid-support",
    routeName: "services.bid-support",
    icon: "fas fa-gavel",
    badge: "Bid Support",
    badgeColor: "#1a7a40",
    title: "Compliance & Bid Support",
    text: "End-to-end support to help your business win tenders — from company profile preparation to full bid submission.",
    features: [
      "Company profile preparation",
      "Tender document preparation",
      "Prequalification support",
      "Compliance checks",
    ],
    pricing: [
      { label: "Company profile", price: "KES 15,000" },
      { label: "Full tender bid", price: "KES 10K – 50K+" },
    ],
  },
  {
    id: "marketplace",
    routeName: "services.marketplace",
    icon: "fas fa-handshake",
    badge: "Marketplace",
    badgeColor: "#1a5fa0",
    title: "B2B Marketplace",
    text: "Connect with verified businesses across Kenya. Our marketplace gives you direct access to a curated database of business partners.",
    features: ["Suppliers", "Contractors", "Service providers"],
    pricing: [],
  },
  {
    id: "funding",
    routeName: "services.funding",
    icon: "fas fa-file-invoice-dollar",
    badge: "Funding",
    badgeColor: "#7a4a1a",
    title: "Business Plan & Funding Proposals",
    text: "We develop professionally crafted business plans and funding proposals designed to attract investors, grants, and financial institutions.",
    features: [],
    pricing: [],
  },
];

const plans = [
  {
    name: "Basic",
    price: "KES 2,500",
    period: "/month",
    badge: "Starter",
    features: [
      "Access all tenders",
      "5 applications/month",
      "Email alerts",
      "Document upload",
    ],
    highlighted: false,
  },
  {
    name: "Professional",
    price: "KES 6,900",
    period: "/quarter",
    badge: "Most Popular",
    features: [
      "Unlimited applications",
      "Priority alerts (email + SMS)",
      "Evaluation dashboard",
      "Supplier analytics",
      "Dedicated support",
    ],
    highlighted: true,
  },
  {
    name: "Enterprise",
    price: "Custom",
    period: "",
    badge: "Best Value",
    features: [
      "Everything in Professional",
      "API access",
      "White-label options",
      "SLA guarantee",
      "On-site training",
    ],
    highlighted: false,
  },
];
</script>

<template>
  <Head title="Our Services – Tender Plug" />

  <div class="landing-page bg-light">
    <!-- ── Navbar ──────────────────────────────────────────── -->
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="services"
    />
    <!-- ── /Navbar ─────────────────────────────────────────── -->

    <main class="pb-5">
      <!-- Hero banner -->
      <section class="services-hero">
        <div class="services-hero-overlay">
          <div class="container text-center">
            <h1 class="services-hero-title">Our Services</h1>
            <p class="services-hero-sub">
              Everything you need to discover, apply for, evaluate, and award
              tenders — on one secure platform.
            </p>
            <Link
              :href="route('register')"
              class="btn btn-success btn-lg px-5 mt-2"
            >
              <i class="fas fa-rocket mr-2"></i>Get Started Free
            </Link>
          </div>
        </div>
      </section>

      <!-- Core services -->
      <section id="tender-discovery" class="py-5">
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-cogs mr-1"></i>Core Platform</span
            >
            <h2 class="font-weight-bold" style="color: var(--brand-primary-dark)">
              End-to-End Procurement Services
            </h2>
            <p class="text-muted mx-auto" style="max-width: 560px">
              From the moment you post or discover a tender to the final award —
              Tender Plug covers every step.
            </p>
          </div>
          <div class="row">
            <div
              v-for="svc in mainServices"
              :key="svc.step"
              class="col-sm-6 col-lg-3 mb-4"
            >
              <div class="svc-card h-100">
                <div class="svc-step-badge">{{ svc.step }}</div>
                <div class="svc-icon-wrap mb-3">
                  <i :class="svc.icon"></i>
                </div>
                <h5 class="font-weight-bold svc-title mb-2">{{ svc.title }}</h5>
                <p class="text-muted small mb-3">{{ svc.text }}</p>
                <ul class="svc-feature-list">
                  <li v-for="f in svc.features" :key="f">
                    <i class="fas fa-check-circle text-success mr-1"></i>{{ f }}
                  </li>
                </ul>
                <Link
                  v-if="svc.routeName"
                  :href="route('services.procurement')"
                  class="btn btn-outline-success btn-sm mt-3"
                  style="text-decoration: none"
                >
                  <i class="fas fa-arrow-right mr-1"></i>Learn More
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Add-on services -->
      <section
        id="online-application"
        class="py-5"
        style="background: var(--brand-surface-blue)"
      >
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-plus-circle mr-1"></i>Add-on Services</span
            >
            <h2 class="font-weight-bold" style="color: var(--brand-primary-dark)">
              More Tools to Grow With
            </h2>
            <p class="text-muted mx-auto" style="max-width: 520px">
              Extend your capabilities with these supplementary services,
              available on selected plans.
            </p>
          </div>
          <div class="row">
            <div
              v-for="addon in addOnServices"
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

      <!-- Professional Services -->
      <section id="bid-support" class="py-5 bg-white">
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-briefcase mr-1"></i>Professional Services</span
            >
            <h2 class="font-weight-bold" style="color: var(--brand-primary-dark)">
              Beyond the Platform
            </h2>
            <p class="text-muted mx-auto" style="max-width: 560px">
              Hands-on services to help your business win tenders, connect with
              partners, and secure funding.
            </p>
          </div>
          <div class="row">
            <div
              v-for="svc in extraServices"
              :key="svc.title"
              :id="svc.id"
              class="col-md-4 mb-4"
            >
              <div class="extra-svc-card h-100">
                <div class="extra-svc-icon-wrap mb-3">
                  <i :class="svc.icon"></i>
                </div>
                <span
                  class="extra-svc-badge mb-2 d-inline-block"
                  :style="{ background: svc.badgeColor }"
                  >{{ svc.badge }}</span
                >
                <h5 class="font-weight-bold mb-2" style="color: var(--brand-primary-dark)">
                  {{ svc.title }}
                </h5>
                <p class="text-muted small mb-3">{{ svc.text }}</p>

                <template v-if="svc.features.length">
                  <p class="small font-weight-bold mb-1" style="color: var(--brand-primary-dark)">
                    What we offer:
                  </p>
                  <ul class="extra-svc-list mb-3">
                    <li v-for="f in svc.features" :key="f">
                      <i class="fas fa-check-circle text-success mr-1"></i
                      >{{ f }}
                    </li>
                  </ul>
                </template>

                <template v-if="svc.pricing.length">
                  <p class="small font-weight-bold mb-1" style="color: var(--brand-primary-dark)">
                    Pricing:
                  </p>
                  <ul class="extra-svc-list">
                    <li v-for="p in svc.pricing" :key="p.label">
                      <i class="fas fa-tag text-success mr-1"></i>
                      <span class="font-weight-semibold">{{ p.label }}:</span>
                      {{ p.price }}
                    </li>
                  </ul>
                </template>

                <div class="d-flex flex-wrap mt-3" style="gap: 0.5rem">
                  <Link
                    :href="route(svc.routeName)"
                    class="btn btn-success btn-sm"
                    style="text-decoration: none"
                  >
                    <i class="fas fa-arrow-right mr-1"></i>Learn More
                  </Link>
                  <Link
                    :href="route('contact')"
                    class="btn btn-outline-success btn-sm"
                    style="text-decoration: none"
                  >
                    <i class="fas fa-envelope mr-1"></i>Enquire
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="services-cta py-5">
        <div class="container text-center">
          <h2 class="font-weight-bold text-white mb-3">
            Have a question about our services?
          </h2>
          <p class="text-white-50 mb-4">
            Our team is ready to help you find the right plan for your needs.
          </p>
          <div
            class="d-flex justify-content-center flex-wrap"
            style="gap: 1rem"
          >
            <Link
              :href="route('contact')"
              class="btn btn-light btn-lg px-5 font-weight-bold"
              style="color: var(--brand-primary)"
            >
              <i class="fas fa-envelope mr-2"></i>Contact Us
            </Link>
            <Link
              :href="route('register')"
              class="btn btn-outline-light btn-lg px-5"
            >
              <i class="fas fa-user-plus mr-2"></i>Register Now
            </Link>
          </div>
        </div>
      </section>
    </main>

    <!-- ── Footer ──────────────────────────────────────────── -->
    <footer class="landing-footer border-top">
      <div class="container footer-content-wrap">
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-md-center"
        >
          <img
            src="/images/tender-link-logo.svg"
            alt="Tender Plug"
            class="footer-logo mb-3 mb-md-0"
          />
          <div class="footer-links d-flex align-items-center flex-wrap">
            <a href="#" class="footer-link mr-3">Terms and Conditions</a>
            <Link :href="route('contact')" class="footer-link">Contact Us</Link>
          </div>
        </div>
        <div class="footer-bottom text-center text-md-left mt-3 pt-3">
          <small class="text-muted"
            >© {{ currentYear }} Tender Plug. All rights reserved.</small
          >
        </div>
      </div>
    </footer>
    <!-- ── /Footer ─────────────────────────────────────────── -->
  </div>
</template>

<style scoped>
.landing-page {
  overflow-x: hidden;
}

.brand-logo-full {
  height: 44px;
  width: auto;
  display: block;
}
.footer-logo {
  height: 34px;
  width: auto;
  display: block;
}
.landing-footer {
  background: #ffffff;
  border-color: var(--brand-primary-border) !important;
  padding: 1.15rem 0 1rem;
}
.footer-links {
  gap: 0.35rem;
}
.footer-link {
  color: var(--brand-secondary-dark);
  font-size: 0.94rem;
  font-weight: 600;
  text-decoration: none;
}
.footer-link:hover {
  color: var(--brand-primary);
  text-decoration: underline;
}
.footer-bottom {
  border-top: 1px solid var(--brand-primary-border-soft);
}

/* ── Hero ─────── */
.services-hero {
  background: url("https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1400&q=80")
    center/cover no-repeat;
  min-height: 380px;
  display: flex;
  align-items: center;
}
.services-hero-overlay {
  width: 100%;
  background: rgba(10, 40, 20, 0.62);
  min-height: 380px;
  display: flex;
  align-items: center;
  padding: 3rem 0;
}
.services-hero-title {
  font-size: 2.6rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 0.5rem;
}
.services-hero-sub {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 580px;
  margin: 0 auto;
}

/* ── Service cards ─ */
.svc-card {
  background: #fff;
  border: 1px solid #e4ebfb;
  border-radius: 0.75rem;
  padding: 2rem 1.5rem 1.5rem;
  position: relative;
  transition: box-shadow 0.2s;
}
.svc-card:hover {
  box-shadow: 0 6px 24px rgba(9, 23, 111, 0.13);
}
.svc-step-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(9, 23, 111, 0.1);
  color: var(--brand-primary);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 99px;
}
.svc-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(9, 23, 111, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: var(--brand-primary);
}
.svc-title {
  color: var(--brand-primary-dark);
}
.svc-feature-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.82rem;
  color: #495057;
}
.svc-feature-list li {
  margin-bottom: 0.3rem;
}

/* ── Add-on cards ─── */
.addon-card {
  background: #fff;
  border: 1px solid #e4ebfb;
  border-radius: 0.75rem;
  padding: 1.25rem 1.25rem;
  transition: box-shadow 0.2s;
}
.addon-card:hover {
  box-shadow: 0 4px 16px rgba(9, 23, 111, 0.1);
}
.addon-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(9, 23, 111, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: var(--brand-primary);
  margin-top: 0.1rem;
}

/* ── Pricing ─────── */
.plan-card {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.75rem;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s;
}
.plan-card:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
}
.plan-card--highlighted {
  border-color: var(--brand-primary);
  box-shadow: 0 8px 28px rgba(9, 23, 111, 0.18);
}
.plan-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 700;
  background: rgba(9, 23, 111, 0.1);
  color: var(--brand-secondary-dark);
  padding: 0.2rem 0.6rem;
  border-radius: 99px;
  margin-bottom: 0.75rem;
  align-self: flex-start;
}
.plan-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--brand-primary-dark);
  margin-bottom: 0.25rem;
}
.plan-price {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--brand-primary);
  margin-bottom: 1.25rem;
}
.plan-period {
  font-size: 0.85rem;
  font-weight: 500;
  color: #6c757d;
}
.plan-feature-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1.5rem;
  font-size: 0.85rem;
  color: #495057;
  flex: 1;
}
.plan-feature-list li {
  margin-bottom: 0.5rem;
}

/* ── CTA ──────────── */
.services-cta {
  background: var(--brand-primary);
}

/* ── Badge soft ───── */
.badge-success-soft {
  background: rgba(9, 23, 111, 0.12);
  color: var(--brand-secondary-dark);
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}

/* Professional / extra services cards */
.extra-svc-card {
  background: #fff;
  border: 1px solid #d6ead9;
  border-radius: 14px;
  padding: 1.6rem 1.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s, transform 0.2s;
}
.extra-svc-card:hover {
  box-shadow: 0 6px 24px rgba(9, 23, 111, 0.13);
  transform: translateY(-3px);
}
.extra-svc-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: var(--brand-surface-gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: var(--brand-primary);
}
.extra-svc-badge {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #fff;
  padding: 0.2em 0.65em;
  border-radius: 99px;
  text-transform: uppercase;
}
.extra-svc-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.extra-svc-list li {
  font-size: 0.875rem;
  color: #4a5568;
  margin-bottom: 0.35rem;
}
.font-weight-semibold {
  font-weight: 600;
}

@media (max-width: 767px) {
  .services-hero-title {
    font-size: 1.8rem;
  }
}
</style>
