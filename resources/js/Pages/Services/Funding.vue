<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

defineProps({ canLogin: { type: Boolean }, canRegister: { type: Boolean } });
const currentYear = new Date().getFullYear();

const openFaq = ref(null);
function toggleFaq(i) {
  openFaq.value = openFaq.value === i ? null : i;
}

const packages = [
  {
    icon: "fas fa-file-alt",
    title: "Business Plan",
    colour: "#b07d2e",
    price: "From KES 18,000",
    badge: "Most Requested",
    highlight: true,
    includes: [
      "Executive summary",
      "Market & competitor analysis",
      "5-year financial projections",
      "Operations overview",
      "Management structure",
      "Bank-ready formatting",
    ],
    idealFor: "Bank loans, AGPO grants, investor presentations",
  },
  {
    icon: "fas fa-donate",
    title: "Funding Proposal",
    colour: "#7a4a1a",
    price: "From KES 14,000",
    badge: "For Grants",
    highlight: false,
    includes: [
      "Problem statement & objectives",
      "Target beneficiary analysis",
      "Implementation plan & timeline",
      "Budget breakdown",
      "Monitoring & evaluation framework",
      "Donor-aligned narrative",
    ],
    idealFor: "NG-CDF, WEF, YEF, EU grants, international donors",
  },
  {
    icon: "fas fa-chart-line",
    title: "Financial Projections",
    colour: "#5c3d11",
    price: "From KES 8,000",
    badge: "Add-on",
    highlight: false,
    includes: [
      "3–5 year cashflow forecast",
      "Profit & loss projections",
      "Break-even analysis",
      "Sensitivity scenarios",
      "Ratio analysis",
      "Formatted for lenders",
    ],
    idealFor: "Standalone or included in business plan",
  },
  {
    icon: "fas fa-presentation",
    title: "Investor Pitch Deck",
    colour: "#964b00",
    price: "From KES 12,000",
    badge: "For Startups",
    highlight: false,
    includes: [
      "Problem & solution slides",
      "Market size & opportunity",
      "Business model overview",
      "Traction & milestones",
      "Team slide",
      "Ask & use of funds",
    ],
    idealFor: "Angel investors, pitch competitions, accelerators",
  },
];

const faqs = [
  {
    q: "How long does it take to prepare a business plan?",
    a: "Most business plans are delivered within 5–8 business days. Rush turnaround (48–72h) is available at an additional fee. We'll agree on a realistic deadline during the initial consultation.",
  },
  {
    q: "Do you need access to my financial records?",
    a: "For financial projections we will need some historical figures (sales, costs, assets) if available. For startups, we build projections from market research and your cost assumptions. We guide you through exactly what we need.",
  },
  {
    q: "Can the documents be submitted to any bank or funder?",
    a: "Yes. Deliverables are formatted to professional standards accepted by Kenyan commercial banks, SACCOs, microfinance institutions, and government grant bodies. We customise to the specific funder where required.",
  },
  {
    q: "Is there a revision period?",
    a: "Yes — we include two rounds of revisions in all packages. Most clients achieve a final document on the first or second draft.",
  },
  {
    q: "Do you offer a bundle discount?",
    a: "Yes. If you need a business plan together with financial projections and/or a pitch deck, we offer a bundled rate. Contact us to discuss your specific requirements.",
  },
  {
    q: "Are your services available outside Nairobi?",
    a: "Our services are fully remote. We work with clients from all 47 counties via email, phone, and WhatsApp. Documents are delivered digitally.",
  },
];
</script>

<template>
  <Head title="Business Plan & Funding Proposals – Tender Plug" />
  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="services"
    />

    <main>
      <!-- ── Hero ───────────────────────────────────────── -->
      <section class="fund-hero">
        <div class="container py-5">
          <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb breadcrumb-dark mb-0">
              <li class="breadcrumb-item">
                <Link :href="route('services')" class="text-white-50"
                  >Services</Link
                >
              </li>
              <li class="breadcrumb-item active text-white">
                Business Plan & Funding
              </li>
            </ol>
          </nav>
          <div class="row align-items-center">
            <div class="col-lg-8">
              <span class="fund-pill mb-3 d-inline-block"
                ><i class="fas fa-file-invoice-dollar mr-2"></i>Professional
                Document Services</span
              >
              <h1 class="fund-hero-title mb-3">
                Business Plans & Funding Proposals That Open Doors
              </h1>
              <p class="fund-hero-sub mb-4">
                We write the documents that get your funding application taken
                seriously — bank loans, government grants, investor pitches, and
                more. Tailored to your business. Done for you.
              </p>
              <div class="d-flex flex-wrap" style="gap: 0.75rem">
                <Link
                  :href="route('contact')"
                  class="btn btn-warning btn-lg px-5 font-weight-bold"
                  style="color: #5c3d11"
                  ><i class="fas fa-comments mr-2"></i>Get a Free Quote</Link
                >
                <Link
                  :href="route('register')"
                  class="btn btn-outline-light btn-lg px-4"
                  ><i class="fas fa-user-plus mr-2"></i>Register Free</Link
                >
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Who needs this ─────────────────────────────── -->
      <section class="who-strip py-4">
        <div class="container">
          <div
            class="d-flex flex-wrap align-items-center justify-content-center"
            style="gap: 1.5rem"
          >
            <span
              class="who-label text-white font-weight-bold mr-3"
              style="opacity: 0.7; font-size: 0.85rem"
              >WHO IT'S FOR:</span
            >
            <span
              v-for="w in [
                'Startups seeking seed funding',
                'SMEs applying for bank loans',
                'Businesses applying for grants',
                'NGOs & CBOs needing donor proposals',
                'Entrepreneurs entering pitch competitions',
              ]"
              :key="w"
              class="who-tag"
              >{{ w }}</span
            >
          </div>
        </div>
      </section>

      <!-- ── Package cards ──────────────────────────────── -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-amber-soft mb-2 d-inline-block"
              ><i class="fas fa-th-large mr-1"></i>Our Packages</span
            >
            <h2 class="font-weight-bold" style="color: #5c3d11">
              Choose the Document You Need
            </h2>
            <p class="text-muted">
              All packages are tailored to your business — never generic
              templates.
            </p>
          </div>
          <div class="row">
            <div
              v-for="pkg in packages"
              :key="pkg.title"
              class="col-sm-6 col-lg-3 mb-4"
            >
              <div
                :class="[
                  'pkg-card h-100',
                  pkg.highlight ? 'pkg-card--highlighted' : '',
                ]"
              >
                <div
                  v-if="pkg.badge"
                  class="pkg-badge"
                  :style="{ background: pkg.colour }"
                >
                  {{ pkg.badge }}
                </div>
                <div
                  class="pkg-icon-wrap mb-3"
                  :style="{ background: pkg.colour + '18', color: pkg.colour }"
                >
                  <i :class="pkg.icon"></i>
                </div>
                <h5 class="font-weight-bold pkg-title mb-1">{{ pkg.title }}</h5>
                <p class="pkg-price mb-3">{{ pkg.price }}</p>
                <ul class="pkg-list mb-3">
                  <li v-for="inc in pkg.includes" :key="inc">
                    <i
                      class="fas fa-check mr-2"
                      :style="{ color: pkg.colour }"
                    ></i
                    >{{ inc }}
                  </li>
                </ul>
                <p class="pkg-ideal">
                  <i class="fas fa-bullseye mr-1"></i
                  ><strong>Ideal for:</strong> {{ pkg.idealFor }}
                </p>
                <Link
                  :href="route('contact')"
                  class="btn pkg-cta-btn btn-block mt-auto"
                  :style="{ background: pkg.colour }"
                >
                  <i class="fas fa-envelope mr-1"></i>Enquire
                </Link>
              </div>
            </div>
          </div>
          <div class="text-center mt-3">
            <p class="text-muted small">
              Need more than one?
              <Link
                :href="route('contact')"
                class="text-warning font-weight-bold"
                >Ask about bundle pricing →</Link
              >
            </p>
          </div>
        </div>
      </section>

      <!-- ── Our process ────────────────────────────────── -->
      <section class="py-5" style="background: #fdf8f1">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-amber-soft mb-2 d-inline-block"
              ><i class="fas fa-map-signs mr-1"></i>Our Process</span
            >
            <h2 class="font-weight-bold" style="color: #5c3d11">
              From Conversation to Completion
            </h2>
          </div>
          <div class="process-timeline">
            <div
              v-for="(step, i) in [
                {
                  icon: 'fas fa-comments',
                  title: 'Free Consultation',
                  body: 'We discuss your business, the funding you need, and which document will serve you best.',
                },
                {
                  icon: 'fas fa-database',
                  title: 'Data Gathering',
                  body: 'We collect your business information, financial data, and any existing documents via a structured brief.',
                },
                {
                  icon: 'fas fa-pen-nib',
                  title: 'Expert Writing',
                  body: 'Our writers and financial analysts produce a first draft to lender and funder standards.',
                },
                {
                  icon: 'fas fa-check-circle',
                  title: 'Review & Deliver',
                  body: 'You review, request revisions, and receive your final polished document ready for submission.',
                },
              ]"
              :key="step.title"
              :class="[
                'process-step',
                i % 2 === 0 ? 'process-step--left' : 'process-step--right',
              ]"
            >
              <div class="process-icon-col">
                <div class="process-circle">{{ i + 1 }}</div>
                <div v-if="i < 3" class="process-line"></div>
              </div>
              <div class="process-content">
                <div class="process-card">
                  <div
                    class="d-flex align-items-center mb-2"
                    style="gap: 0.75rem"
                  >
                    <i
                      :class="step.icon"
                      style="color: #c8892a; font-size: 1.2rem"
                    ></i>
                    <h6 class="font-weight-bold mb-0" style="color: #5c3d11">
                      {{ step.title }}
                    </h6>
                  </div>
                  <p class="text-muted small mb-0">{{ step.body }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── FAQ ────────────────────────────────────────── -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-amber-soft mb-2 d-inline-block"
              ><i class="fas fa-question-circle mr-1"></i>FAQs</span
            >
            <h2 class="font-weight-bold" style="color: #5c3d11">
              Frequently Asked Questions
            </h2>
          </div>
          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div v-for="(faq, i) in faqs" :key="i" class="faq-item">
                <button class="faq-q w-100 text-left" @click="toggleFaq(i)">
                  <span>{{ faq.q }}</span>
                  <i
                    :class="['fas', openFaq === i ? 'fa-minus' : 'fa-plus']"
                    style="color: #c8892a"
                  ></i>
                </button>
                <div v-show="openFaq === i" class="faq-a">{{ faq.a }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA ─────────────────────────────────────────── -->
      <section class="fund-cta py-5">
        <div class="container text-center">
          <h2 class="font-weight-bold text-white mb-3">
            Let's get you funded.
          </h2>
          <p class="text-white-50 mb-4">
            The first conversation is free. Tell us what you're applying for and
            we'll take it from there.
          </p>
          <div
            class="d-flex justify-content-center flex-wrap"
            style="gap: 1rem"
          >
            <Link
              :href="route('contact')"
              class="btn btn-warning btn-lg px-5 font-weight-bold"
              style="color: #5c3d11"
              ><i class="fas fa-comments mr-2"></i>Talk to Our Team</Link
            >
            <Link
              :href="route('register')"
              class="btn btn-outline-light btn-lg px-5"
              ><i class="fas fa-user-plus mr-2"></i>Register Free</Link
            >
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
  border-color: #e8ddd0 !important;
  padding: 1.15rem 0 1rem;
}
.footer-link {
  color: var(--brand-secondary-dark);
  font-size: 0.94rem;
  font-weight: 600;
  text-decoration: none;
}
.footer-link:hover {
  text-decoration: underline;
}
.footer-bottom {
  border-top: 1px solid #f0e8db;
}
.breadcrumb-dark {
  background: transparent;
  padding: 0;
}
.breadcrumb-dark .breadcrumb-item + .breadcrumb-item::before {
  color: rgba(255, 255, 255, 0.4);
}

/* Hero */
.fund-hero {
  background: var(--brand-secondary-dark);
  min-height: 300px;
  display: flex;
  align-items: center;
}
.fund-pill {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.4em 1em;
  border-radius: 99px;
}
.fund-hero-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
  max-width: 640px;
}
.fund-hero-sub {
  font-size: 1.02rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 560px;
}

/* Who strip */
.who-strip {
  background: #5c3d11;
}
.who-tag {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.9em;
  border-radius: 99px;
}

/* Packages */
.badge-amber-soft {
  background: rgba(200, 137, 42, 0.12);
  color: #5c3d11;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}
.pkg-card {
  background: #fff;
  border: 1px solid #e8ddd0;
  border-radius: 0.75rem;
  padding: 1.75rem 1.5rem;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: box-shadow 0.2s;
}
.pkg-card:hover {
  box-shadow: 0 6px 24px rgba(200, 137, 42, 0.12);
}
.pkg-card--highlighted {
  border-color: #c8892a;
  box-shadow: 0 6px 24px rgba(200, 137, 42, 0.18);
}
.pkg-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: #c8892a;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25em 0.85em;
  border-radius: 99px;
  white-space: nowrap;
}
.pkg-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}
.pkg-title {
  color: #5c3d11;
  font-size: 1.1rem;
}
.pkg-price {
  font-size: 1rem;
  font-weight: 700;
  color: #c8892a;
}
.pkg-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.82rem;
  color: #555;
}
.pkg-list li {
  padding: 0.28rem 0;
  border-bottom: 1px solid #f5ede0;
}
.pkg-list li:last-child {
  border-bottom: none;
}
.pkg-ideal {
  font-size: 0.78rem;
  color: #888;
  margin-bottom: 0.75rem;
}
.pkg-cta-btn {
  color: #fff;
  border: none;
  font-weight: 700;
  border-radius: 0.5rem;
  padding: 0.6rem;
}
.pkg-cta-btn:hover {
  opacity: 0.87;
  color: #fff;
}

/* Process timeline */
.process-timeline {
  max-width: 640px;
  margin: 0 auto;
}
.process-step {
  display: flex;
  gap: 1.25rem;
  margin-bottom: 0;
}
.process-icon-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}
.process-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--brand-secondary-dark);
  color: #fff;
  font-weight: 800;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.process-line {
  width: 2px;
  flex: 1;
  background: var(--brand-secondary);
  min-height: 32px;
  margin: 4px 0;
}
.process-content {
  flex: 1;
  padding-bottom: 1.5rem;
}
.process-card {
  background: #fff;
  border: 1px solid #e8ddd0;
  border-radius: 0.75rem;
  padding: 1.25rem;
}

/* FAQ */
.faq-item {
  border-bottom: 1px solid #f0e8db;
}
.faq-q {
  background: none;
  border: none;
  padding: 1.1rem 0;
  font-weight: 600;
  font-size: 0.95rem;
  color: #5c3d11;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}
.faq-q:hover {
  color: #c8892a;
}
.faq-a {
  padding: 0 0 1.25rem;
  font-size: 0.9rem;
  color: #555;
  line-height: 1.6;
}

/* CTA */
.fund-cta {
  background: var(--brand-secondary-dark);
}
</style>
