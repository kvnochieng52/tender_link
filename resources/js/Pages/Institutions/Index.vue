<script setup>
import { computed, ref } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  institutions: {
    type: Array,
    default: () => [],
  },
  institutionTypes: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success || null);

const showForm = ref(false);
const editingInstitutionId = ref(null);

const form = useForm({
  institution_name: "",
  institution_type_id: "",
  institution_email: "",
  institution_telephone: "",
  institution_website: "",
  institution_address: "",
  institution_profile: "",
  institution_logo: null,
});

const startCreate = () => {
  editingInstitutionId.value = null;
  showForm.value = true;
  form.reset();
  form.clearErrors();
};

const startEdit = (institution) => {
  editingInstitutionId.value = institution.id;
  showForm.value = true;
  form.clearErrors();

  form.institution_name = institution.institution_name || "";
  form.institution_type_id = institution.institution_type_id || "";
  form.institution_email = institution.email || "";
  form.institution_telephone = institution.telephone || "";
  form.institution_website = institution.website || "";
  form.institution_address = institution.address || "";
  form.institution_profile = institution.profile || "";
  form.institution_logo = null;
};

const cancelForm = () => {
  showForm.value = false;
  editingInstitutionId.value = null;
  form.reset();
  form.clearErrors();
};

const onLogoChange = (event) => {
  form.institution_logo = event.target.files?.[0] || null;
};

const submit = () => {
  if (editingInstitutionId.value) {
    form.post(route("institutions.update", editingInstitutionId.value), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        cancelForm();
      },
    });
    return;
  }

  form.post(route("institutions.store"), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      cancelForm();
    },
  });
};

const institutionTypeName = (institution) => {
  return institution.institution_type?.name || "—";
};

const logoUrl = (institution) => {
  if (!institution.logo) return null;
  return `/storage/${institution.logo}`;
};
</script>

<template>
  <Head title="Institutions" />

  <DashboardLayout>
    <div class="card border-0 shadow-sm institutions-hero mb-4">
      <div
        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between"
      >
        <div class="mb-3 mb-md-0">
          <h1 class="h5 font-weight-bold mb-1 text-white">My Institutions</h1>
          <p class="mb-0 text-white-50">
            Manage institutions you have created.
          </p>
        </div>
        <button
          type="button"
          class="btn btn-light font-weight-semibold"
          @click="startCreate"
        >
          <i class="fas fa-plus mr-1"></i> Add New Institution
        </button>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="alert alert-success border-0 shadow-sm py-2 mb-3"
    >
      <i class="fas fa-check-circle mr-1"></i>{{ successMessage }}
    </div>

    <div v-if="showForm" class="card border-0 shadow-sm mb-4">
      <div
        class="card-header bg-white border-0 pb-1 d-flex justify-content-between align-items-center"
      >
        <h5 class="font-weight-bold mb-0">
          {{
            editingInstitutionId ? "Edit Institution" : "Add New Institution"
          }}
        </h5>
        <button
          type="button"
          class="btn btn-sm btn-light border"
          @click="cancelForm"
        >
          <i class="fas fa-times mr-1"></i> Cancel
        </button>
      </div>
      <div class="card-body">
        <form @submit.prevent="submit" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Institution Name
                  <span class="required-asterisk">*</span></label
                >
                <input
                  v-model="form.institution_name"
                  type="text"
                  class="form-control"
                  placeholder="Institution name"
                />
                <small
                  v-if="form.errors.institution_name"
                  class="text-danger"
                  >{{ form.errors.institution_name }}</small
                >
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Institution Type
                  <span class="required-asterisk">*</span></label
                >
                <select v-model="form.institution_type_id" class="form-control">
                  <option value="">Select Institution Type</option>
                  <option
                    v-for="type in institutionTypes"
                    :key="type.id"
                    :value="type.id"
                  >
                    {{ type.name }}
                  </option>
                </select>
                <small
                  v-if="form.errors.institution_type_id"
                  class="text-danger"
                  >{{ form.errors.institution_type_id }}</small
                >
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold">Institution Email</label>
                <input
                  v-model="form.institution_email"
                  type="email"
                  class="form-control"
                  placeholder="info@institution.org"
                />
                <small
                  v-if="form.errors.institution_email"
                  class="text-danger"
                  >{{ form.errors.institution_email }}</small
                >
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Institution Telephone</label
                >
                <input
                  v-model="form.institution_telephone"
                  type="text"
                  class="form-control"
                  placeholder="e.g. 0712345678"
                />
                <small
                  v-if="form.errors.institution_telephone"
                  class="text-danger"
                  >{{ form.errors.institution_telephone }}</small
                >
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold">Institution Website</label>
                <input
                  v-model="form.institution_website"
                  type="url"
                  class="form-control"
                  placeholder="https://www.example.org"
                />
                <small
                  v-if="form.errors.institution_website"
                  class="text-danger"
                  >{{ form.errors.institution_website }}</small
                >
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold">Institution Address</label>
                <input
                  v-model="form.institution_address"
                  type="text"
                  class="form-control"
                  placeholder="e.g. 123 Main Street, Nairobi"
                />
                <small
                  v-if="form.errors.institution_address"
                  class="text-danger"
                  >{{ form.errors.institution_address }}</small
                >
              </div>
            </div>

            <div class="col-md-12">
              <div class="form-group">
                <label class="font-weight-semibold">Institution Profile</label>
                <textarea
                  v-model="form.institution_profile"
                  class="form-control"
                  rows="3"
                  placeholder="Brief profile of the institution"
                ></textarea>
                <small
                  v-if="form.errors.institution_profile"
                  class="text-danger"
                  >{{ form.errors.institution_profile }}</small
                >
              </div>
            </div>

            <div class="col-md-12">
              <div class="form-group mb-0">
                <label class="font-weight-semibold">Institution Logo</label>
                <input
                  type="file"
                  class="form-control-file"
                  accept="image/*"
                  @change="onLogoChange"
                />
                <small
                  v-if="form.errors.institution_logo"
                  class="text-danger"
                  >{{ form.errors.institution_logo }}</small
                >
              </div>
            </div>
          </div>

          <div class="mt-3 text-right">
            <button
              type="submit"
              class="btn btn-success"
              :disabled="form.processing"
            >
              <span v-if="form.processing"
                ><i class="fas fa-spinner fa-spin mr-1"></i>Saving...</span
              >
              <span v-else>
                <i class="fas fa-save mr-1"></i>
                {{
                  editingInstitutionId
                    ? "Update Institution"
                    : "Save Institution"
                }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 pb-1">
        <h5 class="font-weight-bold mb-0">Institutions List</h5>
      </div>
      <div class="card-body p-0">
        <div
          v-if="institutions.length === 0"
          class="text-center py-5 text-muted"
        >
          <i class="fas fa-building fa-2x mb-2 d-block"></i>
          No institutions yet. Click "Add New Institution" to create one.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover mb-0 institutions-table">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Institution</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Website</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(institution, idx) in institutions"
                :key="institution.id"
              >
                <td class="text-muted small">{{ idx + 1 }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <img
                      v-if="logoUrl(institution)"
                      :src="logoUrl(institution)"
                      :alt="institution.institution_name"
                      class="institution-logo mr-2"
                    />
                    <div v-else class="institution-logo-placeholder mr-2">
                      {{ institution.institution_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-weight-semibold">
                        {{ institution.institution_name }}
                      </div>
                      <small class="text-muted">{{
                        institution.address || "No address"
                      }}</small>
                    </div>
                  </div>
                </td>
                <td class="small">{{ institutionTypeName(institution) }}</td>
                <td class="small">
                  <div>{{ institution.email || "—" }}</div>
                  <div class="text-muted">
                    {{ institution.telephone || "—" }}
                  </div>
                </td>
                <td class="small">
                  <a
                    v-if="institution.website"
                    :href="institution.website"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-success"
                  >
                    {{ institution.website }}
                  </a>
                  <span v-else>—</span>
                </td>
                <td class="text-right">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    @click="startEdit(institution)"
                  >
                    <i class="fas fa-edit mr-1"></i> Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.institutions-hero {
  background: var(--brand-primary);
}

.required-asterisk {
  color: #dc3545;
  font-weight: 700;
}

.institutions-table th {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.institutions-table td {
  vertical-align: middle;
}

.institution-logo {
  width: 42px;
  height: 42px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #dce3f7;
}

.institution-logo-placeholder {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: var(--brand-primary);
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.font-weight-semibold {
  font-weight: 600;
}
</style>
