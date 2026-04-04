<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

defineProps({ canLogin: { type: Boolean }, canRegister: { type: Boolean } });
const currentYear = new Date().getFullYear();

const services = [
  {
    icon: "fas fa-id-card",
    title: "Company Profile Preparation",
    price: "KES 15,000",
    imgSide: "right",
    colour: "#1a7a40",
    points: [
      "Business registration & certification docs",
      "Tax Compliance Certificate (TCC) guidance",
      "CR12 and director KYC documentation",
      "Capability statement write-up",
      "Past performance references compilation",
      "Professional binding and presentation",
    ],
    body: "A credible, complete company profile is the foundation of every tender bid. Our team gathers, formats, and compiles all statutory and capability documents into a professional dossier that institutional evaluators trust.",
  },
  {
    icon: "fas fa-file-contract",
    title: "Tender Document Preparation",
    price: "KES 10,000 – 50,000+",
    imgSide: "left",
    colour: "#1a5fa0",
    points: [
      "Mandatory forms filled accurately",
      "Technical proposal writing",
      "Financial proposal & BOQ preparation",
      "Compliance checklist cross-check",
      "Supporting annexures formatted",
      "Submission-ready package",
    ],
    body: "Technical errors, missing annexures, or a poorly written technical proposal are the top reasons bids are rejected. We prepare every section of your tender response to spec — so yours stands out for the right reasons.",
  },
  {
    icon: "fas fa-clipboard-list",
    title: "Prequalification Support",
    price: "from KES 8,000",
    imgSide: "right",
    colour: "#6b46c1",
    points: [
      "EOI and prequalification forms",
      "Category-specific capability write-ups",
      "Financial capacity documentation",
      "Reference letters & past contracts",
      "Certification and accreditation guidance",
      "Submission and confirmation tracking",
    ],
    body: "Prequalification is the gateway to institutional tender lists. Our specialists ensure your EOI or PQ submission meets every criterion and positions your firm competitively for the approved supplier register.",
  },
  {
    icon: "fas fa-check-double",
    title: "Compliance Checks",
    price: "Included with submission packages",
    imgSide: "left",
    colour: "#b07d2e",
    points: [
      "Line-by-line tender requirements review",
      "Mandatory document verification",
      "Form accuracy and completeness audit",
      "PPRA / AGPO compliance check",
      "Deadline and submission format check",
      "Final sign-off before submission",
    ],
    body: "Before any document leaves our hands, our compliance team cross-references every requirement in the tender notice and ITT. We catch every omission or error that could disqualify your bid.",
  },
];
</script>

<template>
  <Head title="Compliance & Bid Support – Tender Plug" />
  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="services"
    />

    <main>
      <!-- ── Hero ───────────────────────────────────────── -->
      <section class="bid-hero">
        <div class="container py-5">
          <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb breadcrumb-dark mb-0">
              <li class="breadcrumb-item">
                <Link :href="route('services')" class="text-white-50"
                  >Services</Link
                >
              </li>
              <li class="breadcrumb-item active text-white">
                Compliance & Bid Support
              </li>
            </ol>
          </nav>
          <div class="row align-items-center">
            <div class="col-lg-7">
              <span class="bid-hero-pill mb-3 d-inline-block"
                ><i class="fas fa-gavel mr-2"></i>Professional Bid
                Services</span
              >
              <h1 class="bid-hero-title mb-3">
                Win More Tenders With Expert Bid Support
              </h1>
              <p class="bid-hero-sub mb-4">
                We handle the paperwork so you can focus on delivering. From
                company profile to final submission — our team ensures your bids
                are compliant, complete, and competitive.
              </p>
              <div class="d-flex flex-wrap" style="gap: 0.75rem">
                <Link
                  :href="route('contact')"
                  class="btn btn-light btn-lg px-5 font-weight-bold"
                  style="color: #1a4a20"
                  ><i class="fas fa-envelope mr-2"></i>Get a Free Quote</Link
                >
                <Link
                  :href="route('register')"
                  class="btn btn-outline-light btn-lg px-4"
                  ><i class="fas fa-user-plus mr-2"></i>Register Free</Link
                >
              </div>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0">
              <div class="hero-stats-grid">
                <div class="hero-stat">
                  <span class="hstat-num">95%</span
                  ><span class="hstat-label">Compliance Rate</span>
                </div>
                <div class="hero-stat">
                  <span class="hstat-num">500+</span
                  ><span class="hstat-label">Bids Submitted</span>
                </div>
                <div class="hero-stat">
                  <span class="hstat-num">48–72h</span
                  ><span class="hstat-label">Avg. Turnaround</span>
                </div>
                <div class="hero-stat">
                  <span class="hstat-num">4</span
                  ><span class="hstat-label">Core Services</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Alternating service bands ─────────────────── -->
      <div v-for="(svc, i) in services" :key="svc.title">
        <section
          :class="[
            'svc-band py-5',
            i % 2 === 0 ? 'svc-band--light' : 'svc-band--white',
          ]"
        >
          <div class="container">
            <div
              :class="[
                'row align-items-center',
                svc.imgSide === 'left' ? 'flex-row-reverse' : '',
              ]"
            >
              <div class="col-lg-6 mb-4 mb-lg-0">
                <span
                  class="svc-band-badge mb-3 d-inline-block"
                  :style="{ background: svc.colour + '18', color: svc.colour }"
                >
                  <i :class="svc.icon + ' mr-1'"></i>{{ svc.title }}
                </span>
                <h2 class="font-weight-bold mb-2 svc-band-title">
                  {{ svc.title }}
                </h2>
                <p class="price-tag mb-3">
                  <i class="fas fa-tag mr-1"></i>{{ svc.price }}
                </p>
                <p class="text-muted mb-4">{{ svc.body }}</p>
                <Link
                  :href="route('contact')"
                  class="btn btn-lg px-4 enquire-btn"
                  :style="{ background: svc.colour }"
                >
                  <i class="fas fa-comments mr-2"></i>Enquire About This Service
                </Link>
              </div>
              <div class="col-lg-6">
                <div
                  class="checklist-card"
                  :style="{ borderLeftColor: svc.colour }"
                >
                  <h6
                    class="font-weight-bold mb-3"
                    :style="{ color: svc.colour }"
                  >
                    <i class="fas fa-list-ul mr-2"></i>What's Included
                  </h6>
                  <ul class="check-list">
                    <li v-for="p in svc.points" :key="p">
                      <i
                        class="fas fa-check-circle mr-2"
                        :style="{ color: svc.colour }"
                      ></i
                      >{{ p }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>
        <div v-if="i < services.length - 1" class="band-divider"></div>
      </div>

      <!-- ── Why choose us ──────────────────────────────── -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-soft-green mb-2 d-inline-block"
              ><i class="fas fa-star mr-1"></i>Why Choose Us</span
            >
            <h2 class="font-weight-bold" style="color: #1a4a20">
              What Sets Our Bid Support Apart
            </h2>
          </div>
          <div class="row justify-content-center">
            <div
              v-for="w in [
                {
                  icon: 'fas fa-user-graduate',
                  title: 'Experienced Writers',
                  text: 'Our team has written hundreds of successful bids across construction, IT, services, and supplies.',
                },
                {
                  icon: 'fas fa-map-marked-alt',
                  title: 'Local Knowledge',
                  text: 'Deep familiarity with Kenyan procurement law, PPRA regulations, and institutional requirements.',
                },
                {
                  icon: 'fas fa-clock',
                  title: 'Fast Turnaround',
                  text: 'Most packages completed within 48–72 hours. Rush services available for urgent deadlines.',
                },
                {
                  icon: 'fas fa-redo',
                  title: 'Unlimited Revisions',
                  text: 'We revise until you\'re satisfied. Your success is our success.',
                },
              ]"
              :key="w.title"
              class="col-sm-6 col-lg-3 mb-4"
            >
              <div class="why-card text-center h-100">
                <div class="why-icon mx-auto mb-3"><i :class="w.icon"></i></div>
                <h6 class="font-weight-bold mb-2" style="color: #1a4a20">
                  {{ w.title }}
                </h6>
                <p class="text-muted small mb-0">{{ w.text }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA ─────────────────────────────────────────── -->
      <section class="bid-cta py-5">
        <div class="container text-center">
          <h2 class="text-white font-weight-bold mb-3">
            Your next tender is waiting.
          </h2>
          <p class="text-white-50 mb-4">
            Tell us about the tender and we'll tell you what it'll take to win
            it.
          </p>
          <Link
            :href="route('contact')"
            class="btn btn-light btn-lg px-5 font-weight-bold"
            style="color: #1a4a20"
            ><i class="fas fa-comments mr-2"></i>Start a Conversation</Link
          >
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
.breadcrumb-dark {
  background: transparent;
  padding: 0;
}
.breadcrumb-dark .breadcrumb-item + .breadcrumb-item::before {
  color: rgba(255, 255, 255, 0.4);
}
.bid-hero {
  background: linear-gradient(135deg, #1a4a20 0%, #2d7a38 50%, #34a853 100%);
  min-height: 320px;
  display: flex;
  align-items: center;
}
.bid-hero-pill {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.4em 1em;
  border-radius: 99px;
  letter-spacing: 0.03em;
}
.bid-hero-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
}
.bid-hero-sub {
  font-size: 1.02rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 540px;
}
.hero-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.hero-stat {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 0.75rem;
  padding: 1.25rem 1rem;
  text-align: center;
}
.hstat-num {
  display: block;
  font-size: 1.8rem;
  font-weight: 800;
  color: #fff;
}
.hstat-label {
  font-size: 0.78rem;
  color: rgba(255, 255, 255, 0.75);
  font-weight: 600;
}
.svc-band--light {
  background: #f7fdf9;
}
.svc-band--white {
  background: #fff;
}
.svc-band-badge {
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.35em 0.9em;
  border-radius: 99px;
}
.svc-band-title {
  font-size: 1.6rem;
  color: #1a2e1e;
}
.price-tag {
  font-size: 1rem;
  font-weight: 700;
  color: #6c757d;
}
.enquire-btn {
  color: #fff;
  border: none;
  font-weight: 700;
  border-radius: 0.5rem;
}
.enquire-btn:hover {
  opacity: 0.87;
  color: #fff;
}
.checklist-card {
  background: #fff;
  border-left: 4px solid #28a745;
  border-radius: 0.75rem;
  padding: 1.75rem 1.5rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.check-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.check-list li {
  padding: 0.45rem 0;
  font-size: 0.92rem;
  color: #333;
  border-bottom: 1px solid #f0f0f0;
}
.check-list li:last-child {
  border-bottom: none;
}
.band-divider {
  height: 0;
  border-bottom: 1px dashed #ddd;
}
.badge-soft-green {
  background: rgba(40, 167, 69, 0.1);
  color: #1a4a20;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}
.why-card {
  background: #f7fdf9;
  border: 1px solid #e0f0e6;
  border-radius: 0.75rem;
  padding: 1.75rem 1.25rem;
}
.why-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(40, 167, 69, 0.12);
  color: #1a7a40;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}
.bid-cta {
  background: linear-gradient(135deg, #1a4a20 0%, #0d2c14 100%);
}
</style>
