<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import type { UserDto, UpdateUserRolesPayload, DepartmentDto } from '../services/users';
import { fetchUsers, updateUserRoles } from '../services/users';
import axios from 'axios';

const router = useRouter();

const users = ref<UserDto[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const globalSaving = ref(false);

// Alle Abteilungen für Dropdowns, aus den vorhandenen User-Zuweisungen zusammengemergt
const allDepartments = computed<DepartmentDto[]>(() => {
  const map = new Map<number, DepartmentDto>();
  for (const user of users.value) {
    for (const d of user.departmentHeadDepartments || []) {
      if (!map.has(d.id)) map.set(d.id, d);
    }
    for (const d of user.trainerDepartments || []) {
      if (!map.has(d.id)) map.set(d.id, d);
    }
  }
  return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
});

// Edit-Status pro User (damit man togglen kann, ohne sofort zu speichern)
interface EditState {
  isAdmin: boolean;
  isGeschaeftsstelle: boolean;
  departmentHeadIds: number[];
  trainerIds: number[];
  saving: boolean;
}

const editStates = ref<Record<number, EditState>>({});

const initEditStateForUser = (user: UserDto) => {
  editStates.value[user.id] = {
    isAdmin: user.isAdmin,
    isGeschaeftsstelle: user.isGeschaeftsstelle,
    departmentHeadIds: user.departmentHeadDepartments?.map(d => d.id) ?? [],
    trainerIds: user.trainerDepartments?.map(d => d.id) ?? [],
    saving: false,
  };
};

const loadUsers = async () => {
  isLoading.value = true;
  error.value = null;
  try {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      router.push({ name: 'Login' });
      return;
    }
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    const result = await fetchUsers();
    users.value = result;
    editStates.value = {};
    for (const u of users.value) {
      initEditStateForUser(u);
    }
  } catch (e: any) {
    console.error('Fehler beim Laden der Benutzer:', e);
    error.value = 'Konnte Benutzerliste nicht laden.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadUsers);

const buildPayload = (userId: number): UpdateUserRolesPayload => {
  const state = editStates.value[userId];
  return {
    isAdmin: state.isAdmin,
    isGeschaeftsstelle: state.isGeschaeftsstelle,
    roles: {
      departmentHead: state.departmentHeadIds,
      trainer: state.trainerIds,
    },
  };
};

const saveUser = async (userId: number) => {
  const state = editStates.value[userId];
  if (!state) return;
  state.saving = true;
  error.value = null;
  try {
    const payload = buildPayload(userId);
    await updateUserRoles(userId, payload);
    successMessage.value = 'Änderungen erfolgreich gespeichert.';
    // Nach Erfolg nochmal laden, um Ansicht zu synchronisieren
    await loadUsers();
  } catch (e: any) {
    console.error('Fehler beim Speichern der Benutzerrollen:', e);
    error.value = 'Konnte Änderungen nicht speichern.';
  } finally {
    const s = editStates.value[userId];
    if (s) s.saving = false;
  }
};

// Markiert einen Benutzer als "dirty" (geändert), um das globale Speichern zu ermöglichen
const markDirty = (userId: number) => {
  const state = editStates.value[userId];
  if (!state) return;
  // Hier könnte man eine spezifische Logik einfügen, falls nötig
};

// Speichert alle Änderungen für alle Benutzer, die als "dirty" markiert sind
const saveAllChanges = async () => {
  globalSaving.value = true;
  error.value = null;
  successMessage.value = null;
  try {
    for (const user of users.value) {
      const state = editStates.value[user.id];
      if (state) {
        const payload = buildPayload(user.id);
        await updateUserRoles(user.id, payload);
      }
    }
    successMessage.value = 'Alle Änderungen erfolgreich gespeichert.';
    // Nach Erfolg nochmal laden, um Ansicht zu synchronisieren
    await loadUsers();
  } catch (e: any) {
    console.error('Fehler beim Speichern der Benutzerrollen:', e);
    error.value = 'Konnte Änderungen nicht speichern.';
  } finally {
    globalSaving.value = false;
  }
};
</script>

<template>
  <div class="page">
    <div class="d-flex justify-space-between align-center mb-4">
      <div>
        <h2>Benutzer verwalten</h2>
        <div class="text-subtitle-1 text-medium-emphasis">
          Rollenübersicht und -änderung für alle registrierten Benutzer.
        </div>
      </div>
    </div>

    <v-card class="pa-4" :loading="isLoading">
      <template v-if="error">
        <v-alert type="error" variant="tonal" class="mb-4">
          {{ error }}
        </v-alert>
      </template>

      <template v-if="successMessage">
        <v-alert type="success" variant="tonal" class="mb-4">
          {{ successMessage }}
        </v-alert>
      </template>

      <template v-if="isLoading">
        <div class="d-flex justify-center my-6">
          <v-progress-circular indeterminate color="primary" />
        </div>
      </template>

      <template v-else>
        <div v-if="!users.length" class="text-medium-emphasis">
          Keine Benutzer gefunden.
        </div>

        <div v-else>
          <div class="user-table-wrapper">
            <table class="user-table">
              <thead>
              <tr>
                <th>ID</th>
                <th>Vorname</th>
                <th>Name</th>
                <th>E-Mail</th>
                <th>Admin</th>
                <th>Geschäftsstelle</th>
                <th>Abteilungsleiter in</th>
                <th>Übungsleiter in</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>{{ user.id }}</td>
                <td>{{ user.vorname }}</td>
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>

                <td>
                  <v-select
                    v-model="editStates[user.id].isAdmin"
                    :items="[{ label: 'Ja', value: true }, { label: 'Nein', value: false }]"
                    item-title="label"
                    item-value="value"
                    density="compact"
                    hide-details
                    class="role-select"
                    @update:model-value="markDirty(user.id)"
                  />
                </td>

                <td>
                  <v-select
                    v-model="editStates[user.id].isGeschaeftsstelle"
                    :items="[{ label: 'Ja', value: true }, { label: 'Nein', value: false }]"
                    item-title="label"
                    item-value="value"
                    density="compact"
                    hide-details
                    class="role-select"
                    @update:model-value="markDirty(user.id)"
                  />
                </td>

                <td>
                  <v-select
                    v-model="editStates[user.id].departmentHeadIds"
                    :items="allDepartments"
                    item-title="name"
                    item-value="id"
                    chips
                    multiple
                    density="compact"
                    hide-details
                    placeholder="Keine"
                    class="dept-select"
                    @update:model-value="markDirty(user.id)"
                  />
                </td>

                <td>
                  <v-select
                    v-model="editStates[user.id].trainerIds"
                    :items="allDepartments"
                    item-title="name"
                    item-value="id"
                    chips
                    multiple
                    density="compact"
                    hide-details
                    placeholder="Keine"
                    class="dept-select"
                    @update:model-value="markDirty(user.id)"
                  />
                </td>
              </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-end mt-4">
            <v-btn
              color="primary"
              :loading="globalSaving"
              @click="saveAllChanges"
            >
              Änderungen speichern
            </v-btn>
          </div>
        </div>
      </template>
    </v-card>
  </div>
</template>

<style scoped>
.user-table-wrapper {
  overflow-x: auto;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
}

.user-table th,
.user-table td {
  border-bottom: 1px solid #eee;
  padding: 0.5rem 0.75rem;
  text-align: left;
  vertical-align: middle;
}

.user-table thead th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.role-select,
.dept-select {
  min-width: 120px;
}

.user-table tr:nth-child(even) {
  background-color: #fafafa;
}
</style>
