<script setup>
import { Head, Link } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  application: { type: Object, required: true },
});

const fileUrl = (p) => (p ? `/storage/${p}` : "#");
</script>

<template>
  <DashboardLayout>
    <Head :title="`Application: ${application.application_no || application.company_name}`" />
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex align-items-center">
        <div>
          <h4 class="mb-0">
            {{ application.company_name }}
            <code
              v-if="application.application_no"
              class="text-success ms-2"
              style="font-size: 1rem"
            >
              {{ application.application_no }}
            </code>
          </h4>
          <small class="text-muted"
            >Submitted:
            {{ new Date(application.created_at).toLocaleString() }}</small
          >
        </div>
        <div style="margin-left: auto">
          <Link
            :href="
              route('admin.tenders.applications.index', {
                encryptedId: application.tender.encrypted_id ?? '',
              })
            "
            class="btn btn-sm btn-outline-success"
            >Back to list</Link
          >
        </div>
      </div>

      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100">
              <h6 class="mb-2">Contact Details</h6>
              <p class="mb-1">
                <strong>Telephone:</strong> {{ application.telephone || "—" }}
              </p>
              <p class="mb-1">
                <strong>Email:</strong> {{ application.email || "—" }}
              </p>
              <p class="mb-0">
                <strong>County:</strong> {{ application.county_name || "—" }}
              </p>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100">
              <h6 class="mb-2">Representative</h6>
              <p class="mb-1">
                <strong>Name:</strong>
                {{ application.representative_name || "—" }}
              </p>
              <p class="mb-1">
                <strong>Telephone:</strong>
                {{ application.representative_telephone || "—" }}
              </p>
              <p class="mb-0">
                <strong>Email:</strong>
                {{ application.representative_email || "—" }}
              </p>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100">
              <h6 class="mb-2">Application Stats</h6>
              <div class="d-flex align-items-center mb-2">
                <div style="flex: 1">
                  <div class="small text-muted">Required documents</div>
                  <div class="font-weight-bold">
                    {{ application.stats.required_total }}
                  </div>
                </div>
                <div
                  style="
                    width: 1px;
                    height: 48px;
                    background: #e9ecef;
                    margin: 0 12px;
                  "
                ></div>
                <div style="flex: 1">
                  <div class="small text-muted">Submitted</div>
                  <div class="font-weight-bold">
                    {{ application.stats.submitted_total }}
                  </div>
                </div>
              </div>
              <div class="mb-2">
                <div class="small text-muted">Mandatory satisfied</div>
                <div class="d-flex align-items-center" style="gap: 0.5rem">
                  <div style="flex: 1">
                    <div class="progress" style="height: 10px">
                      <div
                        class="progress-bar bg-success"
                        role="progressbar"
                        :style="{
                          width:
                            Math.round(
                              (application.stats.submitted_required /
                                Math.max(application.stats.required_total, 1)) *
                                100
                            ) + '%',
                        }"
                      ></div>
                    </div>
                  </div>
                  <div class="small">
                    {{ application.stats.submitted_required }} /
                    {{ application.stats.required_total }}
                  </div>
                </div>
              </div>
              <div
                v-if="application.stats.missing_required_count"
                class="small text-danger"
              >
                Missing: {{ application.stats.missing_required_count }}
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-8">
            <div class="card border-0 shadow-sm p-3 mb-3">
              <h6 class="mb-2">Submitted Files</h6>
              <div
                v-if="!application.files || application.files.length === 0"
                class="text-muted"
              >
                No files submitted.
              </div>
              <ul v-else class="list-group">
                <li
                  v-for="f in application.files"
                  :key="f.id"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div>
                    <div class="d-flex align-items-center" style="gap: 0.5rem">
                      <i class="fas fa-file-alt text-secondary"></i>
                      <span class="font-weight-semibold">{{
                        f.requirement?.title || "Document"
                      }}</span>
                    </div>
                    <div class="text-muted small">{{ f.file_name }}</div>
                    <div class="mt-1">
                      <small>
                        <span
                          v-if="f.requirement?.mandatory"
                          class="badge badge-danger"
                          >Mandatory</span
                        >
                        <span v-else class="badge badge-secondary"
                          >Optional</span
                        >
                      </small>
                    </div>
                  </div>
                  <div>
                    <a
                      :href="fileUrl(f.filepath)"
                      target="_blank"
                      rel="noopener"
                      class="btn btn-sm btn-outline-success"
                      >Download</a
                    >
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 mb-3">
              <h6 class="mb-2">Additional Notes</h6>
              <p class="mb-0 text-muted">
                {{ application.additional_notes || "—" }}
              </p>
            </div>

            <div class="card border-0 shadow-sm p-3">
              <h6 class="mb-2">Actions</h6>
              <div class="d-grid">
                <button class="btn btn-success mb-2 w-100">
                  Start Evaluation
                </button>
                <button class="btn btn-outline-secondary w-100">
                  Mark Reviewed
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
