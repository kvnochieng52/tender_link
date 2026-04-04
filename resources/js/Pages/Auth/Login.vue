<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  canResetPassword: { type: Boolean },
  status: { type: String },
  redirectTo: { type: String, default: "" },
});

const googleUrl = computed(() => {
  const base = route("auth.google");
  return props.redirectTo
    ? `${base}?redirect=${encodeURIComponent(props.redirectTo)}`
    : base;
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
          alt="Tender Plug"
          class="auth-logo"
        />
      </div>

      <div class="card auth-card border-0">
        <div class="card-body p-4 p-md-5">
          <h1 class="h4 font-weight-bold mb-1 text-center text-dark">
            Welcome Back
          </h1>
          <p class="text-muted text-center mb-4">
            Login to continue to Tender Plug
          </p>

          <div v-if="status" class="alert alert-success mb-3">
            {{ status }}
          </div>

          <!-- Google — primary option -->
          <a :href="googleUrl" class="btn btn-white btn-block google-btn mb-3">
            <img
              src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
              width="18"
              height="18"
              class="mr-2"
              alt=""
            />
            Continue with Google
          </a>

          <div class="divider-text text-center text-muted small mb-3">
            <span>or sign in with email</span>
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
  border: 1px solid #d8d8d8;
  background: #fff;
  color: #444;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
}

.google-btn:hover {
  background: #f8f9fa;
  border-color: #bbb;
  color: #222;
}

.divider-text {
  position: relative;
}
.divider-text::before,
.divider-text::after {
  content: "";
  position: absolute;
  top: 50%;
  width: 38%;
  height: 1px;
  background: #dee2e6;
}
.divider-text::before {
  left: 0;
}
.divider-text::after {
  right: 0;
}

.auth-actions {
  justify-content: center;
}
</style>
