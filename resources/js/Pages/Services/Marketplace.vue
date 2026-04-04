<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

defineProps({ canLogin: { type: Boolean }, canRegister: { type: Boolean } });
const currentYear = new Date().getFullYear();

const activeRole = ref("buyer");

const categories = [
  { icon: "fas fa-boxes", label: "Suppliers", count: "120+" },
  { icon: "fas fa-hard-hat", label: "Contractors", count: "85+" },
  { icon: "fas fa-laptop-code", label: "IT & Tech", count: "60+" },
  { icon: "fas fa-broom", label: "Cleaning & FM", count: "40+" },
  { icon: "fas fa-shield-alt", label: "Security", count: "35+" },
  { icon: "fas fa-truck", label: "Logistics", count: "50+" },
  { icon: "fas fa-user-tie", label: "Consulting", count: "70+" },
  { icon: "fas fa-tools", label: "Maintenance", count: "55+" },
];

const buyerSteps = [
  {
    icon: "fas fa-search",
    title: "Search the Directory",
    body: "Browse by category, county, or business name. Use filters to narrow down verified suppliers by sector or certification.",
  },
  {
    icon: "fas fa-eye",
    title: "View Business Profiles",
    body: "See each business's capabilities, certifications, past work, and contact details — all verified by Tender Plug.",
  },
  {
    icon: "fas fa-envelope-open-text",
    title: "Make Direct Contact",
    body: "Reach out directly through the platform or via the listed contact. No middle-man, no hidden fees.",
  },
];

const sellerSteps = [
  {
    icon: "fas fa-user-plus",
    title: "Register Your Business",
    body: "Create your free Tender Plug account. Complete your business profile with sector, certifications, county, and key capabilities.",
  },
  {
    icon: "fas fa-certificate",
    title: "Verification",
    body: "Upload your statutory documents for verification. Verified status gives your profile a trust badge visible to all buyers.",
  },
  {
    icon: "fas fa-broadcast-tower",
    title: "Get Discovered",
    body: "Your profile is live and searchable by institutions, government agencies, and other businesses across Kenya — 24/7.",
  },
];
</script>

<template>
  <Head title="B2B Marketplace – Tender Plug" />
  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="services"
    />

    <main>
      <!-- ── Hero split ─────────────────────────────────── -->
      <section class="mkt-hero">
        <div class="container py-5">
          <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb breadcrumb-dark mb-0">
              <li class="breadcrumb-item">
                <Link :href="route('services')" class="text-white-50"
                  >Services</Link
                >
              </li>
              <li class="breadcrumb-item active text-white">B2B Marketplace</li>
            </ol>
          </nav>
          <h1 class="mkt-hero-title mb-3">Kenya's Verified B2B Marketplace</h1>
          <p class="mkt-hero-sub mb-5">
            Buyers find trusted suppliers. Suppliers get discovered. Direct
            connections — no commissions, no gatekeeping.
          </p>

          <!-- Buyer / Seller toggle -->
          <div class="role-toggle mb-4">
            <button
              :class="[
                'role-btn',
                activeRole === 'buyer' ? 'role-btn--active' : '',
              ]"
              @click="activeRole = 'buyer'"
            >
              <i class="fas fa-shopping-cart mr-2"></i>I'm a Buyer
            </button>
            <button
              :class="[
                'role-btn',
                activeRole === 'seller' ? 'role-btn--active' : '',
              ]"
              @click="activeRole = 'seller'"
            >
              <i class="fas fa-store mr-2"></i>I'm a Supplier
            </button>
          </div>

          <!-- Buyer panel -->
          <div v-if="activeRole === 'buyer'" class="role-panel buyer-panel">
            <h4 class="font-weight-bold mb-2 text-white">
              Find Verified Partners
            </h4>
            <p class="text-white-50 mb-3">
              Search 500+ verified Kenyan businesses — suppliers, contractors,
              and service providers.
            </p>
            <div class="d-flex flex-wrap" style="gap: 0.75rem">
              <Link
                :href="route('register')"
                class="btn btn-light btn-lg px-4 font-weight-bold"
                style="color: #1a3a5c"
                ><i class="fas fa-search mr-2"></i>Browse the Directory</Link
              >
              <Link :href="route('contact')" class="btn btn-outline-light px-4"
                ><i class="fas fa-envelope mr-2"></i>Talk to Us</Link
              >
            </div>
          </div>

          <!-- Seller panel -->
          <div v-if="activeRole === 'seller'" class="role-panel seller-panel">
            <h4 class="font-weight-bold mb-2 text-white">
              Get Your Business Discovered
            </h4>
            <p class="text-white-50 mb-3">
              List your business for free and get connected with institutions
              and buyers across Kenya.
            </p>
            <div class="d-flex flex-wrap" style="gap: 0.75rem">
              <Link
                :href="route('register')"
                class="btn btn-warning btn-lg px-4 font-weight-bold"
                style="color: #1a3a5c"
                ><i class="fas fa-user-plus mr-2"></i>List My Business
                Free</Link
              >
              <Link :href="route('contact')" class="btn btn-outline-light px-4"
                ><i class="fas fa-envelope mr-2"></i>Talk to Us</Link
              >
            </div>
          </div>
        </div>
      </section>

      <!-- ── Category directory grid ─────────────────────── -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-blue-soft mb-2 d-inline-block"
              ><i class="fas fa-th-large mr-1"></i>Directory Categories</span
            >
            <h2 class="font-weight-bold" style="color: #1a3a5c">
              Browse by Sector
            </h2>
            <p class="text-muted">
              Over 500 verified businesses across 8+ sectors.
            </p>
          </div>
          <div class="row">
            <div
              v-for="cat in categories"
              :key="cat.label"
              class="col-6 col-sm-4 col-lg-3 mb-3"
            >
              <div class="cat-card text-center">
                <div class="cat-icon mx-auto mb-2">
                  <i :class="cat.icon"></i>
                </div>
                <div class="cat-label">{{ cat.label }}</div>
                <div class="cat-count">{{ cat.count }} listed</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── How it works — split buyer/seller ──────────── -->
      <section class="py-5" style="background: #f0f6ff">
        <div class="container">
          <div class="text-center mb-5">
            <span class="badge-blue-soft mb-2 d-inline-block"
              ><i class="fas fa-exchange-alt mr-1"></i>How It Works</span
            >
            <h2 class="font-weight-bold" style="color: #1a3a5c">
              For Buyers & Suppliers
            </h2>
          </div>
          <div class="row">
            <!-- Buyers column -->
            <div class="col-lg-6 mb-4 mb-lg-0">
              <div class="how-column how-column--buyer">
                <div class="how-col-header buyer-header">
                  <i class="fas fa-shopping-cart mr-2"></i>For Buyers &
                  Institutions
                </div>
                <div class="how-col-body">
                  <div
                    v-for="(s, i) in buyerSteps"
                    :key="s.title"
                    class="how-step d-flex"
                    style="gap: 1rem"
                  >
                    <div class="how-step-num buyer-num flex-shrink-0">
                      {{ i + 1 }}
                    </div>
                    <div>
                      <div
                        class="font-weight-bold mb-1"
                        style="font-size: 0.95rem; color: #1a3a5c"
                      >
                        <i :class="s.icon + ' mr-1'"></i>{{ s.title }}
                      </div>
                      <p class="text-muted small mb-0">{{ s.body }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Suppliers column -->
            <div class="col-lg-6">
              <div class="how-column how-column--seller">
                <div class="how-col-header seller-header">
                  <i class="fas fa-store mr-2"></i>For Suppliers & Contractors
                </div>
                <div class="how-col-body">
                  <div
                    v-for="(s, i) in sellerSteps"
                    :key="s.title"
                    class="how-step d-flex"
                    style="gap: 1rem"
                  >
                    <div class="how-step-num seller-num flex-shrink-0">
                      {{ i + 1 }}
                    </div>
                    <div>
                      <div
                        class="font-weight-bold mb-1"
                        style="font-size: 0.95rem; color: #1a3a5c"
                      >
                        <i :class="s.icon + ' mr-1'"></i>{{ s.title }}
                      </div>
                      <p class="text-muted small mb-0">{{ s.body }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Trust banner ────────────────────────────────── -->
      <section class="trust-banner py-4">
        <div class="container">
          <div class="row text-center">
            <div
              v-for="t in [
                [
                  'fas fa-certificate',
                  'Verified Profiles',
                  'All businesses go through our verification process',
                ],
                [
                  'fas fa-ban',
                  'Zero Commission',
                  'Direct contact — we don\'t take a cut',
                ],
                [
                  'fas fa-map-marker-alt',
                  'Nationwide',
                  'Businesses from all 47 counties',
                ],
                [
                  'fas fa-lock',
                  'Secure',
                  'Your data is encrypted and never sold',
                ],
              ]"
              :key="t[0]"
              class="col-6 col-lg-3 mb-3 mb-lg-0"
            >
              <i :class="t[0]" class="trust-icon mb-2"></i>
              <div class="trust-label">{{ t[1] }}</div>
              <div class="trust-sub">{{ t[2] }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA ─────────────────────────────────────────── -->
      <section class="mkt-cta py-5">
        <div class="container">
          <div class="row g-0">
            <div class="col-lg-6 cta-half cta-half--buyer p-5 text-center">
              <i class="fas fa-shopping-cart cta-icon mb-3"></i>
              <h3 class="font-weight-bold text-white mb-2">Need a Supplier?</h3>
              <p class="text-white-50 mb-4">
                Browse our verified directory to find the right partner for your
                next procurement.
              </p>
              <Link
                :href="route('register')"
                class="btn btn-light btn-lg px-5 font-weight-bold"
                style="color: #1a3a5c"
                >Start Searching</Link
              >
            </div>
            <div class="col-lg-6 cta-half cta-half--seller p-5 text-center">
              <i class="fas fa-store cta-icon mb-3"></i>
              <h3 class="font-weight-bold text-white mb-2">
                Want to be Found?
              </h3>
              <p class="text-white-50 mb-4">
                Register your business and get visible to institutions and
                buyers across Kenya — for free.
              </p>
              <Link
                :href="route('register')"
                class="btn btn-warning btn-lg px-5 font-weight-bold"
                style="color: #1a3a5c"
                >List My Business</Link
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
  border-color: #dce8f5 !important;
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
  border-top: 1px solid #dce8f5;
}
.breadcrumb-dark {
  background: transparent;
  padding: 0;
}
.breadcrumb-dark .breadcrumb-item + .breadcrumb-item::before {
  color: rgba(255, 255, 255, 0.4);
}

/* Hero */
.mkt-hero {
  background: linear-gradient(135deg, #0d1f3c 0%, #1a3a5c 60%, #2460a0 100%);
  min-height: 380px;
  display: flex;
  align-items: center;
}
.mkt-hero-title {
  font-size: 2.4rem;
  font-weight: 800;
  color: #fff;
}
.mkt-hero-sub {
  font-size: 1.05rem;
  color: rgba(255, 255, 255, 0.78);
  max-width: 540px;
}

/* Role toggle */
.role-toggle {
  display: inline-flex;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 99px;
  padding: 4px;
  gap: 4px;
}
.role-btn {
  padding: 0.6rem 1.75rem;
  border-radius: 99px;
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.7);
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.18s;
}
.role-btn--active {
  background: #fff;
  color: #1a3a5c;
}
.role-panel {
  border-radius: 0.75rem;
  padding: 1.75rem 2rem;
  max-width: 560px;
}
.buyer-panel {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.seller-panel {
  background: rgba(234, 179, 8, 0.1);
  border: 1px solid rgba(234, 179, 8, 0.3);
}

/* Categories */
.badge-blue-soft {
  background: rgba(41, 128, 201, 0.12);
  color: #1a3a5c;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}
.cat-card {
  background: #f4f8fd;
  border: 1px solid #d8e8f8;
  border-radius: 0.75rem;
  padding: 1.25rem 0.75rem;
  cursor: pointer;
  transition: all 0.18s;
}
.cat-card:hover {
  background: #1a3a5c;
}
.cat-card:hover .cat-icon i,
.cat-card:hover .cat-label,
.cat-card:hover .cat-count {
  color: #fff !important;
}
.cat-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(41, 128, 201, 0.12);
  color: #2460a0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.18s;
}
.cat-label {
  font-weight: 700;
  font-size: 0.88rem;
  color: #1a3a5c;
  margin-bottom: 0.15rem;
}
.cat-count {
  font-size: 0.75rem;
  color: #6c757d;
}

/* How it works */
.how-column {
  border-radius: 0.75rem;
  overflow: hidden;
  height: 100%;
}
.how-col-header {
  padding: 1rem 1.5rem;
  font-weight: 700;
  font-size: 0.95rem;
  color: #fff;
}
.buyer-header {
  background: #1a3a5c;
}
.seller-header {
  background: #2460a0;
}
.how-col-body {
  background: #fff;
  padding: 1.5rem;
  border: 1px solid #d8e8f8;
  border-top: none;
  border-radius: 0 0 0.75rem 0.75rem;
}
.how-step {
  padding: 0.75rem 0;
  border-bottom: 1px solid #f0f0f0;
}
.how-step:last-child {
  border-bottom: none;
}
.how-step-num {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.95rem;
}
.buyer-num {
  background: rgba(26, 58, 92, 0.1);
  color: #1a3a5c;
}
.seller-num {
  background: rgba(36, 96, 160, 0.1);
  color: #2460a0;
}

/* Trust banner */
.trust-banner {
  background: #1a3a5c;
}
.trust-icon {
  font-size: 1.6rem;
  color: #64b5f6;
  display: block;
}
.trust-label {
  font-weight: 700;
  color: #fff;
  font-size: 0.9rem;
}
.trust-sub {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
}

/* CTA */
.mkt-cta .container {
  padding: 0;
}
.cta-half {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.cta-half--buyer {
  background: linear-gradient(135deg, #1a3a5c, #2460a0);
}
.cta-half--seller {
  background: linear-gradient(135deg, #0d2c52, #1a5fa0);
}
.cta-icon {
  font-size: 2.5rem;
  color: rgba(255, 255, 255, 0.6);
  display: block;
}
</style>
