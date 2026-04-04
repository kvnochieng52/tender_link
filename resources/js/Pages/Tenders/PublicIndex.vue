<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
  tenders: { type: Object, required: true },
  industries: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || "");
const industry = ref(props.filters.industry_id || "");
const county = ref(props.filters.county_id || "");

// Mobile nav state (copied from Welcome.vue)
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
              <Link
                :href="route('about')"
                class="nav-link"
                @click="closeMobileMenu"
                >About Us</Link
              >
            </li>
            <li class="nav-item">
              <Link
                :href="route('services')"
                class="nav-link"
                @click="closeMobileMenu"
                >Services</Link
              >
            </li>
            <li
              :class="[
                'nav-item',
                'nav-item-dropdown',
                tenderSubmenuOpen ? 'is-open' : '',
              ]"
            >
              <a class="nav-link" href="#" @click="toggleTenderSubmenu"
                >Browse Tenders <i class="fas fa-angle-down ml-1"></i
              ></a>
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
              <Link
                :href="route('contact')"
                class="nav-link"
                @click="closeMobileMenu"
                >Contact Us</Link
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
                >Dashboard</Link
              >
              <template v-else>
                <Link
                  :href="route('login')"
                  class="btn btn-outline-success btn-sm ml-2 mb-1 mb-md-0"
                  @click="closeMobileMenu"
                  >Login/Register</Link
                >
                <Link
                  v-if="canRegister"
                  :href="route('register')"
                  class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
                  @click="closeMobileMenu"
                  >Apply Tender</Link
                >
              </template>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <main class="pb-5">
      <section class="pt-4 pb-4">
        <div class="container py-4">
          <div class="row">
            <div class="col-12 mb-3">
              <div class="card">
                <div class="card-body">
                  <form
                    method="get"
                    :action="route('tenders.search')"
                    class="form-inline"
                  >
                    <div
                      class="d-flex flex-wrap align-items-center"
                      style="gap: 12px"
                    >
                      <div
                        class="mb-2"
                        style="flex: 2 1 220px; min-width: 220px"
                      >
                        <input
                          type="text"
                          name="search"
                          v-model="search"
                          class="form-control form-control-sm"
                          placeholder="Keyword or tender number"
                        />
                      </div>

                      <div
                        class="mb-2"
                        style="flex: 1 1 160px; min-width: 150px"
                      >
                        <select
                          name="industry_id"
                          v-model="industry"
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

                      <div
                        class="mb-2"
                        style="flex: 1 1 160px; min-width: 150px"
                      >
                        <select
                          name="county_id"
                          v-model="county"
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

                      <div class="mb-2" style="flex: 0 0 110px">
                        <button class="btn btn-success btn-sm w-100">
                          Search
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div
                v-if="tenders && tenders.data && tenders.data.length"
                class="list-group"
              >
                <div
                  v-for="t in tenders.data"
                  :key="t.id"
                  class="list-group-item mb-2"
                >
                  <div class="d-flex align-items-start">
                    <div class="mr-3">
                      <img
                        v-if="t.institution && t.institution.logo"
                        :src="`/storage/${t.institution.logo}`"
                        alt="logo"
                        style="
                          width: 64px;
                          height: 64px;
                          object-fit: cover;
                          border-radius: 6px;
                        "
                      />
                      <div
                        v-else
                        class="bg-light d-flex align-items-center justify-content-center"
                        style="width: 64px; height: 64px; border-radius: 6px"
                      >
                        No Logo
                      </div>
                    </div>

                    <div class="flex-grow-1">
                      <h5 class="mb-1">
                        <Link
                          :href="route('tenders.public.show', t.slug || t.id)"
                          >{{ t.title }}</Link
                        >
                      </h5>
                      <div class="mb-1 text-muted small">
                        Company:
                        {{
                          t.institution ? t.institution.institution_name : ""
                        }}
                      </div>
                      <p class="mb-1 text-secondary small">
                        {{ t.tender_no ? `Tender No: ${t.tender_no}` : "" }}
                      </p>
                      <div class="mt-2">
                        <Link
                          :href="route('tenders.public.show', t.slug || t.id)"
                          class="btn btn-outline-success btn-sm mr-2"
                          >Details</Link
                        >
                      </div>
                    </div>

                    <div class="ml-3 text-right text-muted small">
                      <div>{{ t.county ? t.county.name : "" }}</div>
                      <div>{{ t.status ? t.status.name : "" }}</div>
                      <div class="mt-2">
                        {{
                          t.closing_date_and_time
                            ? new Date(t.closing_date_and_time).toLocaleString()
                            : t.expiry_date
                            ? new Date(t.expiry_date).toLocaleString()
                            : ""
                        }}
                      </div>
                    </div>
                  </div>
                </div>

                <nav aria-label="Page navigation">
                  <ul class="pagination">
                    <li
                      v-for="link in tenders.links"
                      :key="link.label"
                      class="page-item"
                      :class="{ disabled: !link.url, active: link.active }"
                    >
                      <a
                        class="page-link"
                        :href="link.url"
                        v-html="link.label"
                      ></a>
                    </li>
                  </ul>
                </nav>
              </div>

              <div v-else class="text-center text-muted py-5">
                No tenders found.
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
            >© {{ new Date().getFullYear() }} Tender Link. All rights
            reserved.</small
          >
        </div>
      </div>
    </footer>
  </div>
</template>
