<script setup>
import { useForm, usePage } from "@inertiajs/vue3";

const user = usePage().props.auth.user;

const form = useForm({
  name: user.name ?? "",
  telephone: user.telephone ?? "",
});

const submit = () => {
  form.patch(route("profile.update"));
};
</script>

<template>
  <form @submit.prevent="submit" style="max-width: 520px">
    <p class="text-muted small mb-4">
      Update your display name and phone number. Your email address cannot be
      changed here.
    </p>

    <!-- Email (read-only display) -->
    <div class="form-group mb-3">
      <label class="small font-weight-bold mb-1">Email Address</label>
      <input
        type="email"
        class="form-control form-control-sm bg-light"
        :value="user.email"
        readonly
        disabled
      />
      <small class="form-text text-muted">Email cannot be changed.</small>
    </div>

    <!-- Name -->
    <div class="form-group mb-3">
      <label for="profile-name" class="small font-weight-bold mb-1">
        Full Name <span class="text-danger">*</span>
      </label>
      <input
        id="profile-name"
        v-model="form.name"
        type="text"
        class="form-control form-control-sm"
        :class="{ 'is-invalid': form.errors.name }"
        placeholder="Your full name"
        required
        autofocus
        autocomplete="name"
      />
      <div v-if="form.errors.name" class="invalid-feedback">
        {{ form.errors.name }}
      </div>
    </div>

    <!-- Telephone -->
    <div class="form-group mb-4">
      <label for="profile-telephone" class="small font-weight-bold mb-1"
        >Phone Number</label
      >
      <input
        id="profile-telephone"
        v-model="form.telephone"
        type="tel"
        class="form-control form-control-sm"
        :class="{ 'is-invalid': form.errors.telephone }"
        placeholder="e.g. 0712 345 678"
        autocomplete="tel"
      />
      <div v-if="form.errors.telephone" class="invalid-feedback">
        {{ form.errors.telephone }}
      </div>
    </div>

    <div class="d-flex align-items-center" style="gap: 1rem">
      <button
        type="submit"
        class="btn btn-success btn-sm px-4"
        :disabled="form.processing"
      >
        <i class="fas fa-save mr-1"></i>
        {{ form.processing ? "Saving…" : "Save Changes" }}
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
