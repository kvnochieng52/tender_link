<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
});

const currentYear = new Date().getFullYear();

const team = [
  {
    name: "James Mwangi",
    role: "Chief Executive Officer",
    bio: "10+ years leading procurement and public-sector technology initiatives across East Africa.",
    avatar:
      "https://ui-avatars.com/api/?name=James+Mwangi&background=28a745&color=fff&size=128",
  },
  {
    name: "Winnie Achieng",
    role: "Head of Operations",
    bio: "Expert in tender compliance, supplier onboarding, and end-to-end procurement workflow management.",
    avatar:
      "https://ui-avatars.com/api/?name=Winnie+Achieng&background=28a745&color=fff&size=128",
  },
  {
    name: "Brian Kiprotich",
    role: "Lead Engineer",
    bio: "Full-stack developer focused on building secure, scalable portals for government and NGO procurement.",
    avatar:
      "https://ui-avatars.com/api/?name=Brian+Kiprotich&background=28a745&color=fff&size=128",
  },
  {
    name: "Amina Hassan",
    role: "Client Relations",
    bio: "Dedicated to ensuring every supplier and institution gets timely, professional support on the platform.",
    avatar:
      "https://ui-avatars.com/api/?name=Amina+Hassan&background=28a745&color=fff&size=128",
  },
];

const stats = [
  { value: "5,000+", label: "Registered Suppliers" },
  { value: "1,200+", label: "Tenders Published" },
  { value: "47", label: "Counties Covered" },
  { value: "98%", label: "Client Satisfaction" },
];

const values = [
  {
    icon: "fas fa-handshake",
    title: "Integrity",
    text: "We uphold the highest ethical standards in every tender process.",
  },
  {
    icon: "fas fa-shield-alt",
    title: "Transparency",
    text: "Every step is auditable, open, and traceable for all stakeholders.",
  },
  {
    icon: "fas fa-bolt",
    title: "Efficiency",
    text: "Technology-driven workflows cut procurement timelines dramatically.",
  },
  {
    icon: "fas fa-users",
    title: "Inclusivity",
    text: "We empower MSMEs, youth, and women businesses to compete fairly.",
  },
  {
    icon: "fas fa-chart-line",
    title: "Innovation",
    text: "Continuous improvement backed by data and stakeholder feedback.",
  },
  {
    icon: "fas fa-leaf",
    title: "Sustainability",
    text: "We promote green procurement and responsible supplier practices.",
  },
];
</script>

<template>
  <Head title="About Us – Tender Plug" />

  <div class="landing-page bg-light">
    <!-- ── Navbar ──────────────────────────────────────────── -->
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="about"
    />
    <!-- ── /Navbar ─────────────────────────────────────────── -->

    <main class="pb-5">
      <!-- Hero banner -->
      <section class="about-hero">
        <div class="about-hero-overlay">
          <div class="container text-center">
            <h1 class="about-hero-title">About Tender Plug</h1>
            <p class="about-hero-sub">
              Kenya's most trusted procurement portal — connecting institutions
              with verified suppliers.
            </p>
            <Link
              :href="route('tenders.search')"
              class="btn btn-success btn-lg px-5 mt-2"
            >
              <i class="fas fa-search mr-2"></i>Browse Tenders
            </Link>
          </div>
        </div>
      </section>

      <!-- Who we are -->
      <section class="py-5">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
              <img
                src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80"
                alt="Our team"
                class="img-fluid rounded shadow-sm"
              />
            </div>
            <div class="col-lg-6">
              <span class="badge badge-success-soft mb-2"
                ><i class="fas fa-info-circle mr-1"></i>Who We Are</span
              >
              <h2 class="font-weight-bold mb-3" style="color: #1a3a22">
                Empowering Kenya's Procurement Ecosystem
              </h2>
              <p class="text-muted">
                Tender Plug is a technology-driven procurement portal that
                bridges the gap between government entities, NGOs, private
                institutions, and qualified suppliers across Kenya. We simplify
                tender discovery, submission, and evaluation — for everyone from
                large corporations to small community enterprises.
              </p>
              <p class="text-muted">
                Founded with a mission to cut corruption, reduce delays, and
                democratize access to public procurement, our platform currently
                serves institutions and suppliers in all 47 counties.
              </p>
              <div class="d-flex flex-wrap mt-3" style="gap: 0.75rem">
                <Link
                  :href="route('services')"
                  class="btn btn-success btn-sm px-4"
                  >Our Services</Link
                >
                <Link
                  :href="route('contact')"
                  class="btn btn-outline-success btn-sm px-4"
                  >Contact Us</Link
                >
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats bar -->
      <section class="stats-bar py-4">
        <div class="container">
          <div class="row text-center">
            <div
              v-for="stat in stats"
              :key="stat.label"
              class="col-6 col-md-3 mb-3 mb-md-0"
            >
              <div class="stat-item">
                <span class="stat-value">{{ stat.value }}</span>
                <span class="stat-label d-block">{{ stat.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Our values -->
      <section class="py-5">
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-star mr-1"></i>Our Values</span
            >
            <h2 class="font-weight-bold" style="color: #1a3a22">
              What Guides Us
            </h2>
            <p class="text-muted mx-auto" style="max-width: 520px">
              Every feature we build and every decision we make is rooted in
              these core principles.
            </p>
          </div>
          <div class="row">
            <div
              v-for="val in values"
              :key="val.title"
              class="col-sm-6 col-lg-4 mb-4"
            >
              <div class="value-card h-100">
                <div class="value-icon-wrap mb-3">
                  <i :class="val.icon"></i>
                </div>
                <h5 class="font-weight-bold mb-2">{{ val.title }}</h5>
                <p class="text-muted small mb-0">{{ val.text }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="about-cta py-5">
        <div class="container text-center">
          <h2 class="font-weight-bold text-white mb-3">
            Ready to find your next opportunity?
          </h2>
          <p class="text-white-50 mb-4">
            Join thousands of suppliers and institutions already on Tender Plug.
          </p>
          <div
            class="d-flex justify-content-center flex-wrap"
            style="gap: 1rem"
          >
            <Link
              :href="route('register')"
              class="btn btn-light btn-lg px-5 font-weight-bold"
              style="color: #28a745"
            >
              <i class="fas fa-user-plus mr-2"></i>Register Free
            </Link>
            <Link
              :href="route('tenders.search')"
              class="btn btn-outline-light btn-lg px-5"
            >
              <i class="fas fa-search mr-2"></i>Browse Tenders
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
  border-color: #dfe5e1 !important;
  padding: 1.15rem 0 1rem;
}
.footer-links {
  gap: 0.35rem;
}
.footer-link {
  color: #1f8f53;
  font-size: 0.94rem;
  font-weight: 600;
  text-decoration: none;
}
.footer-link:hover {
  color: #28a745;
  text-decoration: underline;
}
.footer-bottom {
  border-top: 1px solid #ebf1ed;
}

/* ── Hero ─────────── */
.about-hero {
  background: url("https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80")
    center/cover no-repeat;
  min-height: 380px;
  display: flex;
  align-items: center;
}
.about-hero-overlay {
  width: 100%;
  background: rgba(10, 40, 20, 0.62);
  min-height: 380px;
  display: flex;
  align-items: center;
  padding: 3rem 0;
}
.about-hero-title {
  font-size: 2.6rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 0.5rem;
}
.about-hero-sub {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 580px;
  margin: 0 auto;
}

/* ── Stats ──────────── */
.stats-bar {
  background: #1a3a22;
}
.stat-item {
  padding: 1rem 0.5rem;
}
.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  display: block;
  line-height: 1;
  margin-bottom: 0.35rem;
}
.stat-label {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ── Values ──────────── */
.value-card {
  background: #fff;
  border: 1px solid #e9f5ed;
  border-radius: 0.75rem;
  padding: 1.75rem 1.5rem;
  transition: box-shadow 0.2s;
}
.value-card:hover {
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.12);
}
.value-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(40, 167, 69, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: #28a745;
}

/* ── Team ───────────── */
.team-card {
  background: #fff;
  border: 1px solid #e9f5ed;
  border-radius: 0.75rem;
  padding: 2rem 1.25rem;
  transition: box-shadow 0.2s;
}
.team-card:hover {
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.12);
}
.team-avatar {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #28a745;
}

/* ── CTA ────────────── */
.about-cta {
  background: linear-gradient(135deg, #1a5c2e 0%, #28a745 100%);
}

/* ── Badge soft ─────── */
.badge-success-soft {
  background: rgba(40, 167, 69, 0.12);
  color: #1a6130;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}

@media (max-width: 767px) {
  .about-hero-title {
    font-size: 1.8rem;
  }
}
</style>
