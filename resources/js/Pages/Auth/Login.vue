<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <Head title="Login" />

  <div class="auth-page">
    <div class="auth-card-wrap">
      <div class="text-center mb-4">
        <img
          src="/images/tender-link-logo.svg"
          alt="Tender Link"
          class="auth-logo"
        />
      </div>

      <div class="card auth-card border-0">
        <div class="card-body p-4 p-md-5">
          <h1 class="h4 font-weight-bold mb-1 text-center text-dark">
            Welcome Back
          </h1>
          <p class="text-muted text-center mb-4">
            Login to continue to Tender Link
          </p>

          <div v-if="status" class="alert alert-success mb-3">
            {{ status }}
          </div>

          <form @submit.prevent="submit">
            <div class="form-group mb-3">
              <label for="email" class="small font-weight-semibold"
                >Email</label
              >
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="form-control"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
                :class="{ 'is-invalid': form.errors.email }"
              />
              <div v-if="form.errors.email" class="invalid-feedback d-block">
                {{ form.errors.email }}
              </div>
            </div>

            <div class="form-group mb-3">
              <label for="password" class="small font-weight-semibold"
                >Password</label
              >
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="form-control"
                required
                autocomplete="current-password"
                placeholder="Enter password"
                :class="{ 'is-invalid': form.errors.password }"
              />
              <div v-if="form.errors.password" class="invalid-feedback d-block">
                {{ form.errors.password }}
              </div>
            </div>

            <div
              class="d-flex justify-content-between align-items-center mb-3 flex-wrap"
            >
              <div class="custom-control custom-checkbox mb-2 mb-sm-0">
                <input
                  id="remember"
                  v-model="form.remember"
                  type="checkbox"
                  class="custom-control-input"
                />
                <label for="remember" class="custom-control-label"
                  >Remember me</label
                >
              </div>

              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="small auth-link"
              >
                Forgot password?
              </Link>
            </div>

            <button
              type="submit"
              class="btn btn-success btn-block"
              :disabled="form.processing"
            >
              <span v-if="form.processing"
                ><i class="fas fa-spinner fa-spin mr-1"></i>Signing in...</span
              >
              <span v-else>Login</span>
            </button>

            <button
              type="button"
              class="btn btn-outline-secondary btn-block mt-2 google-btn"
            >
              <i class="fab fa-google text-danger mr-2"></i>Login with Gmail
            </button>
          </form>

          <div class="d-flex flex-wrap mt-4 auth-actions">
            <Link
              :href="route('register')"
              class="btn btn-outline-success btn-sm mr-2 mb-2"
              >Register</Link
            >
            <Link
              :href="route('welcome')"
              class="btn btn-light btn-sm border mb-2"
              >Back Home</Link
            >
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
  background: linear-gradient(160deg, #f0faf4 0%, #ffffff 65%);
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
  box-shadow: 0 12px 35px rgba(40, 167, 69, 0.15);
}

.form-control {
  border-radius: 0.55rem;
  border-color: #d5e5da;
}

.form-control:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
}

.auth-link {
  color: #1f8f53;
}

.auth-link:hover {
  color: #28a745;
  text-decoration: underline;
}

.google-btn {
  border-color: #d8d8d8;
}

.auth-actions {
  justify-content: center;
}
</style>
