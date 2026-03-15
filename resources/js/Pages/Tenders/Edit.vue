<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import axios from "axios";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  tender: { type: Object, required: true },
  encryptedId: { type: String, required: true },
  institutions: { type: Array, default: () => [] },
  institutionTypes: { type: Array, default: () => [] },
  industries: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
});

const toast = useToast();
const submitted = ref(false);
const NEW_INSTITUTION_OPTION = "__new__";

const editingInstitution = ref(false);
const institutionUpdateSuccess = ref(null);
const institutionUpdating = ref(false);
const institutionUpdateErrors = ref({});
const localInstitutions = ref(props.institutions.map((i) => ({ ...i })));

// ── Date/time picker configs ──────────────────────────────────────────────────
const closingDateConfig = {
  enableTime: true,
  dateFormat: "Y-m-d H:i",
  minDate: "today",
  time_24hr: true,
};

const expiryDateConfig = {
  enableTime: true,
  dateFormat: "Y-m-d H:i:S",
  altInput: true,
  altFormat: "F j, Y h:i K",
  minuteIncrement: 5,
};

// ── Quill ────────────────────────────────────────────────────────────────────
const quillOptions = {
  placeholder: "",
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, false] }],
      ["bold", "italic", "underline"],
      [{ list: "ordered" }, { list: "bullet" }],
      ["link"],
      ["clean"],
    ],
  },
};

const cleanRichText = (html) => (html ?? "").replace(/<[^>]*>/g, "").trim();

// ── Existing files ────────────────────────────────────────────────────────────
const existingFiles = ref([...(props.tender.files ?? [])]);
const removeFileIds = ref([]);

const markRemove = (fileId) => {
  removeFileIds.value.push(fileId);
  existingFiles.value = existingFiles.value.filter((f) => f.id !== fileId);
};

const fileDownloadUrl = (filepath) => {
  if (!filepath) return "#";
  return `/storage/${filepath}`;
};

// ── New files (drag & drop) ───────────────────────────────────────────────────
const fileInput = ref(null);
const isDragging = ref(false);
const newFiles = ref([]);

const addFiles = (fileList) => {
  const accepted = Array.from(fileList).filter(
    (f) => !newFiles.value.some((e) => e.name === f.name && e.size === f.size)
  );
  newFiles.value = [...newFiles.value, ...accepted];
};

const onDrop = (e) => {
  isDragging.value = false;
  addFiles(e.dataTransfer.files);
};

const onFileInput = (e) => addFiles(e.target.files);
const removeNew = (idx) => {
  newFiles.value.splice(idx, 1);
};
const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / 1048576).toFixed(1) + " MB";
};

// ── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
  title: props.tender.title ?? "",
  tender_no: props.tender.tender_no ?? "",
  institution_id: props.tender.institution_id ?? "",
  create_new_institution: false,
  edit_institution: false,
  institution_name: props.tender.institution?.institution_name ?? "",
  institution_type_id: props.tender.institution?.institution_type_id ?? "",
  institution_email: props.tender.institution?.email ?? "",
  institution_telephone: props.tender.institution?.telephone ?? "",
  institution_logo: null,
  institution_profile: props.tender.institution?.profile ?? "",
  institution_website: props.tender.institution?.website ?? "",
  institution_address: props.tender.institution?.address ?? "",
  industry_id: props.tender.industry_id ?? "",
  county_id: props.tender.county_id ?? "",
  closing_date_and_time: props.tender.closing_date_and_time
    ? new Date(props.tender.closing_date_and_time)
        .toISOString()
        .slice(0, 16)
        .replace("T", " ")
    : "",
  expiry_date: props.tender.expiry_date
    ? new Date(props.tender.expiry_date)
        .toISOString()
        .slice(0, 19)
        .replace("T", " ")
    : "",
  tender_status_id: props.tender.tender_status_id ?? "",
  description: props.tender.description ?? "",
  key_requirements: props.tender.key_requirements ?? "",
});

const institutionIsValid = computed(() => {
  if (form.create_new_institution) {
    return Boolean(form.institution_name && form.institution_type_id);
  }

  return Boolean(form.institution_id);
});

const hasInstitutions = computed(() => localInstitutions.value.length > 0);

const industryOptions = computed(() =>
  props.industries.map((i) => ({ label: i.name, value: String(i.id) }))
);

const countyOptions = computed(() =>
  props.counties.map((c) => ({ label: c.name, value: String(c.id) }))
);

const statusOptions = computed(() =>
  props.statuses.map((s) => ({ label: s.name, value: String(s.id) }))
);

const institutionTypeOptions = computed(() =>
  props.institutionTypes.map((it) => ({ label: it.name, value: String(it.id) }))
);

const institutionSelectionOptions = computed(() => {
  const existing = localInstitutions.value.map((institution) => ({
    label: institution.institution_name,
    value: String(institution.id),
  }));

  return [
    ...existing,
    { label: "+ Add New Institution", value: NEW_INSTITUTION_OPTION },
  ];
});

const selectedIndustryOption = computed({
  get: () =>
    industryOptions.value.find(
      (option) => option.value === String(form.industry_id)
    ) || null,
  set: (option) => {
    form.industry_id = option?.value ?? "";
  },
});

const selectedCountyOption = computed({
  get: () =>
    countyOptions.value.find(
      (option) => option.value === String(form.county_id)
    ) || null,
  set: (option) => {
    form.county_id = option?.value ?? "";
  },
});

const selectedStatusOption = computed({
  get: () =>
    statusOptions.value.find(
      (option) => option.value === String(form.tender_status_id)
    ) || null,
  set: (option) => {
    form.tender_status_id = option?.value ?? "";
  },
});

const selectedInstitutionSelectionOption = computed({
  get: () =>
    institutionSelectionOptions.value.find(
      (option) => option.value === String(institutionSelection.value)
    ) || null,
  set: (option) => {
    institutionSelection.value = option?.value ?? "";
  },
});

const selectedInstitutionTypeOption = computed({
  get: () =>
    institutionTypeOptions.value.find(
      (option) => option.value === String(form.institution_type_id)
    ) || null,
  set: (option) => {
    form.institution_type_id = option?.value ?? "";
  },
});

const institutionSelection = ref(
  hasInstitutions.value
    ? form.institution_id
      ? String(form.institution_id)
      : ""
    : NEW_INSTITUTION_OPTION
);

const selectedInstitution = computed(() => {
  if (!form.institution_id) return null;

  return (
    localInstitutions.value.find(
      (institution) => String(institution.id) === String(form.institution_id)
    ) || null
  );
});

const institutionLogoUrl = computed(() => {
  if (!selectedInstitution.value?.logo) return null;
  return `/storage/${selectedInstitution.value.logo}`;
});

const applyInstitutionSelectionState = () => {
  editingInstitution.value = false;
  form.edit_institution = false;
  institutionUpdateSuccess.value = null;

  if (!hasInstitutions.value) {
    form.create_new_institution = true;
    form.institution_id = "";
    return;
  }

  if (institutionSelection.value === NEW_INSTITUTION_OPTION) {
    form.create_new_institution = true;
    form.institution_id = "";
    return;
  }

  if (institutionSelection.value) {
    form.create_new_institution = false;
    form.institution_id = institutionSelection.value;
    return;
  }

  form.create_new_institution = false;
  form.institution_id = "";
};

const startEditInstitution = () => {
  const inst = selectedInstitution.value;
  if (!inst) return;
  institutionUpdateSuccess.value = null;
  form.institution_name = inst.institution_name || "";
  form.institution_type_id = inst.institution_type_id || "";
  form.institution_email = inst.email || "";
  form.institution_telephone = inst.telephone || "";
  form.institution_website = inst.website || "";
  form.institution_profile = inst.profile || "";
  form.institution_address = inst.address || "";
  form.institution_logo = null;
  form.edit_institution = true;
  editingInstitution.value = true;
};

const cancelEditInstitution = () => {
  editingInstitution.value = false;
  form.edit_institution = false;
  form.institution_name = "";
  form.institution_type_id = "";
  form.institution_email = "";
  form.institution_telephone = "";
  form.institution_website = "";
  form.institution_profile = "";
  form.institution_address = "";
  form.institution_logo = null;
};

const updateInstitution = async () => {
  if (!form.institution_id) return;

  institutionUpdateErrors.value = {};
  institutionUpdateSuccess.value = null;

  const data = new FormData();
  data.append("institution_name", form.institution_name);
  data.append("institution_type_id", form.institution_type_id);
  data.append("institution_email", form.institution_email || "");
  data.append("institution_telephone", form.institution_telephone || "");
  data.append("institution_website", form.institution_website || "");
  data.append("institution_address", form.institution_address || "");
  data.append("institution_profile", form.institution_profile || "");
  if (form.institution_logo) {
    data.append("institution_logo", form.institution_logo);
  }

  institutionUpdating.value = true;

  try {
    const response = await axios.post(
      route("institutions.update", form.institution_id),
      data,
      { headers: { "Content-Type": "multipart/form-data" } }
    );

    const idx = localInstitutions.value.findIndex(
      (i) => String(i.id) === String(form.institution_id)
    );

    if (idx !== -1) {
      const updated = response.data?.institution;
      if (updated) {
        localInstitutions.value[idx] = {
          ...localInstitutions.value[idx],
          ...updated,
        };
      } else {
        localInstitutions.value[idx] = {
          ...localInstitutions.value[idx],
          institution_name: form.institution_name,
          institution_type_id: form.institution_type_id,
          email: form.institution_email,
          telephone: form.institution_telephone,
          website: form.institution_website,
          address: form.institution_address,
          profile: form.institution_profile,
        };
      }
    }

    institutionUpdateSuccess.value = "Institution updated successfully.";
    editingInstitution.value = false;
    form.edit_institution = false;
  } catch (error) {
    if (error.response?.status === 422) {
      institutionUpdateErrors.value = error.response.data.errors || {};
    } else {
      institutionUpdateSuccess.value = null;
      institutionUpdateErrors.value = {
        general: ["An error occurred. Please try again."],
      };
    }
  } finally {
    institutionUpdating.value = false;
  }
};

watch(
  () => institutionSelection.value,
  () => {
    applyInstitutionSelectionState();
  },
  { immediate: true }
);

const onInstitutionLogoChange = (event) => {
  const file = event.target.files?.[0] || null;
  form.institution_logo = file;
};

const isSubmitDisabled = computed(
  () =>
    !form.title?.trim() ||
    !form.tender_no?.trim() ||
    !institutionIsValid.value ||
    !form.industry_id ||
    !form.county_id ||
    !form.closing_date_and_time ||
    !form.expiry_date ||
    !cleanRichText(form.description) ||
    form.processing
);

const submit = () => {
  submitted.value = true;

  if (isSubmitDisabled.value) {
    toast.warning("Please fill in all required fields before submitting.");
    return;
  }

  // Build FormData manually so we can attach files + remove_file_ids
  form
    .transform((data) => {
      const fd = new FormData();

      Object.entries(data).forEach(([k, v]) => {
        if (v === null || v === undefined) return;

        if (typeof v === "boolean") {
          fd.append(k, v ? "1" : "0");
          return;
        }

        fd.append(k, v);
      });

      // _method spoofing for PUT
      fd.append("_method", "PUT");

      newFiles.value.forEach((f) => fd.append("files[]", f));
      removeFileIds.value.forEach((id) => fd.append("remove_file_ids[]", id));

      return fd;
    })
    .post(route("tenders.update", { encryptedId: props.encryptedId }), {
      forceFormData: true,
      onSuccess: () => {
        toast.success("Tender updated successfully!");
        submitted.value = false;
        newFiles.value = [];
        removeFileIds.value = [];
      },
      onError: () => {
        toast.error("Please fix the errors below and try again.");
      },
    });
};
</script>

<template>
  <Head title="Edit Tender" />

  <DashboardLayout>
    <!-- Hero -->
    <div class="card border-0 shadow-sm tender-page-hero mb-4">
      <div
        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between"
      >
        <div class="mb-3 mb-md-0">
          <h1 class="h4 font-weight-bold mb-1 text-white">Edit Tender</h1>
          <p class="mb-0 text-white-50">
            Update tender details, files and status.
          </p>
        </div>
        <Link
          :href="route('tenders.index')"
          class="btn btn-light font-weight-semibold"
        >
          <i class="fas fa-arrow-left mr-1"></i> Back to List
        </Link>
      </div>
    </div>

    <form @submit.prevent="submit" enctype="multipart/form-data">
      <!-- ── Tender Details ─────────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-1">
          <h5 class="font-weight-bold mb-0">Tender Details</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- Title -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Tender Title <span class="required-asterisk">*</span></label
                >
                <input
                  v-model="form.title"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Supply of Office Equipment"
                />
                <small v-if="form.errors.title" class="text-danger">{{
                  form.errors.title
                }}</small>
                <small
                  v-if="submitted && !form.title?.trim()"
                  class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Tender No -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Tender No <span class="required-asterisk">*</span></label
                >
                <input
                  v-model="form.tender_no"
                  type="text"
                  class="form-control"
                  placeholder="e.g. TL/2026/001"
                />
                <small v-if="form.errors.tender_no" class="text-danger">{{
                  form.errors.tender_no
                }}</small>
                <small
                  v-if="submitted && !form.tender_no?.trim()"
                  class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Industry -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Industry <span class="required-asterisk">*</span></label
                >
                <v-select
                  v-model="selectedIndustryOption"
                  :options="industryOptions"
                  label="label"
                  :reduce="(option) => option"
                  placeholder="Select Industry"
                  class="select2-like"
                  :clearable="true"
                  append-to-body
                />
                <small v-if="form.errors.industry_id" class="text-danger">{{
                  form.errors.industry_id
                }}</small>
                <small v-if="submitted && !form.industry_id" class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- County -->
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >County <span class="required-asterisk">*</span></label
                >
                <v-select
                  v-model="selectedCountyOption"
                  :options="countyOptions"
                  label="label"
                  :reduce="(option) => option"
                  placeholder="Select County"
                  class="select2-like"
                  :clearable="true"
                  append-to-body
                />
                <small v-if="form.errors.county_id" class="text-danger">{{
                  form.errors.county_id
                }}</small>
                <small v-if="submitted && !form.county_id" class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Closing Date -->
            <div class="col-md-4">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Closing Date & Time
                  <span class="required-asterisk">*</span></label
                >
                <flat-pickr
                  v-model="form.closing_date_and_time"
                  class="form-control"
                  :config="closingDateConfig"
                  placeholder="Select closing date and time"
                />
                <small
                  v-if="form.errors.closing_date_and_time"
                  class="text-danger"
                  >{{ form.errors.closing_date_and_time }}</small
                >
                <small
                  v-if="submitted && !form.closing_date_and_time"
                  class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Expiry Date -->
            <div class="col-md-4">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Expiry Date & Time
                  <span class="required-asterisk">*</span></label
                >
                <flat-pickr
                  v-model="form.expiry_date"
                  class="form-control"
                  :config="expiryDateConfig"
                  placeholder="Select expiry date and time"
                />
                <small v-if="form.errors.expiry_date" class="text-danger">{{
                  form.errors.expiry_date
                }}</small>
                <small v-if="submitted && !form.expiry_date" class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Status -->
            <div class="col-md-4">
              <div class="form-group">
                <label class="font-weight-semibold">Status</label>
                <v-select
                  v-model="selectedStatusOption"
                  :options="statusOptions"
                  label="label"
                  :reduce="(option) => option"
                  placeholder="Select Status"
                  class="select2-like"
                  :clearable="true"
                  append-to-body
                />
                <small
                  v-if="form.errors.tender_status_id"
                  class="text-danger"
                  >{{ form.errors.tender_status_id }}</small
                >
              </div>
            </div>

            <!-- Description -->
            <div class="col-md-12">
              <div class="form-group">
                <label class="font-weight-semibold"
                  >Description <span class="required-asterisk">*</span></label
                >
                <QuillEditor
                  v-model:content="form.description"
                  contentType="html"
                  theme="snow"
                  :options="quillOptions"
                  class="wysiwyg-editor"
                  placeholder="Tender scope and summary"
                />
                <small v-if="form.errors.description" class="text-danger">{{
                  form.errors.description
                }}</small>
                <small
                  v-if="submitted && !cleanRichText(form.description)"
                  class="text-danger"
                  >This field is required.</small
                >
              </div>
            </div>

            <!-- Key Requirements -->
            <div class="col-md-12">
              <div class="form-group mb-0">
                <label class="font-weight-semibold"
                  >Key Requirements
                  <span class="optional-label">(optional)</span></label
                >
                <QuillEditor
                  v-model:content="form.key_requirements"
                  contentType="html"
                  theme="snow"
                  :options="quillOptions"
                  class="wysiwyg-editor"
                  placeholder="Mandatory qualifications and conditions"
                />
                <small
                  v-if="form.errors.key_requirements"
                  class="text-danger"
                  >{{ form.errors.key_requirements }}</small
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-4 institution-card">
        <div class="card-header bg-white border-0 pb-1">
          <h5 class="font-weight-bold mb-0">Institution</h5>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label v-if="hasInstitutions" class="font-weight-semibold"
              >Select Institution
              <span class="required-asterisk">*</span></label
            >

            <v-select
              v-if="hasInstitutions"
              v-model="selectedInstitutionSelectionOption"
              :options="institutionSelectionOptions"
              label="label"
              :reduce="(option) => option"
              placeholder="Select Existing Institution"
              class="select2-like"
              :clearable="true"
              append-to-body
            />

            <div v-else class="alert alert-light border mb-3">
              No institution found. Please add a new institution below.
            </div>

            <small v-if="form.errors.institution_id" class="text-danger">{{
              form.errors.institution_id
            }}</small>
            <small v-if="submitted && !institutionIsValid" class="text-danger"
              >Please select or add an institution.</small
            >
          </div>

          <div v-if="form.create_new_institution" class="new-institution-panel">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold">Institution Name</label>
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
                  <label class="font-weight-semibold">Institution Type</label>
                  <v-select
                    v-model="selectedInstitutionTypeOption"
                    :options="institutionTypeOptions"
                    label="label"
                    :reduce="(option) => option"
                    placeholder="Select Institution Type"
                    class="select2-like"
                    :clearable="true"
                    append-to-body
                  />
                  <small
                    v-if="form.errors.institution_type_id"
                    class="text-danger"
                    >{{ form.errors.institution_type_id }}</small
                  >
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Institution Email
                    <span class="optional-label">(optional)</span></label
                  >
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
                    >Institution Telephone
                    <span class="optional-label">(optional)</span></label
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
                  <label class="font-weight-semibold"
                    >Institution Website
                    <span class="optional-label">(optional)</span></label
                  >
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
                  <label class="font-weight-semibold"
                    >Institution Address
                    <span class="optional-label">(optional)</span></label
                  >
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
                  <label class="font-weight-semibold"
                    >Institution Profile
                    <span class="optional-label">(optional)</span></label
                  >
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
                  <label class="font-weight-semibold"
                    >Institution Logo
                    <span class="optional-label">(optional)</span></label
                  >
                  <input
                    type="file"
                    class="form-control-file"
                    accept="image/*"
                    @change="onInstitutionLogoChange"
                  />
                  <small
                    v-if="form.errors.institution_logo"
                    class="text-danger"
                    >{{ form.errors.institution_logo }}</small
                  >
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="institutionUpdateSuccess && !editingInstitution"
            class="alert alert-success border-0 py-2 mb-3"
          >
            <i class="fas fa-check-circle mr-1"></i
            >{{ institutionUpdateSuccess }}
          </div>

          <div
            v-if="selectedInstitution && !editingInstitution"
            class="institution-profile-card"
          >
            <div class="institution-profile-card-inner">
              <div class="institution-logo-col">
                <img
                  v-if="institutionLogoUrl"
                  :src="institutionLogoUrl"
                  :alt="selectedInstitution.institution_name"
                  class="institution-logo-img"
                />
                <div v-else class="institution-logo-placeholder">
                  {{
                    selectedInstitution.institution_name.charAt(0).toUpperCase()
                  }}
                </div>
              </div>

              <div class="institution-info-col">
                <div
                  class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2"
                >
                  <div>
                    <h6 class="institution-profile-name mb-0">
                      {{ selectedInstitution.institution_name }}
                    </h6>
                    <span
                      v-if="selectedInstitution.institution_type?.name"
                      class="institution-type-badge"
                      >{{ selectedInstitution.institution_type.name }}</span
                    >
                  </div>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-success"
                    @click="startEditInstitution"
                  >
                    <i class="fas fa-edit mr-1"></i>Edit Institution
                  </button>
                </div>

                <div class="institution-meta-grid">
                  <div
                    v-if="selectedInstitution.email"
                    class="institution-meta-item"
                  >
                    <i class="fas fa-envelope institution-meta-icon"></i>
                    <span>{{ selectedInstitution.email }}</span>
                  </div>
                  <div
                    v-if="selectedInstitution.telephone"
                    class="institution-meta-item"
                  >
                    <i class="fas fa-phone institution-meta-icon"></i>
                    <span>{{ selectedInstitution.telephone }}</span>
                  </div>
                  <div
                    v-if="selectedInstitution.website"
                    class="institution-meta-item"
                  >
                    <i class="fas fa-globe institution-meta-icon"></i>
                    <a
                      :href="selectedInstitution.website"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-success"
                      >{{ selectedInstitution.website }}</a
                    >
                  </div>
                  <div
                    v-if="selectedInstitution.address"
                    class="institution-meta-item"
                  >
                    <i class="fas fa-map-marker-alt institution-meta-icon"></i>
                    <span>{{ selectedInstitution.address }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="selectedInstitution && editingInstitution"
            class="edit-institution-panel"
          >
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="font-weight-bold mb-0 text-success">
                <i class="fas fa-edit mr-1"></i>Editing Institution
              </h6>
              <button
                type="button"
                class="btn btn-sm btn-light border text-muted"
                @click="cancelEditInstitution"
              >
                <i class="fas fa-times mr-1"></i>Cancel Edit
              </button>
            </div>

            <div
              v-if="institutionUpdateErrors.general"
              class="alert alert-danger border-0 py-2 mb-3"
            >
              <i class="fas fa-exclamation-circle mr-1"></i
              >{{ institutionUpdateErrors.general[0] }}
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold">Institution Name</label>
                  <input
                    v-model="form.institution_name"
                    type="text"
                    class="form-control"
                    placeholder="Institution name"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_name"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_name[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold">Institution Type</label>
                  <v-select
                    v-model="selectedInstitutionTypeOption"
                    :options="institutionTypeOptions"
                    label="label"
                    :reduce="(option) => option"
                    placeholder="Select Institution Type"
                    class="select2-like"
                    :clearable="true"
                    append-to-body
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_type_id"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_type_id[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Email <span class="optional-label">(optional)</span></label
                  >
                  <input
                    v-model="form.institution_email"
                    type="email"
                    class="form-control"
                    placeholder="info@institution.org"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_email"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_email[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Telephone
                    <span class="optional-label">(optional)</span></label
                  >
                  <input
                    v-model="form.institution_telephone"
                    type="text"
                    class="form-control"
                    placeholder="e.g. 0712345678"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_telephone"
                    class="text-danger"
                    >{{
                      institutionUpdateErrors.institution_telephone[0]
                    }}</small
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Website
                    <span class="optional-label">(optional)</span></label
                  >
                  <input
                    v-model="form.institution_website"
                    type="url"
                    class="form-control"
                    placeholder="https://www.example.org"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_website"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_website[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Address
                    <span class="optional-label">(optional)</span></label
                  >
                  <input
                    v-model="form.institution_address"
                    type="text"
                    class="form-control"
                    placeholder="e.g. 123 Main Street, Nairobi"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_address"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_address[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group">
                  <label class="font-weight-semibold"
                    >Profile
                    <span class="optional-label">(optional)</span></label
                  >
                  <textarea
                    v-model="form.institution_profile"
                    class="form-control"
                    rows="3"
                    placeholder="Brief profile of the institution"
                  ></textarea>
                  <small
                    v-if="institutionUpdateErrors.institution_profile"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_profile[0] }}</small
                  >
                </div>
              </div>
              <div class="col-md-12">
                <div class="form-group mb-3">
                  <label class="font-weight-semibold"
                    >Logo <span class="optional-label">(optional)</span></label
                  >
                  <input
                    type="file"
                    class="form-control-file"
                    accept="image/*"
                    @change="onInstitutionLogoChange"
                  />
                  <small
                    v-if="institutionUpdateErrors.institution_logo"
                    class="text-danger"
                    >{{ institutionUpdateErrors.institution_logo[0] }}</small
                  >
                </div>
              </div>
            </div>

            <button
              type="button"
              class="btn btn-success"
              :disabled="institutionUpdating"
              @click="updateInstitution"
            >
              <span v-if="institutionUpdating">
                <i class="fas fa-spinner fa-spin mr-1"></i>Updating...
              </span>
              <span v-else>
                <i class="fas fa-save mr-1"></i>Update Institution Details
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- ── Tender Files ───────────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-1">
          <h5 class="font-weight-bold mb-0">Tender Files</h5>
        </div>
        <div class="card-body">
          <!-- Existing files -->
          <div v-if="existingFiles.length" class="mb-3">
            <p class="font-weight-semibold small text-muted mb-2">
              Existing Files
            </p>
            <div
              v-for="file in existingFiles"
              :key="file.id"
              class="d-flex align-items-center justify-content-between border rounded p-2 mb-1"
            >
              <div class="d-flex align-items-center">
                <i class="fas fa-file-alt text-success mr-2"></i>
                <span class="small">{{ file.file_name }}</span>
              </div>
              <div class="d-flex align-items-center">
                <a
                  :href="fileDownloadUrl(file.filepath)"
                  :download="file.file_name"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-sm btn-outline-success py-0 px-2 mr-1"
                  title="Download file"
                >
                  <i class="fas fa-download"></i>
                </a>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger py-0 px-2"
                  @click="markRemove(file.id)"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Drop zone -->
          <div
            class="drop-zone"
            :class="{ 'drag-over': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="onDrop"
            @click="fileInput.click()"
          >
            <i class="fas fa-cloud-upload-alt fa-2x text-success mb-2"></i>
            <p class="mb-0 text-muted small">
              Drag & drop files here or
              <span class="text-success font-weight-semibold"
                >click to browse</span
              >
            </p>
            <input
              ref="fileInput"
              type="file"
              multiple
              class="d-none"
              @change="onFileInput"
            />
          </div>

          <!-- New files queue -->
          <div v-if="newFiles.length" class="mt-3">
            <p class="font-weight-semibold small text-muted mb-2">
              New Files to Upload
            </p>
            <div
              v-for="(f, i) in newFiles"
              :key="i"
              class="d-flex align-items-center justify-content-between border rounded p-2 mb-1"
            >
              <div class="d-flex align-items-center">
                <i class="fas fa-file text-primary mr-2"></i>
                <div>
                  <div class="small font-weight-semibold">{{ f.name }}</div>
                  <div class="text-muted" style="font-size: 0.72rem">
                    {{ formatSize(f.size) }}
                  </div>
                </div>
              </div>
              <button
                type="button"
                class="btn btn-sm btn-outline-danger py-0 px-2"
                @click="removeNew(i)"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Submit ─────────────────────────────────────────────────────── -->
      <div class="d-flex justify-content-end pt-1">
        <Link
          :href="route('tenders.index')"
          class="btn btn-outline-secondary mr-2"
          >Cancel</Link
        >
        <button
          type="submit"
          class="btn btn-success px-4"
          :disabled="form.processing"
        >
          <span v-if="form.processing"
            ><i class="fas fa-spinner fa-spin mr-1"></i>Saving…</span
          >
          <span v-else><i class="fas fa-save mr-1"></i>Update Tender</span>
        </button>
      </div>
    </form>
  </DashboardLayout>
</template>

<style scoped>
.tender-page-hero {
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
}

.new-institution-panel {
  background: #f7faf8;
  border: 1px solid #e1ede5;
  border-radius: 0.65rem;
  padding: 1rem;
}

.institution-profile-card {
  border: 1px solid #d4ead9;
  border-radius: 0.75rem;
  background: linear-gradient(135deg, #f0f9f3 0%, #ffffff 100%);
  padding: 1.1rem;
}

.institution-profile-card-inner {
  display: flex;
  gap: 1.1rem;
  align-items: flex-start;
}

.institution-logo-col {
  flex-shrink: 0;
}

.institution-logo-img {
  width: 72px;
  height: 72px;
  object-fit: contain;
  border-radius: 0.5rem;
  border: 1px solid #d4ead9;
  background: #fff;
  padding: 4px;
}

.institution-logo-placeholder {
  width: 72px;
  height: 72px;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, #28a745 0%, #1f8f53 100%);
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.institution-info-col {
  flex: 1;
  min-width: 0;
}

.institution-profile-name {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a3a22;
}

.institution-type-badge {
  display: inline-block;
  background: rgba(40, 167, 69, 0.12);
  color: #1f8f53;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.55rem;
  border-radius: 1rem;
  margin-top: 0.2rem;
}

.institution-meta-grid {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-top: 0.5rem;
}

.institution-meta-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.875rem;
  color: #495057;
}

.institution-meta-icon {
  color: #28a745;
  width: 14px;
  flex-shrink: 0;
}

.edit-institution-panel {
  background: #fff8f0;
  border: 1px solid #ffd580;
  border-radius: 0.65rem;
  padding: 1rem;
}

.required-asterisk {
  color: #dc3545;
}

.optional-label {
  color: #6c757d;
  font-size: 0.8rem;
  font-weight: 400;
}

.font-weight-semibold {
  font-weight: 600;
}

.wysiwyg-editor {
  min-height: 160px;
}

.drop-zone {
  border: 2px dashed #c3d9cb;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}

.drop-zone:hover,
.drop-zone.drag-over {
  border-color: #28a745;
  background: rgba(40, 167, 69, 0.04);
}

:deep(.select2-like .vs__dropdown-toggle) {
  min-height: 38px;
  border-color: #ced4da;
  cursor: pointer;
}

:deep(.select2-like .vs__selected-options) {
  padding-left: 2px;
}

:deep(.select2-like .vs__search),
:deep(.select2-like .vs__selected) {
  font-size: 0.875rem;
  cursor: pointer;
}

:deep(.select2-like .vs__actions) {
  padding-right: 6px;
}

:deep(.select2-like .vs__open-indicator),
:deep(.select2-like .vs__clear) {
  cursor: pointer;
}

:global(body .vs__dropdown-menu) {
  z-index: 25000 !important;
}

:global(body .vs__dropdown-option) {
  position: relative;
  z-index: 25001;
}
</style>
