<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    canLogin:    { type: Boolean },
    canRegister: { type: Boolean },
});

const mobileMenuOpen   = ref(false);
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
    mobileMenuOpen.value   = false;
    tenderSubmenuOpen.value = false;
};

const currentYear = new Date().getFullYear();

const team = [
    {
        name:  "James Mwangi",
        role:  "Chief Executive Officer",
        bio:   "10+ years leading procurement and public-sector technology initiatives across East Africa.",
        avatar: "https://ui-avatars.com/api/?name=James+Mwangi&background=28a745&color=fff&size=128",
    },
    {
        name:  "Winnie Achieng",
        role:  "Head of Operations",
        bio:   "Expert in tender compliance, supplier onboarding, and end-to-end procurement workflow management.",
        avatar: "https://ui-avatars.com/api/?name=Winnie+Achieng&background=28a745&color=fff&size=128",
    },
    {
        name:  "Brian Kiprotich",
        role:  "Lead Engineer",
        bio:   "Full-stack developer focused on building secure, scalable portals for government and NGO procurement.",
        avatar: "https://ui-avatars.com/api/?name=Brian+Kiprotich&background=28a745&color=fff&size=128",
    },
    {
        name:  "Amina Hassan",
        role:  "Client Relations",
        bio:   "Dedicated to ensuring every supplier and institution gets timely, professional support on the platform.",
        avatar: "https://ui-avatars.com/api/?name=Amina+Hassan&background=28a745&color=fff&size=128",
    },
];

const stats = [
    { value: "5,000+", label: "Registered Suppliers" },
    { value: "1,200+", label: "Tenders Published" },
    { value: "47",     label: "Counties Covered" },
    { value: "98%",    label: "Client Satisfaction" },
];

const values = [
    { icon: "fas fa-handshake",       title: "Integrity",      text: "We uphold the highest ethical standards in every tender process." },
    { icon: "fas fa-shield-alt",      title: "Transparency",   text: "Every step is auditable, open, and traceable for all stakeholders." },
    { icon: "fas fa-bolt",            title: "Efficiency",     text: "Technology-driven workflows cut procurement timelines dramatically." },
    { icon: "fas fa-users",           title: "Inclusivity",    text: "We empower MSMEs, youth, and women businesses to compete fairly." },
    { icon: "fas fa-chart-line",      title: "Innovation",     text: "Continuous improvement backed by data and stakeholder feedback." },
    { icon: "fas fa-leaf",            title: "Sustainability", text: "We promote green procurement and responsible supplier practices." },
];
</script>

<template>
    <Head title="About Us – Tender Link" />

    <div class="landing-page bg-light">

        <!-- ── Navbar ──────────────────────────────────────────── -->
        <div class="container d-flex align-items-center justify-content-between flex-wrap">
            <Link :href="route('welcome')" class="navbar-brand mr-0 py-2">
                <img src="/images/tender-link-logo.svg" alt="Tender Link" class="brand-logo-full" />
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
                <ul class="navbar-nav nav-main-menu flex-row flex-wrap justify-content-center my-2 my-lg-0 mx-lg-auto">
                    <li class="nav-item">
                        <Link :href="route('welcome')" class="nav-link" @click="closeMobileMenu">Home</Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('about')" class="nav-link active" @click="closeMobileMenu">About Us</Link>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('services')" class="nav-link" @click="closeMobileMenu">Services</Link>
                    </li>
                    <li :class="['nav-item','nav-item-dropdown', tenderSubmenuOpen ? 'is-open' : '']">
                        <a class="nav-link" href="#" @click="toggleTenderSubmenu">
                            Browse Tenders <i class="fas fa-angle-down ml-1"></i>
                        </a>
                        <div class="dropdown-menu-custom">
                            <Link :href="route('tenders.search', { industry: 'construction' })" class="dropdown-item" @click="closeMobileMenu">Construction</Link>
                            <Link :href="route('tenders.search', { industry: 'supply' })"       class="dropdown-item" @click="closeMobileMenu">Supply</Link>
                            <Link :href="route('tenders.search', { industry: 'ict' })"           class="dropdown-item" @click="closeMobileMenu">ICT</Link>
                            <Link :href="route('tenders.search', { industry: 'agro' })"          class="dropdown-item" @click="closeMobileMenu">Agro</Link>
                            <Link :href="route('tenders.search')"                                class="dropdown-item" @click="closeMobileMenu">Government</Link>
                            <Link :href="route('tenders.search')"                                class="dropdown-item" @click="closeMobileMenu">NGOs</Link>
                        </div>
                    </li>
                    <li class="nav-item">
                        <Link :href="route('contact')" class="nav-link" @click="closeMobileMenu">Contact Us</Link>
                    </li>
                </ul>

                <div class="auth-actions d-flex align-items-center flex-wrap justify-content-end py-2">
                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-success btn-sm ml-2 mb-1 mb-md-0" @click="closeMobileMenu">
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')"    class="btn btn-outline-success btn-sm ml-2 mb-1 mb-md-0" @click="closeMobileMenu">Login/Register</Link>
                            <Link :href="route('register')" class="btn btn-success btn-sm ml-2 mb-1 mb-md-0"         @click="closeMobileMenu">Apply Tender</Link>
                        </template>
                    </template>
                </div>
            </div>
        </div>
        <!-- ── /Navbar ─────────────────────────────────────────── -->

        <main class="pb-5">

            <!-- Hero banner -->
            <section class="about-hero">
                <div class="about-hero-overlay">
                    <div class="container text-center">
                        <h1 class="about-hero-title">About Tender Link</h1>
                        <p class="about-hero-sub">
                            Kenya's most trusted procurement portal — connecting institutions with verified suppliers.
                        </p>
                        <Link :href="route('tenders.search')" class="btn btn-success btn-lg px-5 mt-2">
                            <i class="fas fa-search mr-2"></i>Browse Tenders
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Who we are -->
            <section class="py-5">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <img
                                src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80"
                                alt="Our team"
                                class="img-fluid rounded shadow-sm"
                            />
                        </div>
                        <div class="col-lg-6">
                            <span class="badge badge-success-soft mb-2"><i class="fas fa-info-circle mr-1"></i>Who We Are</span>
                            <h2 class="font-weight-bold mb-3" style="color:#1a3a22">Empowering Kenya's Procurement Ecosystem</h2>
                            <p class="text-muted">
                                Tender Link is a technology-driven procurement portal that bridges the gap between
                                government entities, NGOs, private institutions, and qualified suppliers across Kenya.
                                We simplify tender discovery, submission, and evaluation — for everyone from large
                                corporations to small community enterprises.
                            </p>
                            <p class="text-muted">
                                Founded with a mission to cut corruption, reduce delays, and democratize access to
                                public procurement, our platform currently serves institutions and suppliers in all
                                47 counties.
                            </p>
                            <div class="d-flex flex-wrap mt-3" style="gap:.75rem">
                                <Link :href="route('services')" class="btn btn-success btn-sm px-4">Our Services</Link>
                                <Link :href="route('contact')"  class="btn btn-outline-success btn-sm px-4">Contact Us</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats bar -->
            <section class="stats-bar py-4">
                <div class="container">
                    <div class="row text-center">
                        <div v-for="stat in stats" :key="stat.label" class="col-6 col-md-3 mb-3 mb-md-0">
                            <div class="stat-item">
                                <span class="stat-value">{{ stat.value }}</span>
                                <span class="stat-label d-block">{{ stat.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Our values -->
            <section class="py-5">
                <div class="container">
                    <div class="section-header text-center mb-5">
                        <span class="badge badge-success-soft mb-2"><i class="fas fa-star mr-1"></i>Our Values</span>
                        <h2 class="font-weight-bold" style="color:#1a3a22">What Guides Us</h2>
                        <p class="text-muted mx-auto" style="max-width:520px">
                            Every feature we build and every decision we make is rooted in these core principles.
                        </p>
                    </div>
                    <div class="row">
                        <div v-for="val in values" :key="val.title" class="col-sm-6 col-lg-4 mb-4">
                            <div class="value-card h-100">
                                <div class="value-icon-wrap mb-3">
                                    <i :class="val.icon"></i>
                                </div>
                                <h5 class="font-weight-bold mb-2">{{ val.title }}</h5>
                                <p class="text-muted small mb-0">{{ val.text }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Team -->
            <section class="py-5 bg-white">
                <div class="container">
                    <div class="section-header text-center mb-5">
                        <span class="badge badge-success-soft mb-2"><i class="fas fa-users mr-1"></i>Our Team</span>
                        <h2 class="font-weight-bold" style="color:#1a3a22">Meet the People Behind Tender Link</h2>
                        <p class="text-muted mx-auto" style="max-width:520px">
                            A passionate group of professionals committed to transforming procurement in Kenya.
                        </p>
                    </div>
                    <div class="row justify-content-center">
                        <div v-for="member in team" :key="member.name" class="col-sm-6 col-lg-3 mb-4">
                            <div class="team-card text-center h-100">
                                <img :src="member.avatar" :alt="member.name" class="team-avatar mb-3" />
                                <h6 class="font-weight-bold mb-1">{{ member.name }}</h6>
                                <p class="text-success small font-weight-bold mb-2">{{ member.role }}</p>
                                <p class="text-muted small mb-0">{{ member.bio }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="about-cta py-5">
                <div class="container text-center">
                    <h2 class="font-weight-bold text-white mb-3">Ready to find your next opportunity?</h2>
                    <p class="text-white-50 mb-4">Join thousands of suppliers and institutions already on Tender Link.</p>
                    <div class="d-flex justify-content-center flex-wrap" style="gap:1rem">
                        <Link :href="route('register')" class="btn btn-light btn-lg px-5 font-weight-bold" style="color:#28a745">
                            <i class="fas fa-user-plus mr-2"></i>Register Free
                        </Link>
                        <Link :href="route('tenders.search')" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-search mr-2"></i>Browse Tenders
                        </Link>
                    </div>
                </div>
            </section>

        </main>

        <!-- ── Footer ──────────────────────────────────────────── -->
        <footer class="landing-footer border-top">
            <div class="container footer-content-wrap">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <img src="/images/tender-link-logo.svg" alt="Tender Link" class="footer-logo mb-3 mb-md-0" />
                    <div class="footer-links d-flex align-items-center flex-wrap">
                        <a href="#" class="footer-link mr-3">Terms and Conditions</a>
                        <Link :href="route('contact')" class="footer-link">Contact Us</Link>
                    </div>
                </div>
                <div class="footer-bottom text-center text-md-left mt-3 pt-3">
                    <small class="text-muted">© {{ currentYear }} Tender Link. All rights reserved.</small>
                </div>
            </div>
        </footer>
        <!-- ── /Footer ─────────────────────────────────────────── -->

    </div>
</template>

<style scoped>
.landing-page { overflow-x: hidden; }

.brand-logo-full { height: 44px; width: auto; display: block; }
.footer-logo    { height: 34px; width: auto; display: block; }
.landing-footer { background: #ffffff; border-color: #dfe5e1 !important; padding: 1.15rem 0 1rem; }
.footer-links   { gap: .35rem; }
.footer-link    { color: #1f8f53; font-size: .94rem; font-weight: 600; text-decoration: none; }
.footer-link:hover { color: #28a745; text-decoration: underline; }
.footer-bottom  { border-top: 1px solid #ebf1ed; }

/* ── Hero ─────────── */
.about-hero {
    background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80') center/cover no-repeat;
    min-height: 380px;
    display: flex;
    align-items: center;
}
.about-hero-overlay {
    width: 100%;
    background: rgba(10, 40, 20, 0.62);
    min-height: 380px;
    display: flex;
    align-items: center;
    padding: 3rem 0;
}
.about-hero-title {
    font-size: 2.6rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: .5rem;
}
.about-hero-sub {
    font-size: 1.1rem;
    color: rgba(255,255,255,.82);
    max-width: 580px;
    margin: 0 auto;
}

/* ── Stats ──────────── */
.stats-bar { background: #1a3a22; }
.stat-item  { padding: 1rem .5rem; }
.stat-value { font-size: 2rem; font-weight: 800; color: #fff; display: block; line-height: 1; margin-bottom: .35rem; }
.stat-label { color: rgba(255,255,255,.7); font-size: .85rem; text-transform: uppercase; letter-spacing: .04em; }

/* ── Values ──────────── */
.value-card {
    background: #fff;
    border: 1px solid #e9f5ed;
    border-radius: .75rem;
    padding: 1.75rem 1.5rem;
    transition: box-shadow .2s;
}
.value-card:hover { box-shadow: 0 6px 20px rgba(40,167,69,.12); }
.value-icon-wrap {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(40,167,69,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #28a745;
}

/* ── Team ───────────── */
.team-card {
    background: #fff;
    border: 1px solid #e9f5ed;
    border-radius: .75rem;
    padding: 2rem 1.25rem;
    transition: box-shadow .2s;
}
.team-card:hover { box-shadow: 0 6px 20px rgba(40,167,69,.12); }
.team-avatar {
    width: 88px; height: 88px; border-radius: 50%;
    object-fit: cover; border: 3px solid #28a745;
}

/* ── CTA ────────────── */
.about-cta {
    background: linear-gradient(135deg, #1a5c2e 0%, #28a745 100%);
}

/* ── Badge soft ─────── */
.badge-success-soft {
    background: rgba(40,167,69,.12);
    color: #1a6130;
    font-size: .8rem;
    font-weight: 600;
    padding: .35em .75em;
    border-radius: 99px;
}

@media (max-width: 767px) {
    .about-hero-title { font-size: 1.8rem; }
}
</style>
