<script setup>
import { ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
  users: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? "");
const filterRole = ref(props.filters.role ?? "");

function applyFilters() {
  router.get(
    route("admin.users.index"),
    { q: search.value || undefined, role: filterRole.value || undefined },
    { preserveState: true, replace: true }
  );
}

let timer = null;
watch(search, () => {
  clearTimeout(timer);
  timer = setTimeout(applyFilters, 350);
});
watch(filterRole, applyFilters);

// ─── Role edit ─────────────────────────────────────────────────────
const editingRole = ref(null); // user id currently being edited
const roleDraft = ref("");

function openRoleEdit(user) {
  editingRole.value = user.id;
  roleDraft.value = user.roles?.[0]?.name ?? "";
}

function saveRole(user) {
  router.patch(
    route("admin.users.update-role", { user: user.id }),
    { role: roleDraft.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        editingRole.value = null;
      },
    }
  );
}

function toggleVerified(user) {
  router.patch(
    route("admin.users.toggle-verified", { user: user.id }),
    {},
    { preserveScroll: true }
  );
}

function toggleActive(user) {
  router.patch(
    route("admin.users.toggle-active", { user: user.id }),
    {},
    { preserveScroll: true }
  );
}

const formatDate = (v) =>
  v
    ? new Date(v).toLocaleString("en-KE", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    : "—";

// ─── Create user modal ──────────────────────────────────────────────
const showCreate = ref(false);
const createForm = useForm({
  name: "",
  email: "",
  telephone: "",
  password: "",
  password_confirmation: "",
  role: "",
});

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  showCreate.value = true;
  document.body.style.overflow = "hidden";
}
function closeCreate() {
  showCreate.value = false;
  document.body.style.overflow = "";
}
function submitCreate() {
  createForm.post(route("admin.users.store"), {
    preserveScroll: true,
    onSuccess: closeCreate,
  });
}

// ─── Edit user modal ────────────────────────────────────────────────
const showEdit = ref(false);
const editUserId = ref(null);
const editForm = useForm({ name: "", email: "", telephone: "" });

function openEdit(user) {
  editUserId.value = user.id;
  editForm.name = user.name ?? "";
  editForm.email = user.email ?? "";
  editForm.telephone = user.telephone ?? "";
  editForm.clearErrors();
  showEdit.value = true;
  document.body.style.overflow = "hidden";
}
function closeEdit() {
  showEdit.value = false;
  document.body.style.overflow = "";
}
function submitEdit() {
  editForm.put(route("admin.users.update", { user: editUserId.value }), {
    preserveScroll: true,
    onSuccess: closeEdit,
  });
}
</script>

<template>
  <DashboardLayout>
    <Head title="User Management — Admin" />

    <div class="card border-0 shadow-sm mb-4">
      <!-- Header / filters -->
      <div
        class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap"
        style="gap: 0.5rem"
      >
        <h5 class="mb-0">
          <i class="fas fa-users mr-2 text-success"></i> User Management
        </h5>

        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem">
          <input
            v-model="search"
            type="search"
            class="form-control form-control-sm"
            placeholder="Search name, email or phone…"
            style="min-width: 220px"
          />
          <select
            v-model="filterRole"
            class="form-control form-control-sm"
            style="min-width: 140px"
          >
            <option value="">All Roles</option>
            <option v-for="r in roles" :key="r" :value="r">
              {{ r.charAt(0).toUpperCase() + r.slice(1) }}
            </option>
          </select>
        </div>

        <span class="badge badge-secondary">{{ users.total }} total</span>

        <button class="btn btn-sm btn-success" @click="openCreate">
          <i class="fas fa-user-plus mr-1"></i> Create User
        </button>
      </div>

      <!-- Table -->
      <div class="card-body p-0">
        <div v-if="users.data.length === 0" class="p-4 text-center text-muted">
          No users found.
        </div>

        <div v-else class="table-responsive">
          <table class="table mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th class="text-center">Verified</th>
                <th class="text-center">Active</th>
                <th class="text-center">Applications</th>
                <th class="text-center">Transactions</th>
                <th>Joined</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(user, idx) in users.data" :key="user.id">
                <td>
                  {{ (users.current_page - 1) * users.per_page + idx + 1 }}
                </td>

                <!-- Name + avatar -->
                <td>
                  <div class="d-flex align-items-center" style="gap: 0.5rem">
                    <img
                      v-if="user.avatar"
                      :src="user.avatar"
                      class="rounded-circle"
                      width="32"
                      height="32"
                      style="object-fit: cover"
                    />
                    <div
                      v-else
                      class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white"
                      style="
                        width: 32px;
                        height: 32px;
                        font-size: 0.75rem;
                        flex-shrink: 0;
                      "
                    >
                      {{ user.name?.charAt(0).toUpperCase() }}
                    </div>
                    <span>{{ user.name }}</span>
                  </div>
                </td>

                <td class="small">{{ user.email }}</td>
                <td class="small">{{ user.telephone || "—" }}</td>

                <!-- Role (inline edit) -->
                <td>
                  <div
                    v-if="editingRole === user.id"
                    class="d-flex align-items-center"
                    style="gap: 0.25rem"
                  >
                    <select
                      v-model="roleDraft"
                      class="form-control form-control-sm"
                      style="min-width: 110px"
                    >
                      <option v-for="r in roles" :key="r" :value="r">
                        {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                      </option>
                    </select>
                    <button
                      class="btn btn-sm btn-success"
                      @click="saveRole(user)"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-secondary"
                      @click="editingRole = null"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <span
                    v-else
                    :class="[
                      'badge',
                      user.roles?.[0]?.name === 'admin'
                        ? 'badge-danger'
                        : 'badge-secondary',
                    ]"
                  >
                    {{ user.roles?.[0]?.name ?? "no role" }}
                  </span>
                </td>

                <!-- Verified -->
                <td class="text-center">
                  <span
                    v-if="user.email_verified_at"
                    class="badge badge-success"
                    title="Email verified"
                  >
                    <i class="fas fa-check"></i> Verified
                  </span>
                  <span v-else class="badge badge-warning">Unverified</span>
                </td>

                <!-- Active -->
                <td class="text-center">
                  <span
                    :class="[
                      'badge',
                      user.is_active ? 'badge-success' : 'badge-danger',
                    ]"
                  >
                    {{ user.is_active ? "Active" : "Inactive" }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="badge badge-info">
                    {{ user.applications_count ?? 0 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge badge-primary">
                    {{ user.transactions_count ?? 0 }}
                  </span>
                </td>

                <td class="small text-nowrap">
                  {{ formatDate(user.created_at) }}
                </td>

                <!-- Actions -->
                <td class="text-right text-nowrap">
                  <button
                    class="btn btn-sm btn-outline-secondary mr-1"
                    title="Edit user"
                    @click="openEdit(user)"
                  >
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-outline-primary mr-1"
                    title="Change role"
                    @click="openRoleEdit(user)"
                  >
                    <i class="fas fa-user-tag"></i>
                  </button>
                  <button
                    :class="[
                      'btn btn-sm',
                      user.email_verified_at
                        ? 'btn-outline-warning'
                        : 'btn-outline-success',
                    ]"
                    :title="
                      user.email_verified_at
                        ? 'Revoke verification'
                        : 'Mark as verified'
                    "
                    @click="toggleVerified(user)"
                  >
                    <i
                      :class="[
                        'fas',
                        user.email_verified_at
                          ? 'fa-user-times'
                          : 'fa-user-check',
                      ]"
                    ></i>
                  </button>
                  <button
                    :class="[
                      'btn btn-sm',
                      user.is_active
                        ? 'btn-outline-danger'
                        : 'btn-outline-success',
                    ]"
                    :title="
                      user.is_active ? 'Deactivate account' : 'Activate account'
                    "
                    @click="toggleActive(user)"
                  >
                    <i
                      :class="[
                        'fas',
                        user.is_active ? 'fa-ban' : 'fa-check-circle',
                      ]"
                    ></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="users.last_page > 1"
        class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap"
        style="gap: 0.5rem"
      >
        <small class="text-muted">
          Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
        </small>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li
              v-for="link in users.links"
              :key="link.label"
              :class="[
                'page-item',
                { active: link.active, disabled: !link.url },
              ]"
            >
              <button
                v-if="link.url"
                class="page-link"
                @click="router.get(link.url, {}, { preserveState: true })"
                v-html="link.label"
              ></button>
              <span v-else class="page-link" v-html="link.label"></span>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </DashboardLayout>

  <!-- ── Create User Modal ──────────────────────────────────────── -->
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="showCreate"
        class="modal-backdrop-custom"
        style="
          position: fixed;
          inset: 0;
          background: rgba(0, 0, 0, 0.45);
          z-index: 1050;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
        "
        @mousedown.self="closeCreate"
      >
        <div
          class="card border-0 shadow-lg"
          style="
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
          "
        >
          <div
            class="card-header bg-white d-flex justify-content-between align-items-center"
          >
            <h6 class="mb-0 font-weight-bold">
              <i class="fas fa-user-plus mr-2 text-success"></i> Create New User
            </h6>
            <button
              type="button"
              class="btn btn-sm btn-light"
              @click="closeCreate"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitCreate">
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Full Name <span class="text-danger">*</span></label
                >
                <input
                  v-model="createForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': createForm.errors.name }"
                  placeholder="John Doe"
                />
                <div v-if="createForm.errors.name" class="invalid-feedback">
                  {{ createForm.errors.name }}
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Email Address <span class="text-danger">*</span></label
                >
                <input
                  v-model="createForm.email"
                  type="email"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': createForm.errors.email }"
                  placeholder="user@example.com"
                />
                <div v-if="createForm.errors.email" class="invalid-feedback">
                  {{ createForm.errors.email }}
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold">Phone</label>
                <input
                  v-model="createForm.telephone"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': createForm.errors.telephone }"
                  placeholder="+254 7XX XXX XXX"
                />
                <div
                  v-if="createForm.errors.telephone"
                  class="invalid-feedback"
                >
                  {{ createForm.errors.telephone }}
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Role <span class="text-danger">*</span></label
                >
                <select
                  v-model="createForm.role"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': createForm.errors.role }"
                >
                  <option value="">— Select role —</option>
                  <option v-for="r in roles" :key="r" :value="r">
                    {{ r.charAt(0).toUpperCase() + r.slice(1) }}
                  </option>
                </select>
                <div v-if="createForm.errors.role" class="invalid-feedback">
                  {{ createForm.errors.role }}
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Password <span class="text-danger">*</span></label
                >
                <input
                  v-model="createForm.password"
                  type="password"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': createForm.errors.password }"
                  placeholder="Min. 8 characters"
                />
                <div v-if="createForm.errors.password" class="invalid-feedback">
                  {{ createForm.errors.password }}
                </div>
              </div>
              <div class="form-group mb-0">
                <label class="small font-weight-bold"
                  >Confirm Password <span class="text-danger">*</span></label
                >
                <input
                  v-model="createForm.password_confirmation"
                  type="password"
                  class="form-control form-control-sm"
                  placeholder="Repeat password"
                />
              </div>
            </form>
          </div>
          <div
            class="card-footer bg-white d-flex justify-content-end"
            style="gap: 0.5rem"
          >
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="closeCreate"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-sm btn-success"
              :disabled="createForm.processing"
              @click="submitCreate"
            >
              <i class="fas fa-user-plus mr-1"></i>
              {{ createForm.processing ? "Creating…" : "Create User" }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>

  <!-- ── Edit User Modal ────────────────────────────────────────── -->
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="showEdit"
        class="modal-backdrop-custom"
        style="
          position: fixed;
          inset: 0;
          background: rgba(0, 0, 0, 0.45);
          z-index: 1050;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
        "
        @mousedown.self="closeEdit"
      >
        <div
          class="card border-0 shadow-lg"
          style="width: 100%; max-width: 440px"
        >
          <div
            class="card-header bg-white d-flex justify-content-between align-items-center"
          >
            <h6 class="mb-0 font-weight-bold">
              <i class="fas fa-user-edit mr-2 text-primary"></i> Edit User
            </h6>
            <button
              type="button"
              class="btn btn-sm btn-light"
              @click="closeEdit"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitEdit">
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Full Name <span class="text-danger">*</span></label
                >
                <input
                  v-model="editForm.name"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': editForm.errors.name }"
                />
                <div v-if="editForm.errors.name" class="invalid-feedback">
                  {{ editForm.errors.name }}
                </div>
              </div>
              <div class="form-group">
                <label class="small font-weight-bold"
                  >Email Address <span class="text-danger">*</span></label
                >
                <input
                  v-model="editForm.email"
                  type="email"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': editForm.errors.email }"
                />
                <div v-if="editForm.errors.email" class="invalid-feedback">
                  {{ editForm.errors.email }}
                </div>
              </div>
              <div class="form-group mb-0">
                <label class="small font-weight-bold">Phone</label>
                <input
                  v-model="editForm.telephone"
                  type="text"
                  class="form-control form-control-sm"
                  :class="{ 'is-invalid': editForm.errors.telephone }"
                />
                <div v-if="editForm.errors.telephone" class="invalid-feedback">
                  {{ editForm.errors.telephone }}
                </div>
              </div>
            </form>
          </div>
          <div
            class="card-footer bg-white d-flex justify-content-end"
            style="gap: 0.5rem"
          >
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="closeEdit"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-sm btn-primary"
              :disabled="editForm.processing"
              @click="submitEdit"
            >
              <i class="fas fa-save mr-1"></i>
              {{ editForm.processing ? "Saving…" : "Save Changes" }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
