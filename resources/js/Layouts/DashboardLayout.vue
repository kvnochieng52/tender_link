<script setup>
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import LoadingBar from "@/Components/LoadingBar.vue";

const sidebarOpen = ref(false);

const user = computed(() => usePage().props.auth.user);
const isAdmin = computed(() => usePage().props.auth.isAdmin);

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
  sidebarOpen.value = false;
};

const logout = () => {
  router.post(route("logout"));
};
</script>

<template>
  <LoadingBar />

  <div class="dashboard-shell bg-light">
    <nav
      class="navbar navbar-expand-md navbar-white bg-white border-bottom shadow-sm sticky-top px-0"
    >
      <div
        class="container d-flex align-items-center justify-content-between flex-wrap"
      >
        <div class="d-flex align-items-center">
          <button
            class="btn btn-light border d-md-none mr-2"
            type="button"
            @click="toggleSidebar"
            aria-label="Toggle sidebar"
          >
            <i class="fas fa-bars"></i>
          </button>
          <Link :href="route('welcome')" class="navbar-brand mr-0 py-2">
            <img
              src="/images/tender-link-logo.svg"
              alt="Tender Link"
              class="brand-logo-full"
            />
          </Link>
        </div>

        <div class="top-nav-links d-none d-md-flex align-items-center">
          <Link :href="route('welcome')" class="nav-link px-2">Home</Link>
          <a href="#" class="nav-link px-2">About Us</a>
          <a href="#" class="nav-link px-2">Services</a>
          <a href="#" class="nav-link px-2">Browse Tenders</a>
          <a href="#" class="nav-link px-2">Contact Us</a>
        </div>

        <div class="d-flex align-items-center flex-wrap py-2">
          <Link
            :href="route('profile.edit')"
            class="btn btn-outline-success btn-sm mr-2 mb-1 mb-md-0"
          >
            {{ user?.name || "My Profile" }}
          </Link>
          <button
            class="btn btn-success btn-sm mb-1 mb-md-0"
            type="button"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </div>
    </nav>

    <div class="dashboard-body">
      <aside :class="['dashboard-sidebar', sidebarOpen ? 'is-open' : '']">
        <div
          class="sidebar-header d-flex justify-content-between align-items-center"
        >
          <h6 class="mb-0 font-weight-bold text-success">Manage Portal</h6>
          <button
            class="btn btn-sm btn-light border d-md-none"
            type="button"
            @click="closeSidebar"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <nav class="sidebar-nav mt-3">
          <!-- Dashboard: visible to all -->
          <Link
            :href="route('dashboard')"
            :class="[
              'sidebar-link',
              {
                active:
                  route().current('dashboard') ||
                  route().current('home.dashboard') ||
                  route().current('home'),
              },
            ]"
            @click="closeSidebar"
          >
            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard Overview
          </Link>

          <!-- My Applications: visible to all -->
          <a href="#" class="sidebar-link" @click="closeSidebar">
            <i class="fas fa-file-alt mr-2"></i> My Applications
          </a>

          <!-- Admin-only section -->
          <template v-if="isAdmin">
            <div class="sidebar-section-label mt-3 mb-1">Admin</div>

            <Link
              :href="route('tenders.index')"
              :class="[
                'sidebar-link',
                { active: route().current('tenders.index') },
              ]"
              @click="closeSidebar"
            >
              <i class="fas fa-list mr-2"></i> Tender List
            </Link>
            <Link
              :href="route('tenders.create')"
              :class="[
                'sidebar-link',
                { active: route().current('tenders.create') },
              ]"
              @click="closeSidebar"
            >
              <i class="fas fa-bullhorn mr-2"></i> New Tender
            </Link>
            <Link
              :href="route('institutions.index')"
              :class="[
                'sidebar-link',
                { active: route().current('institutions.*') },
              ]"
              @click="closeSidebar"
            >
              <i class="fas fa-building mr-2"></i> Institutions
            </Link>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-list-check mr-2"></i> All Applications
            </a>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-clipboard-check mr-2"></i> Evaluation
            </a>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-award mr-2"></i> Award &amp; Notification
            </a>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-users mr-2"></i> User Management
            </a>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-chart-line mr-2"></i> Reports
            </a>
            <a href="#" class="sidebar-link" @click="closeSidebar">
              <i class="fas fa-cog mr-2"></i> Settings
            </a>
          </template>
        </nav>
      </aside>

      <main class="dashboard-content">
        <slot />
      </main>
    </div>

    <footer class="dashboard-footer border-top">
      <div class="container d-flex justify-content-between flex-wrap py-2">
        <small class="text-muted"
          >© {{ new Date().getFullYear() }} Tender Link</small
        >
        <small class="text-muted">Smart Tender Discovery Platform</small>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.dashboard-shell {
  min-height: 100vh;
}

.brand-logo-full {
  height: 44px;
  width: auto;
  display: block;
}

.top-nav-links .nav-link {
  color: #2f3e46;
  font-weight: 600;
}

.top-nav-links .nav-link:hover {
  color: #1f8f53;
}

.dashboard-body {
  display: flex;
  min-height: calc(100vh - 124px);
}

.dashboard-sidebar {
  width: 280px;
  background: #f4f7f5;
  border-right: 1px solid #e1e8e3;
  padding: 1rem;
}

.sidebar-header {
  padding: 0.5rem 0.35rem;
  border-bottom: 1px solid #e5ece7;
}

.sidebar-section-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8fa897;
  padding: 0 0.8rem;
}

.sidebar-link {
  display: block;
  border-radius: 0.55rem;
  padding: 0.7rem 0.8rem;
  color: #415149;
  font-weight: 600;
  margin-bottom: 0.35rem;
  text-decoration: none;
  transition: all 0.2s ease;
}

.sidebar-link:hover {
  background: rgba(40, 167, 69, 0.1);
  color: #1f8f53;
}

.sidebar-link.active {
  background: rgba(40, 167, 69, 0.15);
  color: #1f8f53;
}

.dashboard-content {
  flex: 1;
  padding: 1.15rem;
}

.dashboard-footer {
  background: #fff;
}

@media (max-width: 767.98px) {
  .dashboard-body {
    position: relative;
  }

  .dashboard-sidebar {
    position: fixed;
    top: 74px;
    left: -300px;
    bottom: 0;
    z-index: 1030;
    box-shadow: 0.5rem 0 1.3rem rgba(0, 0, 0, 0.08);
    transition: left 0.25s ease;
  }

  .dashboard-sidebar.is-open {
    left: 0;
  }

  .dashboard-content {
    padding: 0.95rem;
  }
}
</style>
