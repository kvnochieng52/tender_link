<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicNavbar from "@/Components/PublicNavbar.vue";

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
</script>

<template>
  <div class="landing-page bg-light">
    <PublicNavbar
      :canLogin="canLogin"
      :canRegister="canRegister"
      active-page="browse"
    />

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
            alt="Tender Plug"
            class="footer-logo mb-3 mb-md-0"
          />

          <div class="footer-links d-flex align-items-center flex-wrap">
            <a href="#" class="footer-link mr-3">Terms and Conditions</a>
            <a href="#" class="footer-link">Contact Us</a>
          </div>
        </div>

        <div class="footer-bottom text-center text-md-left mt-3 pt-3">
          <small class="text-muted"
            >© {{ new Date().getFullYear() }} Tender Plug. All rights
            reserved.</small
          >
        </div>
      </div>
    </footer>
  </div>
</template>
