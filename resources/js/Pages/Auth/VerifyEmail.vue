<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  status: {
    type: String,
  },
});

const form = useForm({});

const submit = () => {
  form.post(route("verification.send"));
};

const verificationLinkSent = computed(
  () => props.status === "verification-link-sent"
);
</script>

<template>
  <Head title="Email Verification" />

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
          <h1 class="h4 font-weight-bold mb-2 text-center text-dark">
            Verify Your Email
          </h1>
          <p class="text-muted text-center mb-4">
            Confirm your email address to activate your Tender Plug account.
          </p>

          <div class="alert alert-light border alert-info-text mb-3">
            <i class="fas fa-info-circle mr-2"></i>
            We sent a verification link to your email. Click it to continue. If
            you didn't receive it, request another below.
          </div>

          <div v-if="verificationLinkSent" class="alert alert-success mb-3">
            <i class="fas fa-check-circle mr-2"></i>
            A new verification link has been sent to your email address.
          </div>

          <form @submit.prevent="submit">
            <button
              type="submit"
              class="btn btn-success btn-block"
              :disabled="form.processing"
            >
              <span v-if="form.processing">
                <i class="fas fa-spinner fa-spin mr-1"></i>Sending...
              </span>
              <span v-else>
                <i class="fas fa-envelope mr-1"></i>Resend Verification Email
              </span>
            </button>
          </form>

          <div class="d-flex flex-wrap mt-4 auth-actions">
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="btn btn-outline-secondary btn-sm mr-2 mb-2"
            >
              Log Out
            </Link>
            <Link
              :href="route('welcome')"
              class="btn btn-light btn-sm border mb-2"
            >
              Back Home
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
  max-width: 500px;
}

.auth-logo {
  height: 48px;
  width: auto;
}

.auth-card {
  border-radius: 0.9rem;
  box-shadow: 0 12px 35px rgba(9, 23, 111, 0.15);
}

.alert-info-text {
  color: var(--brand-primary-dark);
  background: rgba(240, 250, 244, 0.95);
  border-color: rgba(9, 23, 111, 0.25) !important;
}

.auth-actions {
  justify-content: center;
}
</style>
