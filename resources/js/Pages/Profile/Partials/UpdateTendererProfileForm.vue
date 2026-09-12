<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
  tendererProfile: { type: Object, default: null },
  counties: { type: Array, default: () => [] },
  industries: { type: Array, default: () => [] },
});

const p = props.tendererProfile ?? {};

const form = useForm({
  business_name: p.business_name ?? "",
  trading_name: p.trading_name ?? "",
  registration_number: p.registration_number ?? "",
  kra_pin: p.kra_pin ?? "",
  business_type: p.business_type ?? "",
  year_of_registration: p.year_of_registration ?? "",
  industry_id: p.industry_id ?? "",
  county_id: p.county_id ?? "",
  physical_address: p.physical_address ?? "",
  postal_address: p.postal_address ?? "",
  website: p.website ?? "",
  business_description: p.business_description ?? "",
  certifications: p.certifications ?? "",
  years_of_experience: p.years_of_experience ?? "",
  annual_turnover: p.annual_turnover ?? "",
  contact_person_name: p.contact_person_name ?? "",
  contact_person_position: p.contact_person_position ?? "",
  contact_phone: p.contact_phone ?? "",
  contact_email: p.contact_email ?? "",
});

const businessTypes = [
  "Sole Proprietorship",
  "Partnership",
  "Limited Liability Partnership",
  "Private Limited Company",
  "Public Limited Company",
  "Cooperative",
  "NGO",
  "Other",
];

const submit = () => {
  form.patch(route("profile.tenderer.update"), { preserveScroll: true });
};
</script>

<template>
  <form @submit.prevent="submit">
    <p class="text-muted small mb-4">
      This information is used to pre-fill your tender applications. Fields
      marked with <span class="text-danger">*</span> are required.
    </p>

    <!-- ── Company Identity ────────────────────────────────────────── -->
    <h6 class="text-uppercase text-muted small font-weight-bold mb-3">
      Company Identity
    </h6>
    <div class="row">
      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Business Name <span class="text-danger">*</span>
        </label>
        <input
          v-model="form.business_name"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.business_name }"
          placeholder="Registered business name"
        />
        <div v-if="form.errors.business_name" class="invalid-feedback">
          {{ form.errors.business_name }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Trading Name
          <small class="text-muted font-weight-normal">
            (if different from Business Name)
          </small>
        </label>
        <input
          v-model="form.trading_name"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.trading_name }"
          placeholder="Trading / brand name"
        />
        <div v-if="form.errors.trading_name" class="invalid-feedback">
          {{ form.errors.trading_name }}
        </div>
      </div>

      <div class="col-md-4 form-group mb-3">
        <label class="small font-weight-bold mb-1">Registration Number</label>
        <input
          v-model="form.registration_number"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.registration_number }"
          placeholder="e.g. PVT-XXXXXX"
        />
        <div v-if="form.errors.registration_number" class="invalid-feedback">
          {{ form.errors.registration_number }}
        </div>
      </div>

      <div class="col-md-4 form-group mb-3">
        <label class="small font-weight-bold mb-1">KRA PIN</label>
        <input
          v-model="form.kra_pin"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.kra_pin }"
          placeholder="e.g. P051XXXXXXX"
        />
        <div v-if="form.errors.kra_pin" class="invalid-feedback">
          {{ form.errors.kra_pin }}
        </div>
      </div>

      <div class="col-md-4 form-group mb-3">
        <label class="small font-weight-bold mb-1">Year of Registration</label>
        <input
          v-model="form.year_of_registration"
          type="number"
          min="1900"
          :max="new Date().getFullYear()"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.year_of_registration }"
          placeholder="e.g. 2015"
        />
        <div v-if="form.errors.year_of_registration" class="invalid-feedback">
          {{ form.errors.year_of_registration }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Business Type</label>
        <select
          v-model="form.business_type"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.business_type }"
        >
          <option value="">Select business type</option>
          <option v-for="bt in businessTypes" :key="bt" :value="bt">
            {{ bt }}
          </option>
        </select>
        <div v-if="form.errors.business_type" class="invalid-feedback">
          {{ form.errors.business_type }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Industry / Sector</label>
        <select
          v-model="form.industry_id"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.industry_id }"
        >
          <option value="">Select industry</option>
          <option v-for="i in industries" :key="i.id" :value="i.id">
            {{ i.name }}
          </option>
        </select>
        <div v-if="form.errors.industry_id" class="invalid-feedback">
          {{ form.errors.industry_id }}
        </div>
      </div>
    </div>

    <!-- ── Location & Reach ─────────────────────────────────────────── -->
    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 mt-3">
      Location & Reach
    </h6>
    <div class="row">
      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">County</label>
        <select
          v-model="form.county_id"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.county_id }"
        >
          <option value="">Select county</option>
          <option v-for="c in counties" :key="c.id" :value="c.id">
            {{ c.name }}
          </option>
        </select>
        <div v-if="form.errors.county_id" class="invalid-feedback">
          {{ form.errors.county_id }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Postal Address</label>
        <input
          v-model="form.postal_address"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.postal_address }"
          placeholder="e.g. P.O. Box 12345-00100 Nairobi"
        />
        <div v-if="form.errors.postal_address" class="invalid-feedback">
          {{ form.errors.postal_address }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Physical Address</label>
        <textarea
          v-model="form.physical_address"
          rows="2"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.physical_address }"
          placeholder="Street, building, floor, city"
        ></textarea>
        <div v-if="form.errors.physical_address" class="invalid-feedback">
          {{ form.errors.physical_address }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Website</label>
        <input
          v-model="form.website"
          type="url"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.website }"
          placeholder="https://example.com"
        />
        <div v-if="form.errors.website" class="invalid-feedback">
          {{ form.errors.website }}
        </div>
      </div>

      <div class="col-md-12 form-group mb-3">
        <label class="small font-weight-bold mb-1">Business Description</label>
        <textarea
          v-model="form.business_description"
          rows="3"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.business_description }"
          placeholder="Brief description of what your business does"
        ></textarea>
        <div v-if="form.errors.business_description" class="invalid-feedback">
          {{ form.errors.business_description }}
        </div>
      </div>
    </div>

    <!-- ── Capability & Experience ─────────────────────────────────── -->
    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 mt-3">
      Capability &amp; Experience
    </h6>
    <div class="row">
      <div class="col-md-4 form-group mb-3">
        <label class="small font-weight-bold mb-1">Years of Experience</label>
        <input
          v-model="form.years_of_experience"
          type="number"
          min="0"
          max="200"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.years_of_experience }"
          placeholder="e.g. 8"
        />
        <div v-if="form.errors.years_of_experience" class="invalid-feedback">
          {{ form.errors.years_of_experience }}
        </div>
      </div>

      <div class="col-md-4 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Annual Turnover (KES)
        </label>
        <input
          v-model="form.annual_turnover"
          type="number"
          min="0"
          step="0.01"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.annual_turnover }"
          placeholder="e.g. 12500000"
        />
        <div v-if="form.errors.annual_turnover" class="invalid-feedback">
          {{ form.errors.annual_turnover }}
        </div>
      </div>

      <div class="col-md-12 form-group mb-3">
        <label class="small font-weight-bold mb-1">Certifications</label>
        <textarea
          v-model="form.certifications"
          rows="3"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.certifications }"
          placeholder="One per line — e.g. ISO 9001:2015, KEBS Diamond Mark, NCA-3"
        ></textarea>
        <small class="text-muted d-block mt-1">
          Enter each certification / accreditation on its own line.
        </small>
        <div v-if="form.errors.certifications" class="invalid-feedback">
          {{ form.errors.certifications }}
        </div>
      </div>
    </div>

    <!-- ── Contact Person ───────────────────────────────────────────── -->
    <h6 class="text-uppercase text-muted small font-weight-bold mb-3 mt-3">
      Contact Person
    </h6>
    <div class="row">
      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Contact Person Name <span class="text-danger">*</span>
        </label>
        <input
          v-model="form.contact_person_name"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.contact_person_name }"
          placeholder="Full name"
        />
        <div v-if="form.errors.contact_person_name" class="invalid-feedback">
          {{ form.errors.contact_person_name }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Contact Person Position
        </label>
        <input
          v-model="form.contact_person_position"
          type="text"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.contact_person_position }"
          placeholder="e.g. Managing Director"
        />
        <div v-if="form.errors.contact_person_position" class="invalid-feedback">
          {{ form.errors.contact_person_position }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">Contact Phone</label>
        <input
          v-model="form.contact_phone"
          type="tel"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.contact_phone }"
          placeholder="e.g. 0712 345 678"
        />
        <div v-if="form.errors.contact_phone" class="invalid-feedback">
          {{ form.errors.contact_phone }}
        </div>
      </div>

      <div class="col-md-6 form-group mb-3">
        <label class="small font-weight-bold mb-1">
          Contact Email <span class="text-danger">*</span>
        </label>
        <input
          v-model="form.contact_email"
          type="email"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': form.errors.contact_email }"
          placeholder="contact@example.com"
        />
        <div v-if="form.errors.contact_email" class="invalid-feedback">
          {{ form.errors.contact_email }}
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center mt-2" style="gap: 1rem">
      <button
        type="submit"
        class="btn btn-success btn-sm px-4"
        :disabled="form.processing"
      >
        <i class="fas fa-save mr-1"></i>
        {{ form.processing ? "Saving…" : "Save Business Details" }}
      </button>
      <transition name="fade">
        <span v-if="form.recentlySuccessful" class="small text-success">
          <i class="fas fa-check mr-1"></i> Saved.
        </span>
      </transition>
    </div>
  </form>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
