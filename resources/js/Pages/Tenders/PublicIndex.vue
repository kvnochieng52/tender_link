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

const formatDate = (value) => {
  if (!value) return "";
  return new Date(value).toLocaleString();
};
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
                  :key="t.id || t.title"
                  class="callout callout-success mb-2"
                >
                  <div class="d-flex align-items-start tender-card-wrap">
                    <div class="flex-shrink-0 mr-3">
                      <img
                        v-if="t.institution && t.institution.logo"
                        :src="`/storage/${t.institution.logo}`"
                        :alt="t.institution.institution_name"
                        class="tender-list-logo"
                      />
                      <div v-else class="tender-list-logo-placeholder">
                        <i class="fas fa-building text-muted"></i>
                      </div>
                    </div>

                    <div class="flex-grow-1 min-width-0">
                      <Link
                        :href="route('tenders.public.show', t.slug || t.id)"
                        class="mb-1 font-weight-bold text-dark d-block tender-item-title"
                        style="text-decoration: none; line-height: 1.4"
                      >
                        {{ t.title }}
                      </Link>

                      <div
                        v-if="t.institution && t.institution.institution_name"
                        class="text-muted small mb-1"
                      >
                        <i class="fas fa-building mr-1 text-success"></i>
                        {{ t.institution.institution_name }}
                      </div>

                      <p
                        class="mb-1 text-muted small d-flex flex-wrap align-items-center"
                      >
                        <i class="fas fa-map-marker-alt mr-1 text-success"></i>
                        <span class="mr-3">{{
                          t.county ? t.county.name : ""
                        }}</span>

                        <span
                          v-if="t.published_at || t.created_at"
                          class="mr-3 d-flex align-items-center"
                        >
                          <i
                            class="fas fa-calendar-alt mr-1 text-secondary"
                          ></i>
                          <strong class="mr-1">Published:</strong>
                          <small class="text-muted">
                            {{ formatDate(t.published_at || t.created_at) }}
                          </small>
                        </span>

                        <span
                          v-if="
                            t.closing_at ||
                            t.closing_date_and_time ||
                            t.expiry_date
                          "
                          class="mr-3 d-flex align-items-center"
                        >
                          <i class="fas fa-clock mr-1 text-secondary"></i>
                          <strong class="mr-1">Closing:</strong>
                          <small class="text-muted">
                            {{
                              formatDate(
                                t.closing_at ||
                                  t.closing_date_and_time ||
                                  t.expiry_date
                              )
                            }}
                          </small>
                        </span>

                        <span v-if="t.budget" class="d-flex align-items-center">
                          <i
                            class="fas fa-money-bill-wave mr-1 text-secondary"
                          ></i>
                          <small class="text-muted">{{ t.budget }}</small>
                        </span>
                      </p>

                      <div class="mb-0">
                        <span class="badge badge-secondary mr-1">
                          {{ t.status ? t.status.name : "Status" }}
                        </span>
                      </div>
                    </div>

                    <div class="tender-item-actions flex-shrink-0">
                      <Link
                        :href="route('tenders.public.show', t.slug || t.id)"
                        class="btn btn-outline-success btn-sm"
                        style="text-decoration: none"
                      >
                        <i class="fas fa-info-circle mr-1"></i> Details
                      </Link>
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

.tender-item-title {
  word-break: break-word;
  overflow-wrap: break-word;
  white-space: normal;
  line-height: 1.4;
}

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

@media (max-width: 575.98px) {
  .tender-card-wrap {
    flex-wrap: wrap;
  }
  .tender-item-actions {
    width: 100%;
    flex-direction: row;
    flex-wrap: wrap;
    margin-left: 0;
    padding-left: calc(52px + 1rem);
    margin-top: 0.5rem;
  }
}
</style>
