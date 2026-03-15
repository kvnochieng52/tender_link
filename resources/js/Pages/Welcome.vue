<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
});

const categories = [
  "Construction",
  "Supply",
  "ICT",
  "Agro",
  "Government",
  "NGOs",
];

const sampleTenders = [
  {
    title: "Supply of Medical Equipment",
    county: "Nairobi",
    deadline: "5 days left",
    budget: "KES 12M",
    isSponsored: true,
  },
  {
    title: "Road Rehabilitation Works",
    county: "Mombasa",
    deadline: "11 days left",
    budget: "KES 58M",
    isSponsored: false,
  },
  {
    title: "County ICT Infrastructure Upgrade",
    county: "Kisumu",
    deadline: "3 days left",
    budget: "KES 24M",
    isSponsored: false,
  },
  {
    title: "Supply & Delivery of School Furniture",
    county: "Nakuru",
    deadline: "8 days left",
    budget: "KES 9M",
    isSponsored: false,
  },
  {
    title: "Water Pipeline Extension Project",
    county: "Kiambu",
    deadline: "14 days left",
    budget: "KES 32M",
    isSponsored: true,
  },
];

const plans = [
  {
    name: "Monthly",
    price: "KES 2,500",
    period: "per month",
    badge: "Starter",
  },
  {
    name: "Quarterly",
    price: "KES 6,900",
    period: "per 3 months",
    badge: "Popular",
    highlighted: true,
  },
  {
    name: "Annual",
    price: "KES 24,000",
    period: "per year",
    badge: "Best Value",
  },
];

const coreFeatures = [
  {
    icon: "fas fa-user-shield",
    title: "User Management",
    text: "Registration, login, email verification, password reset, and role-based access for Admin, Paid, and Trial users.",
  },
  {
    icon: "fas fa-wallet",
    title: "Subscriptions & Payments",
    text: "Monthly, quarterly, and annual plans with M-Pesa and card payment channels designed for fast onboarding.",
  },
  {
    icon: "fas fa-file-contract",
    title: "Tender Listing Engine",
    text: "Categorized tenders with filters, search, downloadable documents, and free preview before full access.",
  },
  {
    icon: "fas fa-bell",
    title: "Automated Alerts",
    text: "Email and digest alert experience for categories and keywords, with room for WhatsApp and SMS expansion.",
  },
  {
    icon: "fas fa-chart-pie",
    title: "Admin Analytics",
    text: "Admin-facing overview sections for users, subscriptions, revenue insights, and most viewed tenders.",
  },
  {
    icon: "fas fa-lock",
    title: "Security & Compliance",
    text: "Security-focused UI messaging around encryption, SSL, backups, anti-scraping readiness, and safe access patterns.",
  },
];

const heroSlides = [
  {
    title: "Construction & Infrastructure Opportunities",
    subtitle:
      "Track national and county projects with deadline-focused insights.",
    image:
      "https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1400&q=80",
  },
  {
    title: "Government & NGO Procurement",
    subtitle: "Discover verified public and donor-funded tenders in one place.",
    image:
      "https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1400&q=80",
  },
  {
    title: "ICT, Supply & Agro Business Leads",
    subtitle:
      "Filter opportunities by industry, county, and budget size quickly.",
    image:
      "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1400&q=80",
  },
];

const heroSlideIndex = ref(0);
let heroSlideTimer = null;

const nextHeroSlide = () => {
  heroSlideIndex.value = (heroSlideIndex.value + 1) % heroSlides.length;
};

const prevHeroSlide = () => {
  heroSlideIndex.value =
    (heroSlideIndex.value - 1 + heroSlides.length) % heroSlides.length;
};

const goToHeroSlide = (index) => {
  heroSlideIndex.value = index;
};

onMounted(() => {
  heroSlideTimer = setInterval(nextHeroSlide, 5000);
});

onBeforeUnmount(() => {
  if (heroSlideTimer) {
    clearInterval(heroSlideTimer);
  }
});

const mobileMenuOpen = ref(false);
const tenderSubmenuOpen = ref(false);

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;

  if (!mobileMenuOpen.value) {
    tenderSubmenuOpen.value = false;
  }
};

const toggleTenderSubmenu = (event) => {
  event.preventDefault();
  tenderSubmenuOpen.value = !tenderSubmenuOpen.value;
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
  tenderSubmenuOpen.value = false;
};

const currentYear = new Date().getFullYear();
</script>

<template>
  <Head title="Tender Portal" />

  <div class="landing-page bg-light">
    <nav
      class="navbar navbar-expand-md navbar-white bg-white border-bottom shadow-sm sticky-top px-0"
    >
      <div
        class="container d-flex align-items-center justify-content-between flex-wrap"
      >
        <Link :href="route('welcome')" class="navbar-brand mr-0 py-2">
          <img
            src="/images/tender-link-logo.svg"
            alt="Tender Link"
            class="brand-logo-full"
          />
        </Link>

        <button
          class="navbar-toggler mobile-nav-toggler"
          type="button"
          aria-label="Toggle navigation"
          :aria-expanded="mobileMenuOpen"
          @click="toggleMobileMenu"
        >
          <i class="fas fa-bars"></i>
        </button>

        <div :class="['nav-mobile-collapse', mobileMenuOpen ? 'is-open' : '']">
          <ul
            class="navbar-nav nav-main-menu flex-row flex-wrap justify-content-center my-2 my-lg-0 mx-lg-auto"
          >
            <li class="nav-item">
              <Link
                :href="route('welcome')"
                class="nav-link"
                @click="closeMobileMenu"
                >Home</Link
              >
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" @click="closeMobileMenu">About Us</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" @click="closeMobileMenu">Services</a>
            </li>
            <li
              :class="[
                'nav-item',
                'nav-item-dropdown',
                tenderSubmenuOpen ? 'is-open' : '',
              ]"
            >
              <a class="nav-link" href="#" @click="toggleTenderSubmenu">
                Browse Tenders <i class="fas fa-angle-down ml-1"></i>
              </a>
              <div class="dropdown-menu-custom">
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >Construction</a
                >
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >Supply</a
                >
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >ICT</a
                >
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >Agro</a
                >
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >Government</a
                >
                <a href="#" class="dropdown-item" @click="closeMobileMenu"
                  >NGOs</a
                >
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" @click="closeMobileMenu"
                >Contact Us</a
              >
            </li>
          </ul>

          <div
            class="auth-actions d-flex align-items-center flex-wrap justify-content-end py-2"
          >
            <template v-if="canLogin">
              <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
                @click="closeMobileMenu"
              >
                Dashboard
              </Link>
              <template v-else>
                <Link
                  :href="route('login')"
                  class="btn btn-outline-success btn-sm ml-2 mb-1 mb-md-0"
                  @click="closeMobileMenu"
                >
                  Login/Register
                </Link>
                <Link
                  v-if="canRegister"
                  :href="route('register')"
                  class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
                  @click="closeMobileMenu"
                >
                  Apply Tender
                </Link>
                <Link
                  v-if="canRegister"
                  :href="route('register')"
                  class="btn btn-warning btn-sm ml-2 mb-1 mb-md-0"
                  @click="closeMobileMenu"
                >
                  Post Tender
                </Link>
              </template>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <main class="pb-5">
      <section class="pt-2 pt-md-3 pb-2">
        <div class="container">
          <div class="hero-slider-card mb-4">
            <div class="hero-slider-media position-relative">
              <img
                :src="heroSlides[heroSlideIndex].image"
                :alt="heroSlides[heroSlideIndex].title"
                class="hero-slide-image"
              />

              <div class="hero-slider-overlay">
                <h4 class="font-weight-bold text-white mb-1">
                  {{ heroSlides[heroSlideIndex].title }}
                </h4>
                <p class="text-white-50 mb-0">
                  {{ heroSlides[heroSlideIndex].subtitle }}
                </p>
              </div>

              <button
                class="slider-nav slider-prev"
                type="button"
                aria-label="Previous slide"
                @click="prevHeroSlide"
              >
                <i class="fas fa-chevron-left"></i>
              </button>
              <button
                class="slider-nav slider-next"
                type="button"
                aria-label="Next slide"
                @click="nextHeroSlide"
              >
                <i class="fas fa-chevron-right"></i>
              </button>

              <div class="search-overlay-wrapper">
                <div class="card search-overlay-card border-0 mb-0">
                  <div class="card-header bg-transparent border-0 pb-2">
                    <h3
                      class="card-title font-weight-bold mb-0"
                      style="color: #28a745"
                    >
                      Search Tenders
                    </h3>
                  </div>
                  <div class="card-body pt-2">
                    <form class="pt-1">
                      <div class="form-group mb-2">
                        <label
                          class="small font-weight-600 mb-1"
                          style="color: #1a3a22"
                          >Keyword</label
                        >
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="e.g. ICT, road works"
                        />
                      </div>

                      <div class="form-row">
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: #1a3a22"
                            >Industry</label
                          >
                          <select class="form-control form-control-sm">
                            <option>All Industries</option>
                            <option>Construction</option>
                            <option>Supply</option>
                            <option>ICT</option>
                            <option>Agro</option>
                          </select>
                        </div>
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: #1a3a22"
                            >County</label
                          >
                          <select class="form-control form-control-sm">
                            <option>All Counties</option>
                            <option>Nairobi</option>
                            <option>Mombasa</option>
                            <option>Kisumu</option>
                            <option>Nakuru</option>
                          </select>
                        </div>
                      </div>

                      <div class="form-row">
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: #1a3a22"
                            >Budget Size</label
                          >
                          <select class="form-control form-control-sm">
                            <option>Any Budget</option>
                            <option>Below KES 1M</option>
                            <option>KES 1M - 10M</option>
                            <option>KES 10M - 50M</option>
                            <option>Above KES 50M</option>
                          </select>
                        </div>
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: #1a3a22"
                            >Deadline</label
                          >
                          <select class="form-control form-control-sm">
                            <option>Any Time</option>
                            <option>Within 7 Days</option>
                            <option>Within 14 Days</option>
                            <option>Within 30 Days</option>
                          </select>
                        </div>
                      </div>

                      <button
                        type="button"
                        class="btn btn-success btn-sm btn-block mt-1"
                      >
                        <i class="fas fa-search mr-1"></i> Search
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Our Services Section ────────────────────────────── -->
      <section class="services-section">
        <div class="container">
          <div class="services-header text-center mb-5">
            <span class="services-badge">WHAT WE DO FOR YOU</span>
            <h2 class="services-title mt-2">
              End-to-End Tender Management Services
            </h2>
            <p class="services-subtitle">
              We handle every stage of the tender process on your behalf, saving
              you time and ensuring compliance.
            </p>
          </div>

          <div class="services-flow">
            <!-- Connecting dashed line (desktop only) -->
            <div class="services-connector" aria-hidden="true"></div>

            <div class="row justify-content-center">
              <!-- 01 Tender Listing & Advertising -->
              <div class="col-sm-6 col-lg-3 mb-4">
                <div class="service-card">
                  <div class="service-step-badge">01</div>
                  <div class="service-icon-wrap">
                    <i class="fas fa-bullhorn"></i>
                  </div>
                  <h5 class="service-card-title">
                    Tender Listing &amp; Advertising
                  </h5>
                  <p class="service-card-text">
                    We publish and advertise your tender to thousands of
                    qualified suppliers and contractors across Kenya, maximising
                    response quality.
                  </p>
                  <a href="#" class="service-learn-more"
                    >Learn more <i class="fas fa-arrow-right ml-1"></i
                  ></a>
                </div>
              </div>

              <!-- 02 Shortlisting -->
              <div class="col-sm-6 col-lg-3 mb-4">
                <div class="service-card">
                  <div class="service-step-badge">02</div>
                  <div class="service-icon-wrap">
                    <i class="fas fa-filter"></i>
                  </div>
                  <h5 class="service-card-title">Shortlisting</h5>
                  <p class="service-card-text">
                    We review every submission against your criteria and
                    shortlist only the most qualified and compliant bidders,
                    saving you hours of screening.
                  </p>
                  <a href="#" class="service-learn-more"
                    >Learn more <i class="fas fa-arrow-right ml-1"></i
                  ></a>
                </div>
              </div>

              <!-- 03 Evaluation -->
              <div class="col-sm-6 col-lg-3 mb-4">
                <div class="service-card">
                  <div class="service-step-badge">03</div>
                  <div class="service-icon-wrap">
                    <i class="fas fa-clipboard-check"></i>
                  </div>
                  <h5 class="service-card-title">Evaluation</h5>
                  <p class="service-card-text">
                    Our expert evaluators score bids objectively against your
                    technical and financial criteria, delivering a transparent,
                    audit-ready report.
                  </p>
                  <a href="#" class="service-learn-more"
                    >Learn more <i class="fas fa-arrow-right ml-1"></i
                  ></a>
                </div>
              </div>

              <!-- 04 Award & Notification -->
              <div class="col-sm-6 col-lg-3 mb-4">
                <div class="service-card">
                  <div class="service-step-badge">04</div>
                  <div class="service-icon-wrap">
                    <i class="fas fa-award"></i>
                  </div>
                  <h5 class="service-card-title">Award &amp; Notification</h5>
                  <p class="service-card-text">
                    We issue official award letters and notify all applicants on
                    your behalf, ensuring a professional, compliant conclusion
                    to every tender.
                  </p>
                  <a href="#" class="service-learn-more"
                    >Learn more <i class="fas fa-arrow-right ml-1"></i
                  ></a>
                </div>
              </div>
            </div>
          </div>

          <div class="text-center mt-2">
            <a href="#" class="btn btn-success btn-lg px-5 services-cta-btn">
              <i class="fas fa-handshake mr-2"></i>Get Started With Our Services
            </a>
          </div>
        </div>
      </section>

      <section class="pb-2 browse-tenders-section">
        <div class="container">
          <div class="row">
            <div class="col-12 mb-4">
              <div class="card card-outline card-success h-100 shadow-sm">
                <div
                  class="card-header d-flex justify-content-between align-items-center bg-white"
                >
                  <h3 class="card-title font-weight-bold">Browse Tenders</h3>
                  <span class="badge badge-warning">Free Preview Mode</span>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <span
                      v-for="category in categories"
                      :key="category"
                      class="badge badge-light border mr-2 mb-2 p-2 text-muted"
                    >
                      {{ category }}
                    </span>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6 mb-2 mb-md-0">
                      <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                          <span class="input-group-text"
                            ><i class="fas fa-search text-success"></i
                          ></span>
                        </div>
                        <input
                          class="form-control"
                          placeholder="Search tenders"
                          disabled
                        />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex flex-wrap justify-content-md-end">
                        <span class="badge badge-pill badge-success mr-2 mb-1"
                          >County</span
                        >
                        <span class="badge badge-pill badge-success mr-2 mb-1"
                          >Deadline</span
                        >
                        <span class="badge badge-pill badge-success mr-2 mb-1"
                          >Budget</span
                        >
                        <span class="badge badge-pill badge-success mb-1"
                          >Industry</span
                        >
                      </div>
                    </div>
                  </div>

                  <div
                    v-for="tender in sampleTenders"
                    :key="tender.title"
                    class="callout callout-success mb-2"
                  >
                    <div
                      class="d-flex justify-content-between align-items-start flex-wrap"
                    >
                      <div class="pr-2">
                        <h6 class="mb-1 font-weight-bold text-dark">
                          {{ tender.title }}
                        </h6>
                        <p class="mb-1 text-muted small">
                          {{ tender.county }} • {{ tender.deadline }} •
                          {{ tender.budget }}
                        </p>
                        <span class="badge badge-warning mr-1"
                          >Title visible to all</span
                        >
                        <span class="badge badge-secondary"
                          >Details for paid users</span
                        >
                        <span
                          v-if="tender.isSponsored"
                          class="badge badge-success ml-1"
                          >Sponsored</span
                        >
                      </div>
                      <div>
                        <button
                          class="btn btn-outline-success btn-xs mr-1"
                          type="button"
                        >
                          Save
                        </button>
                        <button
                          class="btn btn-outline-secondary btn-xs"
                          type="button"
                        >
                          Download
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="text-center mt-3">
                    <button type="button" class="btn btn-success px-4">
                      <i class="fas fa-list mr-1"></i> Browse More Tenders
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="pb-5">
        <div class="container">
          <div class="card ready-cta-card text-white shadow-sm border-0 mb-0">
            <div
              class="card-body py-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between"
            >
              <div class="mb-3 mb-md-0 pr-md-3">
                <h4 class="font-weight-bold mb-1">
                  Ready to access verified tenders daily?
                </h4>
                <p class="mb-0 opacity-85">
                  Start with free title preview, then upgrade for full details
                  and documents.
                </p>
              </div>
              <div
                class="d-flex flex-wrap justify-content-end ml-md-auto text-md-right"
              >
                <Link
                  v-if="canRegister"
                  :href="route('register')"
                  class="btn btn-light mr-2 mb-2 mb-md-0"
                  >Create Account</Link
                >
                <Link
                  v-if="canLogin"
                  :href="route('login')"
                  class="btn btn-outline-light mb-2 mb-md-0"
                  >Sign In</Link
                >
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="landing-footer border-top">
      <div class="container footer-content-wrap">
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-md-center"
        >
          <img
            src="/images/tender-link-logo.svg"
            alt="Tender Link"
            class="footer-logo mb-3 mb-md-0"
          />

          <div class="footer-links d-flex align-items-center flex-wrap">
            <a href="#" class="footer-link mr-3">Terms and Conditions</a>
            <a href="#" class="footer-link">Contact Us</a>
          </div>
        </div>

        <div class="footer-bottom text-center text-md-left mt-3 pt-3">
          <small class="text-muted"
            >© {{ currentYear }} Tender Link. All rights reserved.</small
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

.hero-panel {
  background: linear-gradient(
    135deg,
    rgba(40, 167, 69, 0.08),
    rgba(255, 255, 255, 1)
  );
  border: 0;
}

.hero-slider-card {
  overflow: hidden;
  border: 0 !important;
  box-shadow: none !important;
}

.hero-slider-media {
  height: 450px;
  overflow: hidden;
  border-radius: 0.25rem;
}

.search-overlay-wrapper {
  position: absolute;
  right: 3.4rem;
  top: 2.8rem;
  width: min(390px, calc(100% - 4.8rem));
  z-index: 4;
}

.search-overlay-card {
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 0.6rem;
  color: #1a3a22;
  border: 1px solid rgba(40, 167, 69, 0.25);
}

.search-overlay-card .form-control {
  border-color: rgba(255, 255, 255, 0.26);
  background: rgba(255, 255, 255, 0.94);
}

.hero-slide-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.hero-slider-overlay {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 1rem 1.1rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.08));
}

.slider-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.85);
  color: #1f8f53;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.slider-prev {
  left: 0.7rem;
}

.slider-next {
  right: 0.7rem;
}

.hero-dots {
  gap: 0.45rem;
}

.hero-dot {
  width: 0.62rem;
  height: 0.62rem;
  border-radius: 50%;
  border: 0;
  background: #c5d2c8;
}

.hero-dot.active {
  background: #28a745;
}

.mobile-nav-toggler {
  display: none;
  border-color: rgba(40, 167, 69, 0.4);
  color: #1f8f53;
  align-items: center;
  justify-content: center;
}

.mobile-nav-toggler i {
  font-size: 1.2rem;
}

.nav-mobile-collapse {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: 1 1 auto;
}

.auth-actions {
  margin-left: auto;
}

.nav-main-menu {
  gap: 0.15rem;
}

.nav-main-menu .nav-link {
  color: #2f3e46;
  font-weight: 600;
  padding: 0.45rem 0.75rem;
  border-radius: 0.35rem;
}

.nav-main-menu .nav-link:hover {
  color: #1f8f53;
  background-color: rgba(40, 167, 69, 0.08);
}

.nav-item-dropdown {
  position: relative;
}

.dropdown-menu-custom {
  position: absolute;
  top: calc(100% + 0.35rem);
  left: 0;
  z-index: 1100;
  min-width: 190px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0.5rem;
  box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.12);
  display: none;
  padding: 0.35rem 0;
}

.nav-item-dropdown:hover .dropdown-menu-custom {
  display: block;
}

.nav-item-dropdown.is-open .dropdown-menu-custom {
  display: block;
}

.dropdown-item {
  color: #2f3e46;
}

.dropdown-item:hover {
  background-color: rgba(40, 167, 69, 0.08);
  color: #1f8f53;
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
  margin: 0 !important;
  padding: 1.15rem 0 1rem;
}

.footer-content-wrap {
  padding-top: 0.1rem;
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

.icon-circle {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #28a745;
}

/* ── Our Services Section ──────────────────────────────── */
.services-section {
  background: linear-gradient(160deg, #f0faf4 0%, #e8f8ee 100%);
  padding: 4rem 0 3rem;
  position: relative;
  overflow: hidden;
}
.services-section::before {
  content: "";
  position: absolute;
  top: -60px;
  left: -60px;
  width: 260px;
  height: 260px;
  background: radial-gradient(
    circle,
    rgba(40, 167, 69, 0.08) 0%,
    transparent 70%
  );
  border-radius: 50%;
  pointer-events: none;
}
.services-section::after {
  content: "";
  position: absolute;
  bottom: -60px;
  right: -60px;
  width: 300px;
  height: 300px;
  background: radial-gradient(
    circle,
    rgba(40, 167, 69, 0.07) 0%,
    transparent 70%
  );
  border-radius: 50%;
  pointer-events: none;
}
.services-badge {
  display: inline-block;
  background: #28a745;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  padding: 0.28rem 0.9rem;
  border-radius: 50px;
  text-transform: uppercase;
}
.services-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #1a3a22;
  line-height: 1.25;
}
.services-subtitle {
  color: #5a7060;
  font-size: 1rem;
  max-width: 580px;
  margin: 0.5rem auto 0;
}
.services-flow {
  position: relative;
}
.services-connector {
  display: none;
}
@media (min-width: 992px) {
  .services-connector {
    display: block;
    position: absolute;
    top: 58px;
    left: calc(12.5% + 38px);
    right: calc(12.5% + 38px);
    height: 2px;
    background: repeating-linear-gradient(
      90deg,
      #28a745 0px,
      #28a745 10px,
      transparent 10px,
      transparent 22px
    );
    z-index: 0;
  }
}
.service-card {
  background: #fff;
  border-radius: 1rem;
  padding: 2rem 1.4rem 1.6rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(40, 167, 69, 0.09);
  border: 1.5px solid #e2f3e8;
  height: 100%;
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.28s ease, box-shadow 0.28s ease,
    border-color 0.28s ease;
}
.service-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 14px 36px rgba(40, 167, 69, 0.18);
  border-color: #28a745;
}
.service-step-badge {
  position: absolute;
  top: -16px;
  left: 50%;
  transform: translateX(-50%);
  width: 36px;
  height: 36px;
  background: #28a745;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 800;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 10px rgba(40, 167, 69, 0.35);
  border: 3px solid #fff;
  letter-spacing: 0.03em;
}
.service-icon-wrap {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0.5rem auto 1.2rem;
  box-shadow: 0 6px 18px rgba(40, 167, 69, 0.28);
  transition: transform 0.28s ease;
  flex-shrink: 0;
}
.service-card:hover .service-icon-wrap {
  transform: scale(1.1) rotate(-4deg);
}
.service-icon-wrap i {
  font-size: 1.6rem;
  color: #fff;
}
.service-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1a3a22;
  margin-bottom: 0.65rem;
}
.service-card-text {
  font-size: 0.875rem;
  color: #5a7060;
  line-height: 1.6;
  margin-bottom: 1rem;
  flex-grow: 1;
}
.service-learn-more {
  display: inline-block;
  font-size: 0.82rem;
  font-weight: 600;
  color: #28a745;
  text-decoration: none;
  border-bottom: 1.5px solid transparent;
  transition: border-color 0.2s, color 0.2s;
  margin-top: auto;
}
.service-learn-more:hover {
  color: #1f8f53;
  border-bottom-color: #1f8f53;
  text-decoration: none;
}
.services-cta-btn {
  border-radius: 50px;
  font-weight: 700;
  font-size: 1rem;
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.services-cta-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(40, 167, 69, 0.42);
}

.ready-cta-card {
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
}
/* ─────────────────────────────────────────────────────────── */

.feature-card {
  border-left: 3px solid rgba(40, 167, 69, 0.35);
}

.display-5 {
  font-size: 2.25rem;
  line-height: 1.2;
}

@media (max-width: 767.98px) {
  .mobile-nav-toggler {
    display: inline-flex;
  }

  .nav-mobile-collapse {
    display: none;
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    padding-bottom: 0.5rem;
  }

  .nav-mobile-collapse.is-open {
    display: flex;
  }

  .display-5 {
    font-size: 1.75rem;
  }

  .hero-slider-media {
    height: auto;
    min-height: 320px;
  }

  .search-overlay-wrapper {
    position: static;
    width: 100%;
    margin-top: 0.8rem;
  }

  .search-overlay-card {
    background: rgba(33, 37, 41, 0.86);
  }

  .nav-main-menu {
    width: 100%;
    flex-direction: column !important;
    justify-content: flex-start !important;
    margin-top: 0.35rem;
    margin-bottom: 0.2rem;
  }

  .nav-main-menu .nav-item {
    width: 100%;
  }

  .nav-main-menu .nav-link {
    padding: 0.6rem 0.75rem;
  }

  .dropdown-menu-custom {
    position: static;
    min-width: 100%;
    border-radius: 0.35rem;
    box-shadow: none;
    margin: 0.2rem 0 0.35rem;
  }

  .nav-item-dropdown:hover .dropdown-menu-custom {
    display: none;
  }

  .nav-item-dropdown.is-open .dropdown-menu-custom {
    display: block;
  }

  .auth-actions {
    width: 100%;
    justify-content: flex-start !important;
    margin-left: 0;
    padding-top: 0.25rem !important;
  }

  .navbar .btn {
    min-width: 90px;
    margin-left: 0 !important;
    margin-right: 0.5rem;
  }

  .brand-logo-full {
    height: 36px;
  }

  .footer-logo {
    height: 30px;
  }
}
</style>
