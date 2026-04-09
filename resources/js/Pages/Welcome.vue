<script setup>
import { onBeforeUnmount, onMounted, ref, watch, toRefs } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
  tenders: { type: Array, default: () => [] },
  industries: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
});
const { tenders, industries, counties, canLogin, canRegister } = toRefs(props);

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
    title: "Government & NGO Procurement",
    subtitle: "Discover verified public and donor-funded tenders in one place.",
    image: "/images/photo2.jpg",
  },
  // {
  //   title: "Construction & Infrastructure Opportunities",
  //   subtitle:
  //     "Track national and county projects with deadline-focused insights.",
  //   image: "/images/photo1.jpg",
  // },
  // {
  //   title: "ICT, Supply & Agro Business Leads",
  //   subtitle:
  //     "Filter opportunities by industry, county, and budget size quickly.",
  //   image: "/images/photo3.jpg",
  // },
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

const currentYear = new Date().getFullYear();

const formatDate = (d) => {
  if (!d) return "";
  try {
    return new Date(d).toLocaleString();
  } catch (e) {
    return d;
  }
};

// local visible tenders to avoid accidental replacement with sample data
const visibleTenders = ref([]);
visibleTenders.value = tenders.value || [];
watch(tenders, (n) => {
  visibleTenders.value = n || [];
});

// Search form state
const keyword = ref("");
const industryFilter = ref("");
const countyFilter = ref("");
const deadlineFilter = ref("");

const submitSearch = () => {
  const params = {};
  if (keyword.value) params.search = keyword.value;
  if (industryFilter.value) params.industry_id = industryFilter.value;
  if (countyFilter.value) params.county_id = countyFilter.value;
  if (deadlineFilter.value) params.deadline = deadlineFilter.value;

  const query = new URLSearchParams(params).toString();
  const url = route("tenders.search") + (query ? `?${query}` : "");
  window.location.href = url;
};
</script>

<template>
  <Head title="Tender Portal" />

  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="home"
    />

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
                      style="color: var(--brand-primary)"
                    >
                      Search Tenders
                    </h3>
                  </div>
                  <div class="card-body pt-2">
                    <form class="pt-1" @submit.prevent="submitSearch">
                      <div class="form-row">
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: var(--brand-primary-dark)"
                            >Industry</label
                          >
                          <select
                            v-model="industryFilter"
                            class="form-control form-control-sm"
                          >
                            <option value="">All Industries</option>
                            <option
                              v-for="ind in industries"
                              :key="ind.id"
                              :value="ind.id"
                            >
                              {{ ind.name }}
                            </option>
                          </select>
                        </div>
                        <div class="form-group col-6 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: var(--brand-primary-dark)"
                            >County</label
                          >
                          <select
                            v-model="countyFilter"
                            class="form-control form-control-sm"
                          >
                            <option value="">All Counties</option>
                            <option
                              v-for="c in counties"
                              :key="c.id"
                              :value="c.id"
                            >
                              {{ c.name }}
                            </option>
                          </select>
                        </div>
                      </div>

                      <div class="form-row">
                        <div class="form-group col-12 mb-2">
                          <label
                            class="small font-weight-600 mb-1"
                            style="color: var(--brand-primary-dark)"
                            >Deadline</label
                          >
                          <select
                            v-model="deadlineFilter"
                            class="form-control form-control-sm"
                          >
                            <option value="">Any Time</option>
                            <option value="7">Within 7 Days</option>
                            <option value="14">Within 14 Days</option>
                            <option value="30">Within 30 Days</option>
                          </select>
                        </div>
                      </div>

                      <div class="form-group mb-2">
                        <label
                          class="small font-weight-600 mb-1"
                          style="color: var(--brand-primary-dark)"
                          >Keyword</label
                        >
                        <input
                          v-model="keyword"
                          type="text"
                          class="form-control form-control-sm"
                          placeholder="e.g. ICT, road works"
                        />
                      </div>

                      <button
                        type="submit"
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
            <a href="#" class="btn btn-lg px-5 services-cta-btn">
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
                <div class="card-header d-flex align-items-center bg-white">
                  <h3 class="browse-tenders-title font-weight-bold mb-0">
                    Browse Tenders
                  </h3>
                </div>
                <div class="card-body">
                  <div class="row mb-3">
                    <div class="col-12">
                      <div class="input-group input-group-sm">
                        <input
                          v-model="keyword"
                          class="form-control"
                          placeholder="Search tenders"
                          @keyup.enter="submitSearch"
                        />
                        <div class="input-group-append">
                          <select
                            v-model="industryFilter"
                            class="form-control form-control-sm"
                            style="border-radius: 0"
                          >
                            <option value="">All Industries</option>
                            <option
                              v-for="industry in industries"
                              :key="industry.id || industry.name"
                              :value="industry.id"
                            >
                              {{ industry.name }}
                            </option>
                          </select>
                        </div>
                        <div class="input-group-append">
                          <button
                            type="button"
                            class="btn btn-success btn-sm"
                            @click="submitSearch"
                            aria-label="Search tenders"
                          >
                            <i class="fas fa-search mr-1"></i> Search
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div
                    v-for="tender in visibleTenders"
                    :key="tender.id || tender.title"
                    class="callout callout-success mb-2"
                  >
                    <!-- Flex row: logo | content | buttons (desktop: top-right; mobile: wraps below) -->
                    <div class="d-flex align-items-start tender-card-wrap">
                      <!-- Company logo -->
                      <div class="flex-shrink-0 mr-3">
                        <img
                          v-if="tender.institution && tender.institution.logo"
                          :src="`/storage/${tender.institution.logo}`"
                          :alt="tender.institution.institution_name"
                          class="tender-list-logo"
                        />
                        <div v-else class="tender-list-logo-placeholder">
                          <i class="fas fa-building text-muted"></i>
                        </div>
                      </div>

                      <!-- Main content: title + meta, always full-width beside logo -->
                      <div class="flex-grow-1 min-width-0">
                        <Link
                          :href="
                            route(
                              'tenders.public.show',
                              tender.slug || tender.id
                            )
                          "
                          class="mb-1 font-weight-bold text-dark d-block tender-item-title"
                          style="text-decoration: none; line-height: 1.4"
                        >
                          {{ tender.title }}
                        </Link>

                        <div
                          v-if="
                            tender.institution &&
                            tender.institution.institution_name
                          "
                          class="text-muted small mb-1"
                        >
                          <i class="fas fa-building mr-1 text-success"></i>
                          {{ tender.institution.institution_name }}
                        </div>

                        <p
                          class="mb-1 text-muted small d-flex flex-wrap align-items-center"
                        >
                          <i
                            class="fas fa-map-marker-alt mr-1 text-success"
                          ></i>
                          <span class="mr-3">{{
                            tender.county && tender.county.name
                              ? tender.county.name
                              : tender.county_name || ""
                          }}</span>

                          <span
                            v-if="tender.published_at || tender.created_at"
                            class="mr-3 d-flex align-items-center"
                          >
                            <i
                              class="fas fa-calendar-alt mr-1 text-secondary"
                            ></i>
                            <strong class="mr-1">Published:</strong>
                            <small class="text-muted">{{
                              formatDate(
                                tender.published_at || tender.created_at
                              )
                            }}</small>
                          </span>

                          <span
                            v-if="
                              tender.closing_at ||
                              tender.closing_date_and_time ||
                              tender.expiry_date
                            "
                            class="mr-3 d-flex align-items-center"
                          >
                            <i class="fas fa-clock mr-1 text-secondary"></i>
                            <strong class="mr-1">Closing:</strong>
                            <small class="text-muted">{{
                              formatDate(
                                tender.closing_at ||
                                  tender.closing_date_and_time ||
                                  tender.expiry_date
                              )
                            }}</small>
                          </span>

                          <span
                            v-if="tender.budget"
                            class="d-flex align-items-center"
                          >
                            <i
                              class="fas fa-money-bill-wave mr-1 text-secondary"
                            ></i>
                            <small class="text-muted">{{
                              tender.budget
                            }}</small>
                          </span>
                        </p>

                        <!-- Badges -->
                        <div class="mb-0">
                          <span class="badge badge-secondary mr-1">
                            {{ tender.status ? tender.status.name : "Status" }}
                          </span>
                          <span class="badge badge-success ml-1"
                            >Sponsored</span
                          >
                        </div>
                      </div>

                      <!-- Action buttons — top-right on desktop, wraps below on mobile -->
                      <div class="tender-item-actions flex-shrink-0">
                        <Link
                          :href="
                            route(
                              'tenders.public.show',
                              tender.slug || tender.id
                            )
                          "
                          class="btn btn-outline-success btn-sm"
                          style="text-decoration: none"
                        >
                          <i class="fas fa-info-circle mr-1"></i> Details
                        </Link>
                        <button
                          type="button"
                          class="btn btn-outline-secondary btn-sm"
                          aria-label="favorite"
                          title="Add to favorites"
                        >
                          <i class="fas fa-heart mr-1"></i> Favorites
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="text-center mt-3">
                    <Link
                      :href="route('tenders.search')"
                      class="btn btn-success px-4"
                    >
                      <i class="fas fa-list mr-1"></i> Browse More Tenders
                    </Link>
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
  </div>
</template>

<style scoped>
.tender-list-logo {
  width: 52px;
  height: 52px;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid #e9ecef;
  background: #fff;
  padding: 2px;
}
.tender-list-logo-placeholder {
  width: 52px;
  height: 52px;
  border-radius: 6px;
  border: 1px dashed #ced4da;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}
.min-width-0 {
  min-width: 0;
}

/* Tender list item — responsive title + actions */
.tender-item-title {
  word-break: break-word;
  overflow-wrap: break-word;
  white-space: normal;
  line-height: 1.4;
}

/* Desktop: buttons are a 3rd flex child, aligned to the top-right of the card */
.tender-card-wrap {
  flex-wrap: nowrap;
  align-items: flex-start;
}
.tender-item-actions {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-left: 0.75rem;
  flex-shrink: 0;
  align-self: flex-start;
}

/* Mobile: buttons wrap below the logo+content row */
@media (max-width: 575.98px) {
  .tender-card-wrap {
    flex-wrap: wrap;
  }
  .tender-item-actions {
    /* full width, indented to align with content (past the 52px logo + 1rem mr-3) */
    width: 100%;
    flex-direction: row;
    flex-wrap: wrap;
    margin-left: 0;
    padding-left: calc(52px + 1rem);
    margin-top: 0.5rem;
  }
}

/* Browse Tenders card title — responsive, no AdminLTE float conflict */
.browse-tenders-title {
  font-size: 1.25rem;
  line-height: 1.3;
  color: var(--brand-primary-dark);
  float: none !important;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
@media (max-width: 575.98px) {
  .browse-tenders-title {
    font-size: 1rem;
  }
}

.landing-page {
  overflow-x: hidden;
}

.hero-panel {
  background: var(--brand-surface-blue);
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
  color: var(--brand-primary-dark);
  border: 1px solid rgba(9, 23, 111, 0.25);
}

.search-overlay-card .form-control {
  border-color: rgba(255, 255, 255, 0.26);
  background: rgba(255, 255, 255, 0.94);
}

.hero-slide-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 25%;
  display: block;
}

.hero-slider-overlay {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 1rem 1.1rem;
  background: rgba(0, 0, 0, 0.55);
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
  color: var(--brand-secondary-dark);
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
  background: var(--brand-primary-border);
}

.hero-dot.active {
  background: var(--brand-primary);
}

.mobile-nav-toggler {
  display: none;
  border-color: rgba(9, 23, 111, 0.4);
  color: var(--brand-secondary-dark);
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
  color: var(--brand-secondary-dark);
  background-color: rgba(9, 23, 111, 0.08);
}

.nav-item-dropdown {
  position: relative;
}

.dropdown-menu-custom {
  position: absolute;
  /* flush against the trigger — no gap so mouse hover doesn't break */
  top: 100%;
  left: 0;
  z-index: 1100;
  min-width: 190px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0 0 0.5rem 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
  display: none;
  padding: 0.35rem 0;
  /* invisible top padding as a hover bridge so mouse can reach the menu */
  margin-top: 0;
}

/* Hover & open both handled via JS — CSS :hover is removed to avoid race condition */
.nav-item-dropdown.is-open .dropdown-menu-custom {
  display: block;
}

.dropdown-item {
  color: #2f3e46;
}

.dropdown-item:hover {
  background-color: rgba(9, 23, 111, 0.08);
  color: var(--brand-secondary-dark);
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

.icon-circle {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--brand-primary);
}

/* ── Our Services Section ──────────────────────────────── */
.services-section {
  background: var(--brand-surface-blue);
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
  background: transparent;
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
  background: transparent;
  border-radius: 50%;
  pointer-events: none;
}
.services-badge {
  display: inline-block;
  background: var(--brand-primary);
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
  color: var(--brand-primary-dark);
  line-height: 1.25;
}
.services-subtitle {
  color: #5b6478;
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
    background: var(--brand-primary);
    z-index: 0;
  }
}
.service-card {
  background: #fff;
  border-radius: 1rem;
  padding: 2rem 1.4rem 1.6rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(9, 23, 111, 0.09);
  border: 1.5px solid #e4ebfb;
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
  box-shadow: 0 14px 36px rgba(9, 23, 111, 0.18);
  border-color: var(--brand-primary);
}
.service-step-badge {
  position: absolute;
  top: -16px;
  left: 50%;
  transform: translateX(-50%);
  width: 36px;
  height: 36px;
  background: var(--brand-primary);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 800;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 10px rgba(9, 23, 111, 0.35);
  border: 3px solid #fff;
  letter-spacing: 0.03em;
}
.service-icon-wrap {
  width: 70px;
  height: 70px;
  background: var(--brand-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0.5rem auto 1.2rem;
  box-shadow: 0 6px 18px rgba(9, 23, 111, 0.28);
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
  color: var(--brand-primary-dark);
  margin-bottom: 0.65rem;
}
.service-card-text {
  font-size: 0.875rem;
  color: #5b6478;
  line-height: 1.6;
  margin-bottom: 1rem;
  flex-grow: 1;
}
.service-learn-more {
  display: inline-block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--brand-primary);
  text-decoration: none;
  border-bottom: 1.5px solid transparent;
  transition: border-color 0.2s, color 0.2s;
  margin-top: auto;
}
.service-learn-more:hover {
  color: var(--brand-secondary-dark);
  border-bottom-color: var(--brand-secondary-dark);
  text-decoration: none;
}
.services-cta-btn {
  border-radius: 50px;
  font-weight: 700;
  font-size: 1rem;
  background: var(--brand-secondary);
  border-color: var(--brand-secondary);
  color: var(--brand-primary);
  box-shadow: 0 6px 20px rgba(9, 23, 111, 0.3);
  transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.2s ease;
}
.services-cta-btn:hover {
  background: var(--brand-secondary-dark);
  border-color: var(--brand-secondary-dark);
  color: var(--brand-primary);
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(9, 23, 111, 0.42);
}

.ready-cta-card {
  background: var(--brand-primary);
}
/* ─────────────────────────────────────────────────────────── */

.feature-card {
  border-left: 3px solid rgba(9, 23, 111, 0.35);
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
    background: #fff;
    border: 1px solid rgba(9, 23, 111, 0.18);
  }

  .search-overlay-card .form-control,
  .search-overlay-card .custom-select {
    background: #f3f6ff;
    border-color: var(--brand-primary-border);
    color: var(--brand-primary-dark);
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
