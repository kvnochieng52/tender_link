<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  canLogin: { type: Boolean, default: false },
  canRegister: { type: Boolean, default: false },
  activePage: { type: String, default: "" }, // 'home'|'about'|'services'|'browse'|'contact'
  redirectUrl: { type: String, default: "" }, // optional redirect after login/register
});

const mobileMenuOpen = ref(false);
const tenderSubmenuOpen = ref(false);
const servicesSubmenuOpen = ref(false);
const dropdownTimerId = ref(null);
const servicesTimerId = ref(null);

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  if (!mobileMenuOpen.value) {
    tenderSubmenuOpen.value = false;
    servicesSubmenuOpen.value = false;
  }
};

const toggleTenderSubmenu = (e) => {
  e.preventDefault();
  tenderSubmenuOpen.value = !tenderSubmenuOpen.value;
};

const toggleServicesSubmenu = (e) => {
  e.preventDefault();
  servicesSubmenuOpen.value = !servicesSubmenuOpen.value;
};

const openDropdown = () => {
  if (dropdownTimerId.value) clearTimeout(dropdownTimerId.value);
  tenderSubmenuOpen.value = true;
};

const closeDropdown = () => {
  dropdownTimerId.value = setTimeout(() => {
    tenderSubmenuOpen.value = false;
  }, 120);
};

const openServicesDropdown = () => {
  if (servicesTimerId.value) clearTimeout(servicesTimerId.value);
  servicesSubmenuOpen.value = true;
};

const closeServicesDropdown = () => {
  servicesTimerId.value = setTimeout(() => {
    servicesSubmenuOpen.value = false;
  }, 120);
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
  tenderSubmenuOpen.value = false;
  servicesSubmenuOpen.value = false;
};

const categoryUrl = (name) =>
  `${route("tenders.search")}?search=${encodeURIComponent(name)}`;
</script>

<template>
  <div class="container d-flex align-items-center justify-content-between">
    <!-- Logo -->
    <Link :href="route('welcome')" class="navbar-brand mr-0 py-2">
      <img
        src="/images/tender-link-logo.svg"
        alt="Tender Plug"
        class="brand-logo-full"
      />
    </Link>

    <!-- Mobile toggle -->
    <button
      class="navbar-toggler mobile-nav-toggler"
      type="button"
      aria-label="Toggle navigation"
      :aria-expanded="mobileMenuOpen"
      @click="toggleMobileMenu"
    >
      <i class="fas fa-bars"></i>
    </button>

    <!-- Collapsible nav -->
    <div :class="['nav-mobile-collapse', mobileMenuOpen ? 'is-open' : '']">
      <ul
        class="navbar-nav nav-main-menu flex-row flex-wrap justify-content-center my-2 my-lg-0 mx-lg-auto"
      >
        <li class="nav-item">
          <Link
            :href="route('welcome')"
            :class="['nav-link', activePage === 'home' ? 'active' : '']"
            @click="closeMobileMenu"
            >Home</Link
          >
        </li>
        <li class="nav-item">
          <Link
            :href="route('about')"
            :class="['nav-link', activePage === 'about' ? 'active' : '']"
            @click="closeMobileMenu"
            >About Us</Link
          >
        </li>
        <li
          :class="[
            'nav-item',
            'nav-item-dropdown',
            servicesSubmenuOpen ? 'is-open' : '',
            activePage === 'services' ? 'active' : '',
          ]"
          @mouseenter="openServicesDropdown"
          @mouseleave="closeServicesDropdown"
        >
          <a class="nav-link" href="#" @click="toggleServicesSubmenu">
            Services <i class="fas fa-angle-down ml-1"></i>
          </a>
          <div
            class="dropdown-menu-custom"
            @mouseenter="openServicesDropdown"
            @mouseleave="closeServicesDropdown"
          >
            <Link
              :href="route('services.procurement')"
              class="dropdown-item"
              @click="closeMobileMenu"
              ><i class="fas fa-cogs mr-2 text-success"></i>End-to-End
              Procurement</Link
            >
            <Link
              :href="route('services.bid-support')"
              class="dropdown-item"
              @click="closeMobileMenu"
              ><i class="fas fa-gavel mr-2 text-success"></i>Compliance &amp;
              Bid Support</Link
            >
            <Link
              :href="route('services.marketplace')"
              class="dropdown-item"
              @click="closeMobileMenu"
              ><i class="fas fa-handshake mr-2 text-success"></i>B2B
              Marketplace</Link
            >
            <Link
              :href="route('services.funding')"
              class="dropdown-item"
              @click="closeMobileMenu"
              ><i class="fas fa-file-invoice-dollar mr-2 text-success"></i
              >Business Plan &amp; Funding</Link
            >
            <div class="dropdown-divider"></div>
            <Link
              :href="route('services')"
              class="dropdown-item font-weight-bold"
              @click="closeMobileMenu"
              ><i class="fas fa-th-list mr-2"></i>All Services</Link
            >
          </div>
        </li>
        <li
          :class="[
            'nav-item',
            'nav-item-dropdown',
            tenderSubmenuOpen ? 'is-open' : '',
            activePage === 'browse' ? 'active' : '',
          ]"
          @mouseenter="openDropdown"
          @mouseleave="closeDropdown"
        >
          <a class="nav-link" href="#" @click="toggleTenderSubmenu">
            Browse Tenders <i class="fas fa-angle-down ml-1"></i>
          </a>
          <div
            class="dropdown-menu-custom"
            @mouseenter="openDropdown"
            @mouseleave="closeDropdown"
          >
            <Link
              :href="categoryUrl('Construction')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >Construction</Link
            >
            <Link
              :href="categoryUrl('Supply')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >Supply</Link
            >
            <Link
              :href="categoryUrl('ICT')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >ICT</Link
            >
            <Link
              :href="categoryUrl('Agro')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >Agro</Link
            >
            <Link
              :href="categoryUrl('Government')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >Government</Link
            >
            <Link
              :href="categoryUrl('NGO')"
              class="dropdown-item"
              @click="closeMobileMenu"
              >NGOs</Link
            >
            <Link
              :href="route('tenders.search')"
              class="dropdown-item font-weight-bold"
              @click="closeMobileMenu"
              ><i class="fas fa-list mr-1"></i>All Tenders</Link
            >
          </div>
        </li>
        <li class="nav-item">
          <Link
            :href="route('contact')"
            :class="['nav-link', activePage === 'contact' ? 'active' : '']"
            @click="closeMobileMenu"
            >Contact Us</Link
          >
        </li>
      </ul>

      <!-- Auth buttons -->
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
              :href="
                route('login') +
                (redirectUrl
                  ? '?redirect=' + encodeURIComponent(redirectUrl)
                  : '')
              "
              class="btn btn-outline-success btn-sm ml-2 mb-1 mb-md-0"
              @click="closeMobileMenu"
              >Login/Register</Link
            >
            <Link
              v-if="canRegister"
              :href="
                route('register') +
                (redirectUrl
                  ? '?redirect=' + encodeURIComponent(redirectUrl)
                  : '')
              "
              class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
              @click="closeMobileMenu"
              >Apply Tender</Link
            >
          </template>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.brand-logo-full {
  height: 44px;
  width: auto;
  display: block;
}

/* ── Mobile nav ─────────────────────────────────────────── */
.mobile-nav-toggler {
  background: none;
  border: 1px solid rgba(40, 167, 69, 0.4);
  color: #28a745;
  border-radius: 6px;
  padding: 0.35rem 0.6rem;
  font-size: 1.1rem;
  display: none;
}
.nav-mobile-collapse {
  flex: 1;
  display: flex;
  align-items: center;
}

.nav-main-menu .nav-link {
  color: #2f3e46;
  font-weight: 500;
  font-size: 0.92rem;
  padding: 0.45rem 0.8rem;
  border-radius: 6px;
  transition: color 0.15s, background 0.15s;
  white-space: nowrap;
}
.nav-main-menu .nav-link:hover,
.nav-main-menu .nav-link.active {
  color: #1f8f53;
  background: rgba(40, 167, 69, 0.07);
}

/* ── Dropdown ───────────────────────────────────────────── */
.nav-item-dropdown {
  position: relative;
}
.dropdown-menu-custom {
  position: absolute;
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
}
.nav-item-dropdown.is-open .dropdown-menu-custom {
  display: block;
}
.dropdown-item {
  color: #2f3e46;
  padding: 0.45rem 1rem;
  font-size: 0.88rem;
  display: block;
}
.dropdown-item:hover {
  background-color: rgba(40, 167, 69, 0.08);
  color: #1f8f53;
}
.dropdown-divider {
  height: 1px;
  background-color: #e9ecef;
  margin: 0.35rem 0;
}

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 991.98px) {
  .mobile-nav-toggler {
    display: block;
  }
  .nav-mobile-collapse {
    display: none;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    border-top: 1px solid #e9ecef;
    padding-top: 0.5rem;
  }
  .nav-mobile-collapse.is-open {
    display: flex;
  }
  .nav-main-menu {
    flex-direction: column !important;
    width: 100%;
  }
  .nav-main-menu .nav-item {
    width: 100%;
  }
  .nav-main-menu .nav-link {
    display: block;
    padding: 0.6rem 0.5rem;
  }
  .nav-item-dropdown .dropdown-menu-custom {
    position: static;
    box-shadow: none;
    border: none;
    border-left: 2px solid #28a745;
    border-radius: 0;
    margin-left: 1rem;
    background: transparent;
  }
  .nav-item-dropdown.is-open .dropdown-menu-custom {
    display: block;
  }
  .auth-actions {
    width: 100%;
    padding-bottom: 0.5rem;
  }
}
</style>
