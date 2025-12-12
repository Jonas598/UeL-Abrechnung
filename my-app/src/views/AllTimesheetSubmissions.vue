<!-- my-app/src/views/AllTimesheetSubmissions.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

function goBack() {
  router.push({ name: 'Dashboard' })
}

// [BACKEND] Hier später die finale URL für "alle Abrechnungen" eintragen
const API_URL = 'http://127.0.0.1:8000/api/PLACEHOLDER-all-timesheet-submissions'

// State
const isLoading = ref<boolean>(false)
const errorMessage = ref<string | null>(null)

const totalHours = ref<number | null>(null)
const totalAmount = ref<number | null>(null)
const periodLabel = ref<string | null>(null)

// Nur als Doku für das Backend
interface AllTimesheetSummaryResponse {
  totalHours: number
  totalAmount: number
  periodLabel: string
}

// Daten vom Backend holen
async function fetchAllTimesheetSummary() {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await axios.get<AllTimesheetSummaryResponse>(API_URL)

    totalHours.value = response.data.totalHours
    totalAmount.value = response.data.totalAmount
    periodLabel.value = response.data.periodLabel
  } catch (error: any) {
    errorMessage.value =
        error?.response?.data?.message ||
        'Die Übersicht aller Abrechnungen konnte nicht geladen werden.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAllTimesheetSummary()
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
        <h3 class="ma-0">Übersicht aller Abrechnungen</h3>

        <v-btn
            size="small"
            variant="text"
            color="primary"
            :loading="isLoading"
            @click="fetchAllTimesheetSummary"
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

        <!-- Inhalt, wenn Daten da sind -->
        <div v-else-if="totalHours !== null && totalAmount !== null" class="summary">
          <div v-if="periodLabel" class="summary-row">
            <span class="label">Zeitraum:</span>
            <span class="value">{{ periodLabel }}</span>
          </div>

          <div class="summary-row">
            <span class="label">Summe Stunden (alle ÜL):</span>
            <span class="value">
              {{ totalHours?.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              Std.
            </span>
          </div>

          <div class="summary-row">
            <span class="label">Gesamtbetrag:</span>
            <span class="value">
              {{ totalAmount?.toLocaleString('de-DE', { style: 'currency', currency: 'EUR' }) }}
            </span>
          </div>
        </div>

        <!-- Fallback -->
        <div v-else class="placeholder">
          Hier siehst du später eine Zusammenfassung aller eingereichten
          Abrechnungen (z\.B. für die Geschäftsstelle).
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.page {
  padding: 24px;
  max-width: 640px;
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

.summary {
  margin-top: 8px;
  padding: 16px;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.02);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.summary-row:last-child {
  margin-bottom: 0;
}

.label {
  font-weight: 500;
  color: rgba(0, 0, 0, 0.7);
}

.value {
  font-weight: 600;
}
</style>
