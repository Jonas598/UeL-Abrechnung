<!-- language: vue -->
<!-- my-app/src/views/ReleaseSubmissions.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

function goBack() {
  router.push({ name: 'Dashboard' })
}

// [BACKEND] Hier später die finale URL eintragen
const API_URL = 'http://127.0.0.1:8000/api/PLACEHOLDER-release-submissions'

// Typ für einen Eintrag in der Freigabe-Liste
interface Submission {
  id: number
  uebungsleiter: string
  abteilung: string
  zeitraum: string
  stunden: number
  betrag: number
  status: 'offen' | 'freigegeben'
}

// Typ für die erwartete API-Antwort
interface ReleaseSubmissionsResponse {
  submissions: Submission[]
}

// State
const isLoading = ref<boolean>(false)
const errorMessage = ref<string | null>(null)
const submissions = ref<Submission[]>([])

// Daten vom Backend holen
async function fetchReleaseSubmissions() {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await axios.get<ReleaseSubmissionsResponse>(API_URL)
    submissions.value = response.data.submissions
  } catch (error: any) {
    errorMessage.value =
        error?.response?.data?.message ||
        'Die Abrechnungen zur Freigabe konnten nicht geladen werden.'
  } finally {
    isLoading.value = false
  }
}

// Platzhalter für "Freigeben"
async function approveSubmission(id: number) {
  // [BACKEND] Hier später POST/PUT auf die API, z.B.:
  // await axios.post(`${API_URL}/${id}/approve`)
  const item = submissions.value.find(s => s.id === id)
  if (item) {
    item.status = 'freigegeben'
  }
}

onMounted(() => {
  fetchReleaseSubmissions()
})
</script>

<template>
  <div class="page">
    <div class="d-flex justify-start mb-4">
      <v-btn
          color="primary"
          variant="tonal"
          prepend-icon="mdi-arrow-left"
          @click="goBack"
      >
        Zurück zum Dashboard
      </v-btn>
    </div>

    <v-card elevation="6" class="pa-4">
      <v-card-title class="pa-0 mb-4 d-flex align-center justify-space-between">
        <h3 class="ma-0">Abrechnungen freigeben</h3>

        <v-btn
            size="small"
            variant="text"
            color="primary"
            :loading="isLoading"
            @click="fetchReleaseSubmissions"
        >
          Aktualisieren
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <!-- Ladezustand -->
        <div v-if="isLoading" class="placeholder">
          Daten werden geladen ...
        </div>

        <!-- Fehlermeldung -->
        <v-alert
            v-else-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
        >
          {{ errorMessage }}
        </v-alert>

        <!-- Liste der Abrechnungen -->
        <div v-else-if="submissions.length > 0" class="list">
          <div
              v-for="item in submissions"
              :key="item.id"
              class="submission-row"
          >
            <div class="submission-main">
              <div class="line">
                <span class="label">Übungsleiter:</span>
                <span class="value">{{ item.uebungsleiter }}</span>
              </div>
              <div class="line">
                <span class="label">Abteilung:</span>
                <span class="value">{{ item.abteilung }}</span>
              </div>
              <div class="line">
                <span class="label">Zeitraum:</span>
                <span class="value">{{ item.zeitraum }}</span>
              </div>
              <div class="line">
                <span class="label">Stunden:</span>
                <span class="value">
                  {{
                    item.stunden.toLocaleString('de-DE', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    })
                  }}
                  Std.
                </span>
              </div>
              <div class="line">
                <span class="label">Betrag:</span>
                <span class="value">
                  {{
                    item.betrag.toLocaleString('de-DE', {
                      style: 'currency',
                      currency: 'EUR'
                    })
                  }}
                </span>
              </div>
              <div class="line">
                <span class="label">Status:</span>
                <span class="value">
                  {{ item.status === 'offen' ? 'Offen' : 'Freigegeben' }}
                </span>
              </div>
            </div>

            <div class="submission-actions">
              <v-btn
                  size="small"
                  color="success"
                  variant="tonal"
                  :disabled="item.status === 'freigegeben'"
                  @click="approveSubmission(item.id)"
              >
                Freigeben
              </v-btn>
            </div>
          </div>
        </div>

        <!-- Fallback -->
        <div v-else class="placeholder">
          Hier werden später die eingereichten Abrechnungen deiner
          Abteilungen angezeigt, die du prüfen und freigeben kannst.
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.page {
  padding: 24px;
  max-width: 800px;
  margin: 0 auto;
}

.placeholder {
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(0, 0, 0, 0.6);
  font-size: 1rem;
  border-radius: 8px;
  background: rgba(0,0,0,0.02);
  margin-top: 8px;
  padding: 16px;
  text-align: center;
}

.list {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.submission-row {
  display: flex;
  justify-content: space-between;
  align-items: stretch;
  padding: 12px 16px;
  border-radius: 8px;
  background: rgba(0,0,0,0.02);
}

.submission-main {
  flex: 1;
}

.line {
  display: flex;
  gap: 8px;
  margin-bottom: 4px;
}

.line:last-child {
  margin-bottom: 0;
}

.label {
  min-width: 110px;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.7);
}

.value {
  font-weight: 400;
}

.submission-actions {
  display: flex;
  align-items: center;
  margin-left: 16px;
}
</style>
