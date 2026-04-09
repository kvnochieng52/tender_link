<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  email: {
    type: String,
    required: true,
  },
  token: {
    type: String,
    required: true,
  },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(route("password.store"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
};
</script>

<template>
  <Head title="Reset Password" />

  <div class="auth-page">
    <div class="auth-card-wrap">
      <div class="text-center mb-4">
        <img
          src="/images/tender-link-logo.svg"
          alt="Tender Plug"
          class="auth-logo"
        />
      </div>

      <div class="card auth-card border-0">
        <div class="card-body p-4 p-md-5">
          <h1 class="h4 font-weight-bold mb-1 text-center text-dark">
            Create New Password
          </h1>
          <p class="text-muted text-center mb-4">
            Please create a new strong password for your account.
          </p>

          <form @submit.prevent="submit">
            <div class="form-group mb-3">
              <label for="email" class="small font-weight-semibold">Email</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-control"
                readonly
                autocomplete="username"
                :class="{ 'is-invalid': form.errors.email }"
              />
              <div v-if="form.errors.email" class="invalid-feedback d-block">
                {{ form.errors.email }}
              </div>
            </div>

            <div class="form-group mb-3">
              <label for="password" class="small font-weight-semibold">New Password</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="form-control"
                required
                autofocus
                autocomplete="new-password"
                placeholder="Enter new password"
                :class="{ 'is-invalid': form.errors.password }"
              />
              <div v-if="form.errors.password" class="invalid-feedback d-block">
                {{ form.errors.password }}
              </div>
            </div>

            <div class="form-group mb-3">
              <label for="password_confirmation" class="small font-weight-semibold">Confirm New Password</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="form-control"
                required
                autocomplete="new-password"
                placeholder="Confirm new password"
                :class="{ 'is-invalid': form.errors.password_confirmation }"
              />
              <div v-if="form.errors.password_confirmation" class="invalid-feedback d-block">
                {{ form.errors.password_confirmation }}
              </div>
            </div>

            <button
              type="submit"
              class="btn btn-success btn-block"
              :disabled="form.processing"
            >
              <span v-if="form.processing">
                <i class="fas fa-spinner fa-spin mr-1"></i> Resetting...
              </span>
              <span v-else>Reset Password</span>
            </button>
          </form>

          <div class="text-center mt-4">
            <Link :href="route('login')" class="small auth-link">
              <i class="fas fa-arrow-left mr-1"></i> Back to login
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--brand-surface-blue);
  padding: 1.25rem;
}

.auth-card-wrap {
  width: 100%;
  max-width: 430px;
}

.auth-logo {
  height: 48px;
  width: auto;
}

.auth-card {
  border-radius: 0.9rem;
  box-shadow: 0 12px 35px rgba(9, 23, 111, 0.15);
}

.form-control {
  border-radius: 0.55rem;
  border-color: #d5e5da;
}

.form-control:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 0.2rem rgba(9, 23, 111, 0.15);
}

.auth-link {
  color: var(--brand-secondary-dark);
}

.auth-link:hover {
  color: var(--brand-primary);
  text-decoration: underline;
}
</style>
