<!-- language: vue -->
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

type Berechtigung = {
  rolle: string
  abteilung: string
}

const router = useRouter()
const user = ref<any>(null)
const permissions = ref<Berechtigung[]>([])
const isLoading = ref(true)
const API_URL = 'http://127.0.0.1:8000/api'

const isDepartmentHead = computed(() => {
  return permissions.value.some(p => p.rolle === 'Abteilungsleiter')
})

const isTrainer = computed(() => {
  return permissions.value.some(p => p.rolle === 'Übungsleiter')
})

const isAdmin = computed(() => user.value?.isAdmin === true)

const isOfficeManager = computed(() => {
  return permissions.value.some(p => p.rolle === 'Geschäftsstelle')
})

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/dashboard`)
    user.value = response.data.user
    permissions.value = response.data.berechtigungen
  } catch (error) {
    console.error('Fehler beim Laden:', error)
    handleLogout()
  } finally {
    isLoading.value = false
  }
})

const handleLogout = async () => {
  try {
    await axios.post(`${API_URL}/logout`)
  } catch (error) {
    console.warn('Logout Backend-Warnung:', error)
  } finally {
    localStorage.removeItem('auth_token')
    delete axios.defaults.headers.common['Authorization']
    router.push({ name: 'Login' })
  }
}
</script>

<template>
  <div class="page">
    <div class="d-flex justify-space-between align-center mb-4">
      <div>
        <h2>Dashboard</h2>
        <div v-if="user" class="text-subtitle-1 text-medium-emphasis">
          Hallo, {{ user.vorname }} {{ user.name }}!
        </div>
        <div v-else-if="isLoading" class="text-caption">
          Lade Benutzerdaten...
        </div>
      </div>

      <v-btn
          color="error"
          variant="tonal"
          prepend-icon="mdi-logout"
          @click="handleLogout"
      >
        Abmelden
      </v-btn>
    </div>

    <v-card class="pa-4" :loading="isLoading">
      <template v-if="user">
        <p>Du bist eingeloggt als: <strong>{{ user.email }}</strong></p>
        <p class="mt-2">Hier ist dein geschützter Bereich für Zeiterfassung und Co.</p>
        <div class="mt-2 text-body-2 text-medium-emphasis">
          <span v-if="user.isAdmin">Du hast Admin-Rechte!</span>
        </div>

        <v-divider class="my-4"></v-divider>

        <h3 class="text-subtitle-1 font-weight-bold mb-2">Deine Abteilungen und Rollen</h3>

        <ul v-if="permissions.length > 0" class="ml-4">
          <li v-for="(item, index) in permissions" :key="index" class="mb-1">
            <strong>{{ item.abteilung }}</strong>:
            {{ item.rolle === 'Uebungsleiter' ? 'Übungsleiter' : item.rolle }}
          </li>
        </ul>
        <p v-else class="text-caption text-medium-emphasis">
          Keine Abteilungen zugewiesen.
        </p>
      </template>

      <div class="btn-grid mt-4">
        <!-- Übungsleiter Bereich -->
        <div class="section-title">Übungsleiter Bereich</div>

        <v-btn
            class="mt-4 w-100"
            color="primary"
            prepend-icon="mdi-clock-outline"
            @click="router.push({ name: 'Timesheet' })"
        >
          Stunde erfassen
        </v-btn>

        <v-btn
            class="mt-4 w-100"
            color="primary"
            prepend-icon="mdi-clock-outline"
            @click="router.push({ name: 'Drafts' })"
        >
          Übersicht Entwürfe
        </v-btn>

        <v-btn
            class="mt-4 w-100"
            color="primary"
            prepend-icon="mdi-clock-outline"
            @click="router.push({ name: 'TimesheetSubmissions' })"
        >
          Übersicht Abrechnungen
        </v-btn>

        <br>

        <!-- Abteilungsleiter Bereich -->
        <div>
          <div class="section-title">Abteilungsleiter Bereich</div>

          <v-btn
              class="mt-4 w-100"
              color="primary"
              prepend-icon="mdi-check-decagram-outline"
              @click="router.push({ name: 'ReleaseSubmissions' })"
          >
            Abrechnungen freigeben
          </v-btn>
        </div>

        <br>

        <!-- Geschäftsstellen Bereich -->
        <div>
          <div class="section-title">Geschäftsstelle</div>

          <v-btn
              class="mt-4 w-100"
              color="primary"
              prepend-icon="mdi-file-document-outline"
              @click="router.push({ name: 'AllTimesheetSubmissions' })"
          >
            Übersicht der Abrechnungen
          </v-btn>
        </div>

        <br>

        <!-- Admin Bereich -->
        <div v-if="isAdmin">
          <div class="section-title">Admin Bereich</div>

          <v-btn
              class="mt-4 w-100"
              color="primary"
              prepend-icon="mdi-account-plus"
              :to="{ name: 'CreateUser' }"
          >
            User erstellen
          </v-btn>
        </div>
      </div>
    </v-card>
  </div>
</template>

<style scoped>
.btn-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  row-gap: 5px;
  column-gap: 16px;
  align-items: start;
}

.section-title {
  grid-column: 1 / -1;
  font-size: 0.95rem;
  font-weight: 600;
  color: rgba(0,0,0,0.7);
  margin-top: 40px;
  margin-bottom: 0px;
  align-self: center;
}

.btn-grid .grid-btn {
  width: 100%;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-left: 16px;
  padding-right: 16px;
  text-transform: uppercase;
  box-sizing: border-box;
}

@media (max-width: 600px) {
  .btn-grid {
    grid-template-columns: 1fr;
  }
  .btn-grid .grid-btn {
    height: 48px;
  }
}
</style>
