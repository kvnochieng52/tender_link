<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
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
  commonRequirements: { type: Array, default: () => [] },
});

const toast = useToast();
const page = usePage();
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
  tender_link_process: props.tender.tender_link_process ?? false,
  tender_fee_amount: props.tender.tender_fee_amount ?? null,
  requirements: [],
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

// Stepper state and navigation
const currentStep = ref(1);
const nextToRequirements = () => {
  submitted.value = true;
  if (isSubmitDisabled.value) {
    toast.warning(
      "Please complete required tender details before continuing.",
      { timeout: 5000 }
    );
    return;
  }

  currentStep.value = 2;
  nextTick(() => window.scrollTo({ top: 0, behavior: "smooth" }));
};

const backToStepOne = () => {
  currentStep.value = 1;
};

// If the URL contains a query param to open requirements, set the step
onMounted(() => {
  try {
    if (page.url && page.url.indexOf("step=requirements") !== -1) {
      currentStep.value = 2;
      nextTick(() => window.scrollTo({ top: 0, behavior: "smooth" }));
    }
  } catch (e) {
    // ignore
  }
});

// Requirements UI state (load from props)
const commonRequirementLibrary = ref(
  props.commonRequirements && props.commonRequirements.length
    ? props.commonRequirements.map((r) => ({ ...r }))
    : []
);

const commonRequirementSearch = ref("");
const showAutocomplete = ref(false);
const filteredCommonRequirements = computed(() => {
  if (!commonRequirementSearch.value.trim())
    return commonRequirementLibrary.value;
  const search = commonRequirementSearch.value.trim().toLowerCase();
  return commonRequirementLibrary.value.filter(
    (item) =>
      item.title.toLowerCase().includes(search) ||
      (item.notes || "").toLowerCase().includes(search)
  );
});

const selectedLibraryRequirementIds = ref([]);
const generatedRequirements = ref([]);
const customRequirementTitle = ref("");
const customRequirementNotes = ref("");
const customRequirementMandatory = ref(true);
const customRequirements = ref([]);
const showCustomRequirementForm = ref(false);
const editingRequirementTarget = ref(null);
const editingInlineIndex = ref(null);

// initialize from existing tender requirements
if (props.tender.requirements && props.tender.requirements.length) {
  const libIds = [];
  const customs = [];
  props.tender.requirements.forEach((r) => {
    if (r.source === "Library" && r.source_id) {
      libIds.push(r.source_id);
    } else {
      customs.push({
        title: r.title,
        notes: r.notes,
        mandatory: Boolean(r.mandatory),
        source: r.source || "Custom",
        id: r.id,
      });
    }
  });
  selectedLibraryRequirementIds.value = libIds;
  customRequirements.value = customs;
}

const selectedLibraryRequirements = computed(() =>
  commonRequirementLibrary.value
    .filter((item) => selectedLibraryRequirementIds.value.includes(item.id))
    .map((item) => ({
      id: item.id,
      title: item.title,
      notes: item.notes,
      mandatory: item.mandatory,
      source: "Library",
      sourceType: "library",
    }))
);

const allRequirementItems = computed(() => [
  ...generatedRequirements.value.map((item, index) => ({
    ...item,
    source: item.source || "Generated",
    sourceType: "generated",
    sourceIndex: index,
  })),
  ...selectedLibraryRequirements.value,
  ...customRequirements.value.map((item, index) => ({
    ...item,
    source: item.source || "Custom",
    sourceType: "custom",
    sourceIndex: index,
  })),
]);

const resetCustomRequirementForm = () => {
  customRequirementTitle.value = "";
  customRequirementNotes.value = "";
  customRequirementMandatory.value = true;
  editingRequirementTarget.value = null;
  editingInlineIndex.value = null;
  showCustomRequirementForm.value = false;
};

const openCustomRequirementForm = () => {
  editingInlineIndex.value = null;
  showCustomRequirementForm.value = !showCustomRequirementForm.value;
};

const addCustomRequirement = () => {
  if (!customRequirementTitle.value.trim()) return;

  const requirementPayload = {
    title: customRequirementTitle.value.trim(),
    notes: customRequirementNotes.value.trim(),
    mandatory: customRequirementMandatory.value,
    source: "Custom",
  };

  if (editingRequirementTarget.value?.sourceType === "custom") {
    customRequirements.value.splice(
      editingRequirementTarget.value.sourceIndex,
      1,
      requirementPayload
    );
  } else if (editingRequirementTarget.value?.sourceType === "generated") {
    generatedRequirements.value.splice(
      editingRequirementTarget.value.sourceIndex,
      1,
      {
        ...generatedRequirements.value[
          editingRequirementTarget.value.sourceIndex
        ],
        ...requirementPayload,
      }
    );
  } else {
    if (editingRequirementTarget.value?.sourceType === "library") {
      selectedLibraryRequirementIds.value =
        selectedLibraryRequirementIds.value.filter(
          (id) => id !== editingRequirementTarget.value.id
        );
    }
    customRequirements.value.push(requirementPayload);
  }

  resetCustomRequirementForm();
};

const removeCustomRequirement = (index) => {
  customRequirements.value = customRequirements.value.filter(
    (_, i) => i !== index
  );
};

const editRequirement = (item, index) => {
  customRequirementTitle.value = item.title || "";
  customRequirementNotes.value = item.notes || "";
  customRequirementMandatory.value = Boolean(item.mandatory);
  editingRequirementTarget.value = {
    sourceType: item.sourceType,
    sourceIndex: item.sourceIndex,
    id: item.id,
  };
  editingInlineIndex.value = index;
  showCustomRequirementForm.value = true;
};

const deleteRequirement = (item) => {
  if (item.sourceType === "custom") {
    removeCustomRequirement(item.sourceIndex);
    return;
  }
  if (item.sourceType === "generated") {
    generatedRequirements.value = generatedRequirements.value.filter(
      (_, i) => i !== item.sourceIndex
    );
    return;
  }
  if (item.sourceType === "library") {
    selectedLibraryRequirementIds.value =
      selectedLibraryRequirementIds.value.filter((id) => id !== item.id);
  }
};

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

// Reset to Step 1 if requirements are disabled while on Step 2
watch(
  () => form.tender_link_process,
  (newValue) => {
    if (!newValue && currentStep.value === 2) {
      currentStep.value = 1;
    }
  }
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

  // Only attach requirements if the requirements page is enabled
  if (form.tender_link_process) {
    form.requirements = allRequirementItems.value.map((r) => ({
      title: r.title,
      notes: r.notes || null,
      mandatory: !!r.mandatory,
      source: r.source || null,
      source_id: r.id || null,
    }));
  } else {
    // If requirements page is disabled, submit with empty requirements
    form.requirements = [];
  }

  // Build FormData manually so we can attach files + remove_file_ids
  form
    .transform((data) => {
      const fd = new FormData();

      const appendValue = (name, value) => {
        if (value === null || value === undefined) return;
        if (typeof value === "boolean") {
          fd.append(name, value ? "1" : "0");
          return;
        }
        fd.append(name, value);
      };

      Object.entries(data).forEach(([k, v]) => {
        if (v === null || v === undefined) return;

        // Handle arrays (including arrays of objects) so Laravel parses them as arrays
        if (Array.isArray(v)) {
          v.forEach((item, idx) => {
            if (item === null || item === undefined) return;
            if (typeof item === "object" && !(item instanceof File)) {
              Object.entries(item).forEach(([ik, iv]) => {
                if (iv === null || iv === undefined) return;
                if (typeof iv === "boolean") {
                  fd.append(`${k}[${idx}][${ik}]`, iv ? "1" : "0");
                } else {
                  fd.append(`${k}[${idx}][${ik}]`, iv);
                }
              });
            } else {
              // simple array values
              appendValue(`${k}[${idx}]`, item);
            }
          });

          return;
        }

        // Files (File objects) should be appended directly
        if (v instanceof File) {
          fd.append(k, v);
          return;
        }

        appendValue(k, v);
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
        // If the user updated from the Requirements step, take them back to the list
        if (currentStep.value === 2) {
          window.location.replace(route("tenders.index"));
          return;
        }

        // Otherwise navigate to the edit page with query to open Requirements step so state is consistent
        const url =
          route("tenders.edit", { encryptedId: props.encryptedId }) +
          "?step=requirements";
        window.location.replace(url);
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
      <div class="card border-0 shadow-sm mb-4 stepper-card">
        <div class="card-body py-3 px-3 px-md-4">
          <div class="stepper-wrap">
            <div
              class="stepper-item"
              :class="{ active: currentStep === 1, done: currentStep > 1 }"
              role="button"
              tabindex="0"
              @click="currentStep = 1"
              @keydown.enter="currentStep = 1"
            >
              <span class="stepper-dot">1</span>
              <div>
                <div class="stepper-title">Tender Details</div>
                <div class="stepper-subtitle">
                  Basic information, institution and files
                </div>
              </div>
            </div>
            <div v-if="form.tender_link_process" class="stepper-line"></div>
            <div
              v-if="form.tender_link_process"
              class="stepper-item"
              :class="{ active: currentStep === 2 }"
              role="button"
              tabindex="0"
              @click="currentStep = 2"
              @keydown.enter="currentStep = 2"
            >
              <span class="stepper-dot">2</span>
              <div>
                <div class="stepper-title">Requirements Setup</div>
                <div class="stepper-subtitle">
                  Generated list, library and custom requirements
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template v-if="currentStep === 1">
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
                    >Tender Title
                    <span class="required-asterisk">*</span></label
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
                  <small
                    v-if="submitted && !form.industry_id"
                    class="text-danger"
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
                  <small
                    v-if="submitted && !form.expiry_date"
                    class="text-danger"
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
      </template>

      <template v-if="currentStep === 2 && form.tender_link_process">
        <div class="mb-3">
          <button
            type="button"
            class="btn btn-sm btn-light"
            @click="backToStepOne"
          >
            <i class="fas fa-arrow-left mr-1"></i> Back to Details
          </button>
        </div>

        <div class="row requirements-layout-row">
          <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="requirements-left-column">
              <div class="card border-0 shadow-sm requirements-card">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Common Requirements</h5>
                </div>
                <div class="card-body">
                  <div class="mb-2">
                    <input
                      v-model="commonRequirementSearch"
                      type="search"
                      class="form-control form-control-sm"
                      placeholder="Search common requirements..."
                    />
                  </div>

                  <div
                    class="common-library-list"
                    style="
                      max-height: 720px;
                      overflow-y: auto;
                      overflow-x: hidden;
                    "
                  >
                    <div class="row">
                      <div
                        v-for="item in filteredCommonRequirements"
                        :key="item.id"
                        class="col-12 mb-3"
                      >
                        <label class="library-item w-100 mb-0">
                          <div class="d-flex align-items-start">
                            <input
                              v-model="selectedLibraryRequirementIds"
                              :value="item.id"
                              type="checkbox"
                              class="mt-1 mr-2"
                            />
                            <div>
                              <div class="font-weight-semibold text-dark">
                                {{ item.title }}
                              </div>
                              <small class="text-muted d-block">{{
                                item.notes
                              }}</small>
                            </div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-7">
            <div
              class="card border-0 shadow-sm mb-4 requirements-card requirements-preview-card"
            >
              <div
                class="card-header bg-white border-0 pb-1 d-flex justify-content-between align-items-center flex-wrap"
              >
                <h5 class="font-weight-bold mb-0">
                  Final Requirement Checklist Preview
                </h5>
                <span class="badge badge-success mt-2 mt-sm-0"
                  >{{ allRequirementItems.length }} item(s)</span
                >
              </div>
              <div class="card-body p-0">
                <div v-if="!allRequirementItems.length" class="p-3 text-muted">
                  No requirements added yet. Select from library or add custom
                  requirements.
                </div>
                <div v-else class="table-responsive">
                  <table class="table table-sm mb-0 requirements-preview-table">
                    <thead class="thead-light">
                      <tr>
                        <th style="width: 40px">#</th>
                        <th>Requirement Details</th>
                        <th style="width: 120px">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template
                        v-for="(item, index) in allRequirementItems"
                        :key="`${item.source}-${item.title}-${index}`"
                      >
                        <tr>
                          <td>{{ index + 1 }}</td>
                          <td>
                            <div class="font-weight-semibold text-dark">
                              {{ item.title }}
                            </div>
                            <small class="text-muted d-block mt-1">{{
                              item.notes || "No notes provided."
                            }}</small>
                            <span
                              class="mt-2 d-inline-block"
                              :class="
                                item.mandatory
                                  ? 'badge badge-danger'
                                  : 'badge badge-secondary'
                              "
                              >{{
                                item.mandatory ? "Mandatory" : "Optional"
                              }}</span
                            >
                          </td>
                          <td class="text-right">
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-primary mr-2"
                              @click="editRequirement(item, index)"
                              title="Edit requirement"
                            >
                              <i class="fas fa-pen"></i>
                            </button>
                            <button
                              type="button"
                              class="btn btn-sm btn-light border"
                              @click="deleteRequirement(item)"
                              title="Delete requirement"
                            >
                              <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                          </td>
                        </tr>

                        <tr v-if="editingInlineIndex === index">
                          <td colspan="3">
                            <div class="p-2">
                              <div
                                class="card border-0 shadow-sm requirements-card custom-requirement-card custom-requirement-form"
                              >
                                <div class="card-body py-2">
                                  <div class="form-row">
                                    <div class="col-12 mb-2">
                                      <input
                                        v-model="customRequirementTitle"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Requirement title"
                                      />
                                    </div>
                                    <div class="col-12 mb-2">
                                      <label
                                        class="font-weight-semibold d-block"
                                        >Requirement Type</label
                                      >
                                      <div
                                        class="form-check form-check-inline mr-3"
                                      >
                                        <input
                                          id="inlineReqMandatory"
                                          v-model="customRequirementMandatory"
                                          :value="true"
                                          class="form-check-input"
                                          type="radio"
                                        />
                                        <label
                                          class="form-check-label"
                                          for="inlineReqMandatory"
                                          >Mandatory</label
                                        >
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input
                                          id="inlineReqOptional"
                                          v-model="customRequirementMandatory"
                                          :value="false"
                                          class="form-check-input"
                                          type="radio"
                                        />
                                        <label
                                          class="form-check-label"
                                          for="inlineReqOptional"
                                          >Optional</label
                                        >
                                      </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                      <textarea
                                        v-model="customRequirementNotes"
                                        class="form-control form-control-sm"
                                        rows="2"
                                        placeholder="Notes (optional)"
                                      ></textarea>
                                    </div>
                                    <div
                                      class="col-12 d-flex justify-content-end"
                                    >
                                      <button
                                        type="button"
                                        class="btn btn-sm btn-outline-success mr-2"
                                        @click="addCustomRequirement"
                                      >
                                        <i
                                          class="fas"
                                          :class="
                                            editingRequirementTarget
                                              ? 'fa-save'
                                              : 'fa-plus'
                                          "
                                        ></i>
                                        {{
                                          editingRequirementTarget
                                            ? "Save"
                                            : "Add"
                                        }}
                                      </button>
                                      <button
                                        type="button"
                                        class="btn btn-sm btn-light border"
                                        @click="resetCustomRequirementForm"
                                      >
                                        Cancel
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer bg-white border-0 pt-0 pb-3 text-right">
                <button
                  type="button"
                  class="btn btn-outline-success"
                  @click="openCustomRequirementForm"
                >
                  <i
                    class="fas mr-1"
                    :class="showCustomRequirementForm ? 'fa-minus' : 'fa-plus'"
                  ></i>
                  {{
                    showCustomRequirementForm && editingInlineIndex === null
                      ? "Hide Form"
                      : "New Requirement"
                  }}
                </button>
              </div>
              <div
                v-if="showCustomRequirementForm && editingInlineIndex === null"
                class="px-3 pb-3"
              >
                <div
                  class="card border-0 shadow-sm requirements-card custom-requirement-card custom-requirement-form"
                >
                  <div class="card-header bg-white border-0 pb-1">
                    <h5 class="font-weight-bold mb-0">
                      {{
                        editingRequirementTarget
                          ? "Edit Requirement"
                          : "New Requirement"
                      }}
                    </h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-12">
                        <div class="form-group">
                          <label class="font-weight-semibold"
                            >Requirement Title</label
                          >
                          <input
                            v-model="customRequirementTitle"
                            type="text"
                            class="form-control"
                            placeholder="e.g. Site visit attendance certificate"
                          />
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-group">
                          <label class="font-weight-semibold d-block"
                            >Requirement Type</label
                          >
                          <div class="form-check form-check-inline mr-3">
                            <input
                              id="customReqMandatory"
                              v-model="customRequirementMandatory"
                              :value="true"
                              class="form-check-input"
                              type="radio"
                            />
                            <label
                              class="form-check-label"
                              for="customReqMandatory"
                              >Mandatory</label
                            >
                          </div>
                          <div class="form-check form-check-inline">
                            <input
                              id="customReqOptionalLarge"
                              v-model="customRequirementMandatory"
                              :value="false"
                              class="form-check-input"
                              type="radio"
                            />
                            <label
                              class="form-check-label"
                              for="customReqOptionalLarge"
                              >Optional</label
                            >
                          </div>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-group">
                          <label class="font-weight-semibold"
                            >Notes
                            <span class="optional-label"
                              >(optional)</span
                            ></label
                          >
                          <textarea
                            v-model="customRequirementNotes"
                            class="form-control"
                            rows="3"
                            placeholder="Additional notes (optional)"
                          ></textarea>
                        </div>
                      </div>
                      <div class="col-12 d-flex justify-content-end">
                        <button
                          type="button"
                          class="btn btn-outline-success mr-2"
                          @click="addCustomRequirement"
                        >
                          <i
                            class="fas"
                            :class="
                              editingRequirementTarget ? 'fa-save' : 'fa-plus'
                            "
                          ></i>
                          {{ editingRequirementTarget ? "Save" : "Add" }}
                        </button>
                        <button
                          type="button"
                          class="btn btn-light border"
                          @click="resetCustomRequirementForm"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template v-if="currentStep === 1">
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

            <div
              v-if="form.create_new_institution"
              class="new-institution-panel"
            >
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
                      selectedInstitution.institution_name
                        .charAt(0)
                        .toUpperCase()
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
                      <i
                        class="fas fa-map-marker-alt institution-meta-icon"
                      ></i>
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
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
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
                      >{{
                        institutionUpdateErrors.institution_type_id[0]
                      }}</small
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="font-weight-semibold"
                      >Email
                      <span class="optional-label">(optional)</span></label
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
                      >{{
                        institutionUpdateErrors.institution_website[0]
                      }}</small
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
                      >{{
                        institutionUpdateErrors.institution_address[0]
                      }}</small
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
                      >{{
                        institutionUpdateErrors.institution_profile[0]
                      }}</small
                    >
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group mb-3">
                    <label class="font-weight-semibold"
                      >Logo
                      <span class="optional-label">(optional)</span></label
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

        <!-- ── Tender Linking Process ─────────────────────────────────────── -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0 pb-1">
            <h5 class="font-weight-bold mb-0">Tender Linking Process</h5>
          </div>
          <div class="card-body">
            <div class="form-check mb-3">
              <input
                v-model="form.tender_link_process"
                type="checkbox"
                class="form-check-input"
                id="editTenderLinkProcess"
              />
              <label class="form-check-label" for="editTenderLinkProcess">
                <span class="font-weight-semibold"
                  >Enable Requirements Page</span
                >
                <small class="d-block text-muted mt-1">
                  When checked, the requirements page will be displayed for
                  applicants. You can also charge a tender fee.
                </small>
              </label>
              <small
                v-if="form.errors.tender_link_process"
                class="text-danger"
                >{{ form.errors.tender_link_process }}</small
              >
            </div>

            <div
              v-if="form.tender_link_process"
              class="alert alert-info border-0 mb-3"
            >
              <i class="fas fa-info-circle mr-2"></i>
              The requirements page is now enabled. You can optionally set a
              tender fee below.
            </div>

            <div v-if="form.tender_link_process" class="form-group">
              <label class="font-weight-semibold" for="editTenderFeeAmount">
                Tender Fee Amount
                <span class="optional-label">(optional)</span>
              </label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">KES</span>
                </div>
                <input
                  v-model.number="form.tender_fee_amount"
                  type="number"
                  class="form-control"
                  id="editTenderFeeAmount"
                  placeholder="0.00"
                  step="0.01"
                  min="0"
                />
              </div>
              <small class="text-muted d-block mt-1">
                Leave blank for no fee (defaults to null)
              </small>
              <small v-if="form.errors.tender_fee_amount" class="text-danger">{{
                form.errors.tender_fee_amount
              }}</small>
            </div>
          </div>
        </div>
      </template>

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
  border-radius: 0.8rem;
}

.stepper-card {
  border-radius: 0.75rem;
}

.stepper-wrap {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.stepper-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  opacity: 0.65;
}

.stepper-item {
  cursor: pointer;
}

.stepper-item.active,
.stepper-item.done {
  opacity: 1;
}

.stepper-dot {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  border: 1px solid #d6e9dc;
  background: #f8fbf9;
  color: #4f5f56;
}

.stepper-item.active .stepper-dot,
.stepper-item.done .stepper-dot {
  background: #28a745;
  color: #fff;
  border-color: #28a745;
}

.stepper-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #1f2d26;
}

.stepper-subtitle {
  font-size: 0.75rem;
  color: #6c757d;
}

.stepper-line {
  flex: 1;
  height: 1px;
  background: #e3ebe6;
}

.requirements-card {
  border-radius: 0.75rem;
}

.requirements-layout-row {
  align-items: flex-start;
}

.requirements-left-column {
  display: flex;
  flex-direction: column;
}

.custom-requirement-card {
  border: 1px solid #d7e9dc;
}

.custom-requirement-form .card-header h5 {
  font-size: 0.92rem;
}

.custom-requirement-form .card-body {
  padding: 0.85rem;
}

.custom-requirement-form .font-weight-semibold,
.custom-requirement-form .form-check-label {
  font-size: 0.82rem;
}

.custom-requirement-form .form-control {
  font-size: 0.82rem;
  padding: 0.28rem 0.55rem;
  min-height: calc(1.5em + 0.56rem + 2px);
}

.requirements-preview-card {
  min-height: 100%;
}

.library-item {
  border: 1px solid #e2ece5;
  border-radius: 0.6rem;
  padding: 0.7rem 0.8rem;
  background: #fff;
}

.library-item .font-weight-semibold {
  font-size: 0.9rem;
}

.library-item .text-muted {
  font-size: 0.78rem;
}

.library-item:hover {
  border-color: #b8d9c3;
  background: #fbfefc;
}

.requirements-preview-table td,
.requirements-preview-table th {
  vertical-align: middle;
}

.new-institution-panel {
  background: #f7faf8;
  border: 1px solid #e1ede5;
  border-radius: 0.65rem;
  padding: 1rem;
}

/* Institution profile card */
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

.institution-profile-text {
  font-size: 0.85rem;
  color: #6c757d;
  line-height: 1.5;
}

/* Edit institution panel */
.edit-institution-panel {
  background: #fff8f0;
  border: 1px solid #ffd580;
  border-radius: 0.65rem;
  padding: 1rem;
}

.edit-logo-thumb {
  width: 56px;
  height: 56px;
  object-fit: contain;
  border-radius: 0.4rem;
  border: 1px solid #dee2e6;
}

.optional-label {
  color: #6c757d;
  font-weight: 500;
  font-size: 0.85em;
}

.required-asterisk {
  color: #dc3545;
  font-weight: 700;
}

.submit-action-row {
  order: 4;
  padding-top: 0.5rem;
}

.file-drop-zone {
  border: 2px dashed rgba(40, 167, 69, 0.4);
  border-radius: 0.75rem;
  padding: 1.4rem 1rem;
  text-align: center;
  background: rgba(240, 250, 244, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-drop-zone:hover {
  background: rgba(240, 250, 244, 1);
  border-color: #28a745;
}

.file-drop-icon {
  font-size: 1.7rem;
  color: #1f8f53;
  margin-bottom: 0.55rem;
}

.selected-file-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #e6eee9;
  border-radius: 0.5rem;
  padding: 0.45rem 0.6rem;
  margin-bottom: 0.5rem;
  background: #fff;
}

:deep(.wysiwyg-editor .ql-toolbar) {
  border: 1px solid #ced4da;
  border-radius: 0.25rem 0.25rem 0 0;
}

:deep(.wysiwyg-editor .ql-container) {
  border: 1px solid #ced4da;
  border-top: 0;
  border-radius: 0 0 0.25rem 0.25rem;
  min-height: 180px;
}

:deep(.wysiwyg-editor .ql-editor) {
  min-height: 140px;
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

@media (max-width: 767.98px) {
  .stepper-wrap {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.6rem;
  }

  .stepper-line {
    width: 100%;
  }
}
</style>
