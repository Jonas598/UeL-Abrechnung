<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
// Variable für den User (Name, Email etc.)
const user = ref<any>(null)
const isLoading = ref(true)

// API-URL für User-Daten (passt zu deiner api.php route: Route::get('/user'...))
const API_URL = 'http://127.0.0.1:8000/api'

// Beim Laden der Komponente: User Daten holen
onMounted(async () => {
  try {
    // Da wir das Token schon im Header haben (aus main.ts oder EnterPassword),
    // können wir direkt anfragen.
    const response = await axios.get(`${API_URL}/user`)
    user.value = response.data
  } catch (error) {
    console.error('Fehler beim Laden des Users:', error)
    // Wenn der User nicht geladen werden kann (z.B. Token ungültig),
    // schicken wir ihn zum Login zurück.
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
    <!-- Header Zeile -->
    <div class="d-flex justify-space-between align-center mb-4">
      <div>
        <h2>Dashboard</h2>
        <!-- Zeige Namen an, wenn geladen -->
        <div v-if="user" class="text-subtitle-1 text-medium-emphasis">
          Hallo, {{user.vorname}} {{ user.name }}!
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

    <!-- Inhalt -->
    <v-card class="pa-4" :loading="isLoading">
      <template v-if="user">
        <p>Du bist eingeloggt als: <strong>{{ user.email }}</strong></p>
        <p class="mt-2">Hier ist dein geschützter Bereich für Zeiterfassung und Co.</p>
      </template>
    </v-card>

    <v-btn
        class="mt-4"
        color="primary"
        prepend-icon="mdi-clock-outline"
        @click="router.push({ name: 'Timesheet' })"
    >
      Zur Stundenerfassung
    </v-btn>
  </div>
</template>

<style scoped>
.page {
  padding: 24px;
  max-width: 800px;
  margin: 0 auto;
}
</style>