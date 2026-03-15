<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";

const props = defineProps({
  tender: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const formatDateTime = (val) => {
  if (!val) return "—";
  return new Date(val).toLocaleString("en-KE", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const statusBadgeClass = computed(() => {
  const name = props.tender?.status?.name;
  if (name === "Active") return "badge-success";
  if (name === "Closed") return "badge-secondary";
  if (name === "Cancelled") return "badge-danger";
  return "badge-light";
});

const institutionLogoUrl = computed(() => {
  if (!props.tender?.institution?.logo) return null;
  return `/storage/${props.tender.institution.logo}`;
});

const fileDownloadUrl = (filepath) => {
  if (!filepath) return "#";
  return `/storage/${filepath}`;
};

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
</script>

<template>
  <Head :title="`${tender.title} - Tender Details`" />

  <div class="public-tender-page bg-light min-vh-100">
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
            <Link
              v-if="user"
              :href="route('tenders.index')"
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
                :href="route('register')"
                class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
                @click="closeMobileMenu"
              >
                Apply Tender
              </Link>
              <Link
                :href="route('register')"
                class="btn btn-warning btn-sm ml-2 mb-1 mb-md-0"
                @click="closeMobileMenu"
              >
                Post Tender
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <div class="container py-4">
      <!-- Hero -->
      <div class="card tender-hero mb-4">
        <div class="card-body px-4 pt-4 pb-3">
          <!-- Badges -->
          <div
            class="d-flex flex-wrap align-items-center mb-3"
            style="gap: 0.4rem"
          >
            <span
              class="badge badge-pill hero-status-badge"
              :class="statusBadgeClass"
            >
              {{ tender.status?.name || "Unknown" }}
            </span>
            <span class="badge badge-pill hero-industry-badge">
              {{ tender.industry?.name || "Industry" }}
            </span>
          </div>

          <!-- Title — full width -->
          <h1 class="hero-title mb-3">{{ tender.title }}</h1>

          <!-- Single meta row: Tender No | County | Closing Date | Expiry Date -->
          <div class="hero-meta-row">
            <div class="hero-meta-item">
              <span class="hero-meta-label">Tender No.</span>
              <span class="hero-meta-value">{{ tender.tender_no || "—" }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label">County</span>
              <span class="hero-meta-value">{{
                tender.county?.name || "—"
              }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label"
                ><i class="far fa-calendar-alt mr-1"></i>Closing Date</span
              >
              <span class="hero-meta-value text-danger">{{
                formatDateTime(tender.closing_date_and_time)
              }}</span>
            </div>
            <span class="hero-meta-sep d-none d-sm-inline-block"></span>
            <div class="hero-meta-item">
              <span class="hero-meta-label"
                ><i class="far fa-calendar-check mr-1"></i>Expiry Date</span
              >
              <span class="hero-meta-value">{{
                formatDateTime(tender.expiry_date)
              }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8 mb-4 mb-lg-0">
          <!-- Description -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-1">
              <h5 class="font-weight-bold mb-0">Tender Description</h5>
            </div>
            <div class="card-body">
              <div class="content-html" v-html="tender.description"></div>
            </div>
          </div>

          <!-- Requirements -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-1">
              <h5 class="font-weight-bold mb-0">Key Requirements</h5>
            </div>
            <div class="card-body">
              <div
                v-if="tender.key_requirements"
                class="content-html"
                v-html="tender.key_requirements"
              ></div>
              <p v-else class="text-muted mb-0">
                No key requirements specified.
              </p>
            </div>
          </div>

          <!-- Files -->
          <div class="card border-0 shadow-sm">
            <div
              class="card-header bg-white border-0 pb-1 d-flex justify-content-between align-items-center"
            >
              <h5 class="font-weight-bold mb-0">Tender Documents</h5>
              <span class="badge badge-light"
                >{{ tender.files?.length || 0 }} files</span
              >
            </div>
            <div class="card-body">
              <div
                v-if="!tender.files || tender.files.length === 0"
                class="alert alert-light border mb-0"
              >
                No downloadable documents available.
              </div>

              <div v-else class="list-group list-group-flush">
                <a
                  v-for="file in tender.files"
                  :key="file.id"
                  :href="fileDownloadUrl(file.filepath)"
                  :download="file.file_name"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <div class="d-flex align-items-center">
                    <i class="fas fa-file-alt text-success mr-2"></i>
                    <span>{{ file.file_name }}</span>
                  </div>
                  <span class="btn btn-sm btn-outline-success">
                    <i class="fas fa-download mr-1"></i> Download
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <!-- Institution -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-1">
              <h5 class="font-weight-bold mb-0">Tender Institution</h5>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-start mb-3">
                <img
                  v-if="institutionLogoUrl"
                  :src="institutionLogoUrl"
                  :alt="tender.institution?.institution_name"
                  class="institution-logo mr-3"
                />
                <div v-else class="institution-logo-placeholder mr-3">
                  {{
                    tender.institution?.institution_name
                      ?.charAt(0)
                      ?.toUpperCase() || "I"
                  }}
                </div>

                <div>
                  <h6 class="font-weight-bold mb-1">
                    {{ tender.institution?.institution_name || "—" }}
                  </h6>
                  <span class="badge badge-success-light">{{
                    tender.institution?.institution_type?.name || "—"
                  }}</span>
                </div>
              </div>

              <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-2" v-if="tender.institution?.email">
                  <i class="fas fa-envelope text-success mr-2"></i
                  >{{ tender.institution.email }}
                </li>
                <li class="mb-2" v-if="tender.institution?.telephone">
                  <i class="fas fa-phone text-success mr-2"></i
                  >{{ tender.institution.telephone }}
                </li>
                <li class="mb-2" v-if="tender.institution?.website">
                  <i class="fas fa-globe text-success mr-2"></i>
                  <a
                    :href="tender.institution.website"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-success"
                  >
                    {{ tender.institution.website }}
                  </a>
                </li>
                <li v-if="tender.institution?.address">
                  <i class="fas fa-map-marker-alt text-success mr-2"></i
                  >{{ tender.institution.address }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Quick summary -->
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-1">
              <h5 class="font-weight-bold mb-0">Quick Summary</h5>
            </div>
            <div class="card-body small">
              <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted">Tender No</span>
                <span class="font-weight-semibold">{{ tender.tender_no }}</span>
              </div>
              <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted">Status</span>
                <span class="font-weight-semibold">{{
                  tender.status?.name || "—"
                }}</span>
              </div>
              <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted">Industry</span>
                <span class="font-weight-semibold">{{
                  tender.industry?.name || "—"
                }}</span>
              </div>
              <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-muted">County</span>
                <span class="font-weight-semibold">{{
                  tender.county?.name || "—"
                }}</span>
              </div>
              <div class="d-flex justify-content-between pt-2">
                <span class="text-muted">Documents</span>
                <span class="font-weight-semibold">{{
                  tender.files?.length || 0
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.brand-logo-full {
  height: 42px;
  width: auto;
  display: block;
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
}
.tender-hero {
  background: #ffffff;
  border: 1px solid #c8e6d3 !important;
  border-left: 5px solid #28a745 !important;
  border-radius: 0.5rem;
  box-shadow: 0 2px 12px rgba(40, 167, 69, 0.08) !important;
}

.hero-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a3326;
  line-height: 1.4;
  letter-spacing: -0.01em;
  margin-bottom: 0;
}

@media (max-width: 576px) {
  .hero-title {
    font-size: 1rem;
  }
}

.hero-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 0;
}

.hero-meta-item {
  display: flex;
  flex-direction: column;
  margin-right: 2.5rem;
  margin-bottom: 0.25rem;
}

.hero-meta-sep {
  width: 1px;
  height: 36px;
  background: #d4edda;
  margin-right: 2.5rem;
  align-self: center;
}

.hero-meta-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #6c757d;
  font-weight: 600;
  margin-bottom: 2px;
}

.hero-meta-value {
  font-size: 0.95rem;
  color: #1a3326;
  font-weight: 600;
}

.hero-status-badge {
  font-size: 0.75rem;
  padding: 0.35em 0.75em;
  font-weight: 600;
}

.hero-industry-badge {
  font-size: 0.75rem;
  padding: 0.35em 0.75em;
  background: rgba(40, 167, 69, 0.1);
  color: #1f8f53;
  font-weight: 600;
}

.content-html {
  color: #495057;
  line-height: 1.65;
}

.content-html :deep(p:last-child) {
  margin-bottom: 0;
}

.institution-logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 0.5rem;
  border: 1px solid #d7e6dc;
  background: #fff;
  padding: 4px;
}

.institution-logo-placeholder {
  width: 64px;
  height: 64px;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-success-light {
  background: rgba(40, 167, 69, 0.12);
  color: #1f8f53;
  font-weight: 600;
}

.font-weight-semibold {
  font-weight: 600;
}
</style>
