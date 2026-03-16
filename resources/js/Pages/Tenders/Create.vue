<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
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
  institutions: {
    type: Array,
    default: () => [],
  },
  institutionTypes: {
    type: Array,
    default: () => [],
  },
  industries: {
    type: Array,
    default: () => [],
  },
  counties: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const toast = useToast();
const submitted = ref(false);
const fileInput = ref(null);
const NEW_INSTITUTION_OPTION = "__new__";
const currentStep = ref(1);

const editingInstitution = ref(false);
const institutionUpdateSuccess = ref(null);

const requirementsSourceText = ref("");
const generatedRequirements = ref([]);
const selectedLibraryRequirementIds = ref([]);
const customRequirementTitle = ref("");
const customRequirementNotes = ref("");
const customRequirementMandatory = ref(true);
const customRequirements = ref([]);
const showCustomRequirementForm = ref(false);
const editingRequirementTarget = ref(null);

const commonRequirementLibrary = [
  {
    id: "certificate_incorporation",
    title: "Valid Certificate of Incorporation / Registration",
    notes: "Include change of particulars where applicable.",
    mandatory: true,
  },
  {
    id: "tax_compliance",
    title: "Valid Tax Compliance Certificate",
    notes: "Certificate must be valid at submission date.",
    mandatory: true,
  },
  {
    id: "cr12",
    title: "CR12 from Registrar of Companies",
    notes: "Issued within the last six (6) months.",
    mandatory: true,
  },
  {
    id: "form_of_tender",
    title: "Signed & Stamped Form of Tender",
    notes: "Use company letterhead.",
    mandatory: true,
  },
  {
    id: "bid_security",
    title: "Bid Security / Guarantee",
    notes: "Set amount and validity period in days.",
    mandatory: true,
  },
  {
    id: "business_permit",
    title: "Valid Business Permit",
    notes: "Issued by county government for current year.",
    mandatory: true,
  },
  {
    id: "audited_accounts",
    title: "Certified Audited Accounts",
    notes: "Attach required years and CPA/ICPAK details.",
    mandatory: true,
  },
  {
    id: "confidential_questionnaire",
    title: "Signed Confidential Business Questionnaire",
    notes: "Indicate physical, postal, telephone and email contacts.",
    mandatory: true,
  },
  {
    id: "independent_determination",
    title: "Certificate of Independent Tender Determination",
    notes: "Signed and stamped in company letterhead.",
    mandatory: true,
  },
];

// Local reactive copy so we can patch in-place without a page reload
const localInstitutions = ref(props.institutions.map((i) => ({ ...i })));

// Common Requirements search/autocomplete
const commonRequirementSearch = ref("");
const showAutocomplete = ref(false);
const filteredCommonRequirements = computed(() => {
  if (!commonRequirementSearch.value.trim()) return commonRequirementLibrary;
  const search = commonRequirementSearch.value.trim().toLowerCase();
  return commonRequirementLibrary.filter(
    (item) =>
      item.title.toLowerCase().includes(search) ||
      item.notes.toLowerCase().includes(search)
  );
});

const form = useForm({
  title: "",
  tender_no: "",
  institution_id: "",
  create_new_institution: false,
  edit_institution: false,
  institution_name: "",
  institution_type_id: "",
  institution_email: "",
  institution_telephone: "",
  institution_logo: null,
  institution_profile: "",
  institution_website: "",
  institution_address: "",
  industry_id: "",
  county_id: "",
  closing_date_and_time: "",
  expiry_date: "",
  description: "",
  key_requirements: "",
  files: [],
});

const closingDateConfig = {
  enableTime: true,
  dateFormat: "Y-m-d H:i:S",
  altInput: true,
  altFormat: "F j, Y h:i K",
  minDate: new Date(),
  minuteIncrement: 5,
};

const expiryDateConfig = {
  enableTime: true,
  dateFormat: "Y-m-d H:i:S",
  altInput: true,
  altFormat: "F j, Y h:i K",
  minuteIncrement: 5,
};

const quillOptions = {
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, false] }],
      ["bold", "italic", "underline", "strike"],
      [{ list: "ordered" }, { list: "bullet" }],
      [{ align: [] }],
      ["link", "blockquote"],
      ["clean"],
    ],
  },
};

const successMessage = computed(() => page.props.flash?.success || null);

const cleanRichText = (value) =>
  (value || "")
    .replace(/<[^>]*>/g, "")
    .replace(/&nbsp;/g, " ")
    .trim();

const institutionIsValid = computed(() => {
  if (form.create_new_institution) {
    return Boolean(form.institution_name && form.institution_type_id);
  }
  return Boolean(form.institution_id);
});

const isSubmitDisabled = computed(() => {
  return (
    !form.title?.trim() ||
    !form.tender_no?.trim() ||
    !form.industry_id ||
    !form.county_id ||
    !form.closing_date_and_time ||
    !form.expiry_date ||
    !cleanRichText(form.description) ||
    !form.files?.length ||
    !institutionIsValid.value ||
    form.processing
  );
});

const selectedLibraryRequirements = computed(() =>
  commonRequirementLibrary
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

const canFinalizeTender = computed(() => {
  return form.processing || allRequirementItems.value.length === 0;
});

const hasInstitutions = computed(() => localInstitutions.value.length > 0);

const industryOptions = computed(() =>
  props.industries.map((i) => ({ label: i.name, value: String(i.id) }))
);

const countyOptions = computed(() =>
  props.counties.map((c) => ({ label: c.name, value: String(c.id) }))
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
  hasInstitutions.value ? "" : NEW_INSTITUTION_OPTION
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

const institutionUpdating = ref(false);
const institutionUpdateErrors = ref({});

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

    // Patch the local institutions array so the profile card updates instantly
    const idx = localInstitutions.value.findIndex(
      (institution) => String(institution.id) === String(form.institution_id)
    );

    if (idx !== -1) {
      if (response.data?.institution) {
        localInstitutions.value[idx] = {
          ...localInstitutions.value[idx],
          ...response.data.institution,
        };
      } else {
        // Fall back: patch with what we submitted
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

const addSelectedFiles = (selectedFiles) => {
  if (!selectedFiles?.length) return;
  const incoming = Array.from(selectedFiles);
  const existing = form.files || [];
  form.files = [...existing, ...incoming];
  form.clearErrors("files", "files.*");
};

const onFilesSelected = (event) => {
  addSelectedFiles(event.target.files);
  event.target.value = "";
};

const onDropFiles = (event) => {
  event.preventDefault();
  addSelectedFiles(event.dataTransfer.files);
};

const openFilePicker = () => {
  fileInput.value?.click();
};

const removeFile = (index) => {
  form.files = form.files.filter((_, i) => i !== index);
};

const nextToRequirements = () => {
  submitted.value = true;
  if (isSubmitDisabled.value) {
    toast.warning(
      "Please complete required tender details before continuing.",
      { timeout: 5000 }
    );
    return;
  }

  if (!requirementsSourceText.value.trim()) {
    const source =
      cleanRichText(form.key_requirements) || cleanRichText(form.description);
    requirementsSourceText.value = source;
  }

  currentStep.value = 2;
};

const backToStepOne = () => {
  currentStep.value = 1;
};

const resetCustomRequirementForm = () => {
  customRequirementTitle.value = "";
  customRequirementNotes.value = "";
  customRequirementMandatory.value = true;
  editingRequirementTarget.value = null;
  showCustomRequirementForm.value = false;
};

const openCustomRequirementForm = () => {
  if (!showCustomRequirementForm.value) {
    customRequirementTitle.value = "";
    customRequirementNotes.value = "";
    customRequirementMandatory.value = true;
    editingRequirementTarget.value = null;
  }
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

const editRequirement = (item) => {
  customRequirementTitle.value = item.title || "";
  customRequirementNotes.value = item.notes || "";
  customRequirementMandatory.value = Boolean(item.mandatory);
  editingRequirementTarget.value = {
    sourceType: item.sourceType,
    sourceIndex: item.sourceIndex,
    id: item.id,
  };
  showCustomRequirementForm.value = true;
};

const deleteRequirement = (item) => {
  if (item.sourceType === "custom") {
    removeCustomRequirement(item.sourceIndex);
    return;
  }
  if (item.sourceType === "generated") {
    generatedRequirements.value = generatedRequirements.value.filter(
      (_, index) => index !== item.sourceIndex
    );
    return;
  }
  if (item.sourceType === "library") {
    selectedLibraryRequirementIds.value =
      selectedLibraryRequirementIds.value.filter((id) => id !== item.id);
  }
};

const submit = () => {
  submitted.value = true;
  if (isSubmitDisabled.value) {
    toast.warning("Please fill in all required fields before submitting.", {
      timeout: 5000,
    });
    return;
  }

  if (!form.files?.length) {
    form.setError("files", "Please upload at least one tender file.");
    toast.warning("Please upload at least one tender file.", { timeout: 5000 });
    return;
  }

  form.clearErrors("files", "files.*");

  form.post(route("tenders.store"), {
    forceFormData: true,
    onSuccess: () => {
      toast.success("Tender registered successfully!", { timeout: 4000 });
      submitted.value = false;
      currentStep.value = 1;
      requirementsSourceText.value = "";
      generatedRequirements.value = [];
      selectedLibraryRequirementIds.value = [];
      customRequirements.value = [];
      resetCustomRequirementForm();
      editingInstitution.value = false;
      form.reset(
        "title",
        "tender_no",
        "institution_id",
        "create_new_institution",
        "edit_institution",
        "institution_name",
        "institution_type_id",
        "institution_email",
        "institution_telephone",
        "institution_logo",
        "institution_profile",
        "institution_website",
        "institution_address",
        "industry_id",
        "county_id",
        "closing_date_and_time",
        "expiry_date",
        "description",
        "key_requirements",
        "files"
      );
    },
  });
};
</script>

<template>
  <Head title="Register Tender" />

  <DashboardLayout>
    <div class="card border-0 shadow-sm tender-page-hero mb-4">
      <div
        class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between"
      >
        <div class="mb-3 mb-md-0">
          <h1 class="h4 font-weight-bold mb-1 text-white">
            Tender Registration
          </h1>
          <p class="mb-0 text-white-50">
            Create and publish a tender with all required details and supporting
            files.
          </p>
        </div>
        <Link :href="route('dashboard')" class="btn btn-light btn-sm">
          <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </Link>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="alert alert-success border-0 shadow-sm mb-4"
    >
      <i class="fas fa-check-circle mr-2"></i>{{ successMessage }}
    </div>

    <form @submit.prevent="submit" class="tender-form-sections">
      <div class="card border-0 shadow-sm mb-4 stepper-card">
        <div class="card-body py-3 px-3 px-md-4">
          <div class="stepper-wrap">
            <div
              class="stepper-item"
              :class="{ active: currentStep === 1, done: currentStep > 1 }"
            >
              <span class="stepper-dot">1</span>
              <div>
                <div class="stepper-title">Tender Details</div>
                <div class="stepper-subtitle">
                  Basic information, institution and files
                </div>
              </div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item" :class="{ active: currentStep === 2 }">
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
        <div class="card border-0 shadow-sm mb-4 tender-details-card">
          <div class="card-header bg-white border-0 pb-1">
            <h5 class="font-weight-bold mb-0">Tender Details</h5>
          </div>
          <div class="card-body">
            <div class="row">
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

              <div class="col-md-6">
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

              <div class="col-md-6">
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

            <!-- Institution update success banner -->
            <div
              v-if="institutionUpdateSuccess && !editingInstitution"
              class="alert alert-success border-0 py-2 mb-3"
            >
              <i class="fas fa-check-circle mr-1"></i
              >{{ institutionUpdateSuccess }}
            </div>

            <!-- Profile card: existing institution selected, not editing -->
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

            <!-- Edit form: existing institution selected, editing mode -->
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
                  <div class="form-group mb-0">
                    <label class="font-weight-semibold"
                      >Logo
                      <span class="optional-label">(optional)</span></label
                    >
                    <div class="d-flex align-items-center">
                      <img
                        v-if="institutionLogoUrl"
                        :src="institutionLogoUrl"
                        class="edit-logo-thumb mr-3"
                        alt="Current logo"
                      />
                      <input
                        type="file"
                        class="form-control-file"
                        accept="image/*"
                        @change="onInstitutionLogoChange"
                      />
                    </div>
                    <small class="text-muted"
                      >Leave blank to keep current logo.</small
                    >
                    <small
                      v-if="institutionUpdateErrors.institution_logo"
                      class="text-danger d-block"
                      >{{ institutionUpdateErrors.institution_logo[0] }}</small
                    >
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-3 pt-2 border-top">
                <button
                  type="button"
                  class="btn btn-success"
                  :disabled="institutionUpdating"
                  @click="updateInstitution"
                >
                  <span v-if="institutionUpdating"
                    ><i class="fas fa-spinner fa-spin mr-1"></i>Saving...</span
                  >
                  <span v-else
                    ><i class="fas fa-save mr-1"></i>Update Institution
                    Details</span
                  >
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 tender-files-card">
          <div class="card-header bg-white border-0 pb-1">
            <h5 class="font-weight-bold mb-0">
              Tender Files <span class="required-asterisk">*</span>
            </h5>
          </div>
          <div class="card-body">
            <div
              class="file-drop-zone"
              @dragover.prevent
              @drop="onDropFiles"
              @click="openFilePicker"
            >
              <input
                ref="fileInput"
                type="file"
                class="d-none"
                multiple
                @change="onFilesSelected"
              />
              <i class="fas fa-cloud-upload-alt file-drop-icon"></i>
              <p class="mb-1 font-weight-semibold text-dark">
                Drag & drop files here
              </p>
              <p class="mb-0 text-muted small">
                or click to browse files (multiple supported)
              </p>
            </div>

            <small class="text-muted d-block mt-2"
              >At least one file is required.</small
            >

            <small v-if="form.errors.files" class="text-danger d-block mt-2">{{
              form.errors.files
            }}</small>
            <small
              v-if="submitted && !form.files?.length"
              class="text-danger d-block mt-1"
              >Please upload at least one tender file.</small
            >
            <small
              v-if="form.errors['files.*']"
              class="text-danger d-block mt-2"
              >{{ form.errors["files.*"] }}</small
            >

            <div v-if="form.files.length" class="mt-3">
              <div
                class="selected-file-row"
                v-for="(file, index) in form.files"
                :key="`${file.name}-${index}`"
              >
                <div class="text-truncate pr-2">
                  <i class="far fa-file-alt mr-2 text-success"></i
                  >{{ file.name }}
                </div>
                <button
                  type="button"
                  class="btn btn-sm btn-light border"
                  @click.stop="removeFile(index)"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          class="d-flex flex-wrap justify-content-end mt-4 mb-4 submit-action-row"
        >
          <button
            type="button"
            class="btn btn-success px-4"
            :disabled="form.processing"
            @click="nextToRequirements"
          >
            <span
              ><i class="fas fa-arrow-right mr-1"></i>Continue to
              Requirements</span
            >
          </button>
        </div>
      </template>

      <template v-else>
        <div class="card border-0 shadow-sm mb-4 requirements-card">
          <div
            class="card-header bg-white border-0 pb-1 d-flex justify-content-between align-items-center flex-wrap"
          >
            <div>
              <h5 class="font-weight-bold mb-1">
                Tender Requirements (Step 2)
              </h5>
              <small class="text-muted d-block">
                Select requirements to be submitted from our common requirements
                library or simply click on add new requirements to add more that
                you need.
              </small>
            </div>
          </div>
          <div class="card-body"></div>
        </div>

        <div class="row requirements-layout-row">
          <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="requirements-left-column">
              <div class="card border-0 shadow-sm requirements-card">
                <div class="card-header bg-white border-0 pb-1">
                  <h5 class="font-weight-bold mb-0">Common Requirements</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div
                      v-for="item in commonRequirementLibrary"
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
                      <tr
                        v-for="(item, index) in allRequirementItems"
                        :key="`${item.source}-${item.title}-${index}`"
                      >
                        <td>{{ index + 1 }}</td>
                        <td>
                          <div class="font-weight-semibold text-dark">
                            {{ item.title }}
                          </div>
                          <small class="text-muted d-block mt-1">
                            {{ item.notes || "No notes provided." }}
                          </small>
                          <span
                            class="mt-2 d-inline-block"
                            :class="
                              item.mandatory
                                ? 'badge badge-danger'
                                : 'badge badge-secondary'
                            "
                          >
                            {{ item.mandatory ? "Mandatory" : "Optional" }}
                          </span>
                        </td>
                        <td class="text-right">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-primary mr-2"
                            @click="editRequirement(item)"
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
                    showCustomRequirementForm ? "Hide Form" : "New Requirement"
                  }}
                </button>
              </div>
              <div v-if="showCustomRequirementForm" class="px-3 pb-3">
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
                              id="customReqOptional"
                              v-model="customRequirementMandatory"
                              :value="false"
                              class="form-check-input"
                              type="radio"
                            />
                            <label
                              class="form-check-label"
                              for="customReqOptional"
                              >Optional</label
                            >
                          </div>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-group mb-2">
                          <label class="font-weight-semibold"
                            >Notes / Instructions (optional)</label
                          >
                          <textarea
                            v-model="customRequirementNotes"
                            class="form-control"
                            rows="2"
                            placeholder="Add validation notes, period constraints, or formatting instructions"
                          ></textarea>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-success mr-2"
                            @click="addCustomRequirement"
                          >
                            <i
                              class="fas mr-1"
                              :class="
                                editingRequirementTarget ? 'fa-save' : 'fa-plus'
                              "
                            ></i>
                            {{
                              editingRequirementTarget
                                ? "Save Changes"
                                : "Add Requirement"
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
              </div>
            </div>
          </div>
        </div>

        <div
          class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-4 submit-action-row"
        >
          <button
            type="button"
            class="btn btn-light border"
            @click="backToStepOne"
          >
            <i class="fas fa-arrow-left mr-1"></i>Back to Step 1
          </button>
          <button
            type="submit"
            class="btn btn-success px-4"
            :disabled="canFinalizeTender"
          >
            <span v-if="form.processing"
              ><i class="fas fa-spinner fa-spin mr-1"></i>Submitting...</span
            >
            <span v-else><i class="fas fa-save mr-1"></i>Register Tender</span>
          </button>
        </div>
      </template>
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

.form-control:focus,
.custom-select:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.14);
}

.tender-form-sections {
  display: flex;
  flex-direction: column;
}

.tender-details-card {
  order: 1;
}

.tender-files-card {
  order: 2;
}

.institution-card {
  order: 3;
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