<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const updatePassword = () => {
  form.put(route("password.update"), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset("password", "password_confirmation");
        passwordInput.value?.focus();
      }
      if (form.errors.current_password) {
        form.reset("current_password");
        currentPasswordInput.value?.focus();
      }
    },
  });
};
</script>

<template>
  <form @submit.prevent="updatePassword" style="max-width: 520px">
    <p class="text-muted small mb-4">
      Use a long, random password to keep your account secure.
    </p>

    <div class="form-group mb-3">
      <label for="current-password" class="small font-weight-bold mb-1">
        Current Password <span class="text-danger">*</span>
      </label>
      <input
        id="current-password"
        ref="currentPasswordInput"
        v-model="form.current_password"
        type="password"
        class="form-control form-control-sm"
        :class="{ 'is-invalid': form.errors.current_password }"
        placeholder="Enter current password"
        autocomplete="current-password"
      />
      <div v-if="form.errors.current_password" class="invalid-feedback">
        {{ form.errors.current_password }}
      </div>
    </div>

    <div class="form-group mb-3">
      <label for="new-password" class="small font-weight-bold mb-1">
        New Password <span class="text-danger">*</span>
      </label>
      <input
        id="new-password"
        ref="passwordInput"
        v-model="form.password"
        type="password"
        class="form-control form-control-sm"
        :class="{ 'is-invalid': form.errors.password }"
        placeholder="Min. 8 characters"
        autocomplete="new-password"
      />
      <div v-if="form.errors.password" class="invalid-feedback">
        {{ form.errors.password }}
      </div>
    </div>

    <div class="form-group mb-4">
      <label for="confirm-password" class="small font-weight-bold mb-1">
        Confirm New Password <span class="text-danger">*</span>
      </label>
      <input
        id="confirm-password"
        v-model="form.password_confirmation"
        type="password"
        class="form-control form-control-sm"
        :class="{ 'is-invalid': form.errors.password_confirmation }"
        placeholder="Repeat new password"
        autocomplete="new-password"
      />
      <div v-if="form.errors.password_confirmation" class="invalid-feedback">
        {{ form.errors.password_confirmation }}
      </div>
    </div>

    <div class="d-flex align-items-center" style="gap: 1rem">
      <button
        type="submit"
        class="btn btn-success btn-sm px-4"
        :disabled="form.processing"
      >
        <i class="fas fa-key mr-1"></i>
        {{ form.processing ? "Updating…" : "Update Password" }}
      </button>
      <transition name="fade">
        <span v-if="form.recentlySuccessful" class="small text-success">
          <i class="fas fa-check mr-1"></i> Password updated.
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
