<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
  canLogin: { type: Boolean },
  canRegister: { type: Boolean },
  flash: { type: Object, default: () => ({}) },
});

const mobileMenuOpen = ref(false);
const tenderSubmenuOpen = ref(false);

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  if (!mobileMenuOpen.value) tenderSubmenuOpen.value = false;
};
const toggleTenderSubmenu = (e) => {
  e.preventDefault();
  tenderSubmenuOpen.value = !tenderSubmenuOpen.value;
};
const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
  tenderSubmenuOpen.value = false;
};

const currentYear = new Date().getFullYear();

const form = useForm({
  name: "",
  email: "",
  subject: "",
  message: "",
});

const submitted = ref(false);

const send = () => {
  form.post(route("contact.send"), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      submitted.value = true;
    },
  });
};

const contactDetails = [
  {
    icon: "fas fa-map-marker-alt",
    label: "Address",
    value: "Madonna House Annex, Westlands, Nairobi",
  },
  { icon: "fas fa-phone-alt", label: "Phone", value: "0108517504" },
  { icon: "fas fa-envelope", label: "Email", value: "info@tenderplug.com" },
  {
    icon: "fas fa-clock",
    label: "Hours",
    value: "Mon – Fri: 8:00 AM – 6:00 PM EAT",
  },
];

const faqs = [
  {
    q: "How do I register as a supplier?",
    a: "Click the 'Register Free' button in the navigation bar, complete the form, verify your email, and choose a subscription plan.",
  },
  {
    q: "Can I post tenders as an institution?",
    a: "Yes. Contact our team through this form and we'll set up your institution account with admin access to create and manage tenders.",
  },
  {
    q: "What payment methods are supported?",
    a: "We accept M-Pesa, Visa, and Mastercard for subscription payments.",
  },
  {
    q: "How quickly are tenders updated on the portal?",
    a: "New tenders are published within 24 hours of submission and are immediately searchable.",
  },
  {
    q: "Is my submitted data secure?",
    a: "All documents are encrypted at rest and in transit. We follow OWASP security practices and conduct regular security audits.",
  },
];

const openFaq = ref(null);
const toggleFaq = (i) => {
  openFaq.value = openFaq.value === i ? null : i;
};
</script>

<template>
  <Head title="Contact Us – Tender Plug" />

  <div class="landing-page bg-light">
    <!-- ── Navbar ──────────────────────────────────────────── -->
    <div
      class="container d-flex align-items-center justify-content-between flex-wrap"
    >
      <Link :href="route('welcome')" class="navbar-brand mr-0 py-2">
        <img
          src="/images/tender-link-logo.svg"
          alt="Tender Plug"
          class="brand-logo-full"
        />
      </Link>

      <button
        class="navbar-toggler mobile-nav-toggler"
        type="button"
        aria-label="Toggle navigation"
        :aria-expanded="mobileMenuOpen"
        @click="toggleMobileMenu"
      >
        <i class="fas fa-bars"></i>
      </button>

      <div :class="['nav-mobile-collapse', mobileMenuOpen ? 'is-open' : '']">
        <ul
          class="navbar-nav nav-main-menu flex-row flex-wrap justify-content-center my-2 my-lg-0 mx-lg-auto"
        >
          <li class="nav-item">
            <Link
              :href="route('welcome')"
              class="nav-link"
              @click="closeMobileMenu"
              >Home</Link
            >
          </li>
          <li class="nav-item">
            <Link
              :href="route('about')"
              class="nav-link"
              @click="closeMobileMenu"
              >About Us</Link
            >
          </li>
          <li class="nav-item">
            <Link
              :href="route('services')"
              class="nav-link"
              @click="closeMobileMenu"
              >Services</Link
            >
          </li>
          <li
            :class="[
              'nav-item',
              'nav-item-dropdown',
              tenderSubmenuOpen ? 'is-open' : '',
            ]"
          >
            <a class="nav-link" href="#" @click="toggleTenderSubmenu">
              Browse Tenders <i class="fas fa-angle-down ml-1"></i>
            </a>
            <div class="dropdown-menu-custom">
              <Link
                :href="route('tenders.search', { industry: 'construction' })"
                class="dropdown-item"
                @click="closeMobileMenu"
                >Construction</Link
              >
              <Link
                :href="route('tenders.search', { industry: 'supply' })"
                class="dropdown-item"
                @click="closeMobileMenu"
                >Supply</Link
              >
              <Link
                :href="route('tenders.search', { industry: 'ict' })"
                class="dropdown-item"
                @click="closeMobileMenu"
                >ICT</Link
              >
              <Link
                :href="route('tenders.search', { industry: 'agro' })"
                class="dropdown-item"
                @click="closeMobileMenu"
                >Agro</Link
              >
              <Link
                :href="route('tenders.search')"
                class="dropdown-item"
                @click="closeMobileMenu"
                >Government</Link
              >
              <Link
                :href="route('tenders.search')"
                class="dropdown-item"
                @click="closeMobileMenu"
                >NGOs</Link
              >
            </div>
          </li>
          <li class="nav-item">
            <Link
              :href="route('contact')"
              class="nav-link active"
              @click="closeMobileMenu"
              >Contact Us</Link
            >
          </li>
        </ul>

        <div
          class="auth-actions d-flex align-items-center flex-wrap justify-content-end py-2"
        >
          <template v-if="canLogin">
            <Link
              v-if="$page.props.auth.user"
              :href="route('dashboard')"
              class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
              @click="closeMobileMenu"
            >
              Dashboard
            </Link>
            <template v-else>
              <Link
                :href="route('login')"
                class="btn btn-outline-success btn-sm ml-2 mb-1 mb-md-0"
                @click="closeMobileMenu"
                >Login/Register</Link
              >
              <Link
                :href="route('register')"
                class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"
                @click="closeMobileMenu"
                >Apply Tender</Link
              >
            </template>
          </template>
        </div>
      </div>
    </div>
    <!-- ── /Navbar ─────────────────────────────────────────── -->

    <main class="pb-5">
      <!-- Hero banner -->
      <section class="contact-hero">
        <div class="contact-hero-overlay">
          <div class="container text-center">
            <h1 class="contact-hero-title">Get in Touch</h1>
            <p class="contact-hero-sub">
              We'd love to hear from you. Reach out with questions, partnership
              inquiries, or feedback.
            </p>
          </div>
        </div>
      </section>

      <!-- Contact details + form -->
      <section class="py-5">
        <div class="container">
          <div class="row">
            <!-- Left: details -->
            <div class="col-lg-4 mb-4 mb-lg-0">
              <div class="contact-info-card h-100">
                <h4 class="font-weight-bold mb-4" style="color: #1a3a22">
                  Contact Information
                </h4>
                <div
                  v-for="detail in contactDetails"
                  :key="detail.label"
                  class="contact-detail-row mb-3"
                >
                  <div class="contact-detail-icon">
                    <i :class="detail.icon"></i>
                  </div>
                  <div>
                    <p class="contact-detail-label mb-0">{{ detail.label }}</p>
                    <p class="contact-detail-value mb-0">{{ detail.value }}</p>
                  </div>
                </div>

                <hr class="my-4 border-success-light" />

                <p class="small font-weight-bold mb-2" style="color: #1a3a22">
                  Follow us
                </p>
                <div class="d-flex" style="gap: 0.65rem">
                  <a href="#" class="social-btn" aria-label="Twitter"
                    ><i class="fab fa-twitter"></i
                  ></a>
                  <a href="#" class="social-btn" aria-label="LinkedIn"
                    ><i class="fab fa-linkedin-in"></i
                  ></a>
                  <a href="#" class="social-btn" aria-label="Facebook"
                    ><i class="fab fa-facebook-f"></i
                  ></a>
                  <a href="#" class="social-btn" aria-label="WhatsApp"
                    ><i class="fab fa-whatsapp"></i
                  ></a>
                </div>
              </div>
            </div>

            <!-- Right: form -->
            <div class="col-lg-8">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                  <h5 class="mb-0 font-weight-bold" style="color: #1a3a22">
                    <i class="fas fa-paper-plane text-success mr-2"></i>Send Us
                    a Message
                  </h5>
                </div>
                <div class="card-body p-4">
                  <!-- Success message -->
                  <transition name="fade">
                    <div
                      v-if="submitted"
                      class="alert alert-success d-flex align-items-center mb-4"
                    >
                      <i class="fas fa-check-circle mr-2 fa-lg"></i>
                      <div>
                        <strong>Message sent!</strong>
                        Thank you for reaching out. We'll get back to you within
                        24 hours.
                      </div>
                    </div>
                  </transition>

                  <form @submit.prevent="send">
                    <div class="form-row">
                      <div class="form-group col-md-6 mb-3">
                        <label class="small font-weight-bold mb-1"
                          >Full Name <span class="text-danger">*</span></label
                        >
                        <input
                          v-model="form.name"
                          type="text"
                          class="form-control form-control-sm"
                          :class="{ 'is-invalid': form.errors.name }"
                          placeholder="e.g. Jane Mwenda"
                          required
                        />
                        <div v-if="form.errors.name" class="invalid-feedback">
                          {{ form.errors.name }}
                        </div>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="small font-weight-bold mb-1"
                          >Email Address
                          <span class="text-danger">*</span></label
                        >
                        <input
                          v-model="form.email"
                          type="email"
                          class="form-control form-control-sm"
                          :class="{ 'is-invalid': form.errors.email }"
                          placeholder="jane@example.com"
                          required
                        />
                        <div v-if="form.errors.email" class="invalid-feedback">
                          {{ form.errors.email }}
                        </div>
                      </div>
                    </div>

                    <div class="form-group mb-3">
                      <label class="small font-weight-bold mb-1"
                        >Subject <span class="text-danger">*</span></label
                      >
                      <select
                        v-model="form.subject"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors.subject }"
                        required
                      >
                        <option value="">— Select a subject —</option>
                        <option value="General Enquiry">General Enquiry</option>
                        <option value="Supplier Registration">
                          Supplier Registration
                        </option>
                        <option value="Institution/Posting Account">
                          Institution / Posting Account
                        </option>
                        <option value="Technical Support">
                          Technical Support
                        </option>
                        <option value="Billing & Payments">
                          Billing &amp; Payments
                        </option>
                        <option value="Partnership Opportunity">
                          Partnership Opportunity
                        </option>
                        <option value="Other">Other</option>
                      </select>
                      <div v-if="form.errors.subject" class="invalid-feedback">
                        {{ form.errors.subject }}
                      </div>
                    </div>

                    <div class="form-group mb-4">
                      <label class="small font-weight-bold mb-1"
                        >Message <span class="text-danger">*</span></label
                      >
                      <textarea
                        v-model="form.message"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors.message }"
                        rows="5"
                        placeholder="Write your message here…"
                        required
                      ></textarea>
                      <div v-if="form.errors.message" class="invalid-feedback">
                        {{ form.errors.message }}
                      </div>
                    </div>

                    <button
                      type="submit"
                      class="btn btn-success btn-sm px-5"
                      :disabled="form.processing"
                    >
                      <i class="fas fa-paper-plane mr-1"></i>
                      {{ form.processing ? "Sending…" : "Send Message" }}
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Map placeholder -->
      <section class="pb-5">
        <div class="container">
          <div
            class="map-placeholder d-flex align-items-center justify-content-center"
          >
              <div class="text-center text-muted">
              <i
                class="fas fa-map-marker-alt fa-3x mb-3"
                style="color: #28a745"
              ></i>
              <p class="mb-0 font-weight-bold">Madonna House Annex, Westlands, Nairobi</p>
              <small>Map embed coming soon</small>
            </div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="section-header text-center mb-5">
            <span class="badge badge-success-soft mb-2"
              ><i class="fas fa-question-circle mr-1"></i>FAQ</span
            >
            <h2 class="font-weight-bold" style="color: #1a3a22">
              Frequently Asked Questions
            </h2>
            <p class="text-muted mx-auto" style="max-width: 480px">
              Can't find what you're looking for? Send us a message above.
            </p>
          </div>
          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div v-for="(faq, i) in faqs" :key="i" class="faq-item mb-2">
                <button
                  class="faq-question w-100 text-left"
                  type="button"
                  @click="toggleFaq(i)"
                >
                  <span>{{ faq.q }}</span>
                  <i
                    :class="[
                      'fas',
                      openFaq === i ? 'fa-chevron-up' : 'fa-chevron-down',
                    ]"
                  ></i>
                </button>
                <transition name="faq-slide">
                  <div v-if="openFaq === i" class="faq-answer">
                    {{ faq.a }}
                  </div>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ── Footer ──────────────────────────────────────────── -->
    <footer class="landing-footer border-top">
      <div class="container footer-content-wrap">
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-md-center"
        >
          <img
            src="/images/tender-link-logo.svg"
            alt="Tender Plug"
            class="footer-logo mb-3 mb-md-0"
          />
          <div class="footer-links d-flex align-items-center flex-wrap">
            <a href="#" class="footer-link mr-3">Terms and Conditions</a>
            <Link :href="route('contact')" class="footer-link">Contact Us</Link>
          </div>
        </div>
        <div class="footer-bottom text-center text-md-left mt-3 pt-3">
          <small class="text-muted"
            >© {{ currentYear }} Tender Plug. All rights reserved.</small
          >
        </div>
      </div>
    </footer>
    <!-- ── /Footer ─────────────────────────────────────────── -->
  </div>
</template>

<style scoped>
.landing-page {
  overflow-x: hidden;
}

.brand-logo-full {
  height: 44px;
  width: auto;
  display: block;
}
.footer-logo {
  height: 34px;
  width: auto;
  display: block;
}
.landing-footer {
  background: #ffffff;
  border-color: #dfe5e1 !important;
  padding: 1.15rem 0 1rem;
}
.footer-links {
  gap: 0.35rem;
}
.footer-link {
  color: #1f8f53;
  font-size: 0.94rem;
  font-weight: 600;
  text-decoration: none;
}
.footer-link:hover {
  color: #28a745;
  text-decoration: underline;
}
.footer-bottom {
  border-top: 1px solid #ebf1ed;
}

/* ── Hero ─────── */
.contact-hero {
  background: url("https://images.unsplash.com/photo-1423666639041-f56000c27a9a?auto=format&fit=crop&w=1400&q=80")
    center/cover no-repeat;
  min-height: 320px;
  display: flex;
  align-items: center;
}
.contact-hero-overlay {
  width: 100%;
  background: rgba(10, 40, 20, 0.62);
  min-height: 320px;
  display: flex;
  align-items: center;
  padding: 3rem 0;
}
.contact-hero-title {
  font-size: 2.6rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: 0.5rem;
}
.contact-hero-sub {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.82);
  max-width: 520px;
  margin: 0 auto;
}

/* ── Contact info card ── */
.contact-info-card {
  background: #1a3a22;
  border-radius: 0.75rem;
  padding: 2rem 1.75rem;
  color: #fff;
}
.contact-detail-row {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}
.contact-detail-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  color: #7fd99a;
  flex-shrink: 0;
}
.contact-detail-label {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.contact-detail-value {
  font-size: 0.9rem;
  color: #fff;
  font-weight: 500;
}
.border-success-light {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.social-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.85rem;
  text-decoration: none;
  transition: background 0.2s;
}
.social-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
}

/* ── Map ─────────── */
.map-placeholder {
  border-radius: 0.75rem;
  background: #e9f5ed;
  border: 2px dashed #b2dfbb;
  min-height: 200px;
  padding: 2.5rem;
}

/* ── FAQ ─────────── */
.faq-item {
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  overflow: hidden;
}
.faq-question {
  background: #fff;
  border: none;
  padding: 1rem 1.25rem;
  font-weight: 600;
  font-size: 0.92rem;
  color: #1a3a22;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: background 0.15s;
}
.faq-question:hover {
  background: #f8fdf9;
}
.faq-answer {
  background: #f8fdf9;
  padding: 0.85rem 1.25rem;
  font-size: 0.88rem;
  color: #495057;
  border-top: 1px solid #dee2e6;
}

.faq-slide-enter-active,
.faq-slide-leave-active {
  transition: opacity 0.2s, max-height 0.25s;
  max-height: 300px;
  overflow: hidden;
}
.faq-slide-enter-from,
.faq-slide-leave-to {
  opacity: 0;
  max-height: 0;
}

/* ── Fade ─────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Badge soft ───── */
.badge-success-soft {
  background: rgba(40, 167, 69, 0.12);
  color: #1a6130;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35em 0.75em;
  border-radius: 99px;
}

@media (max-width: 767px) {
  .contact-hero-title {
    font-size: 1.8rem;
  }
  .contact-info-card {
    margin-bottom: 1.5rem;
  }
}
</style>
