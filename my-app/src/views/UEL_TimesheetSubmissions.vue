<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

function goBack() {
  router.push({ name: 'Dashboard' })
}

// URL zum neuen Endpoint
const API_URL = 'http://127.0.0.1:8000/api/abrechnung/meine'

// Interface für eine Abrechnung in der Liste
interface Submission {
  id: number
  zeitraum: string
  stunden: number
  status: string
  status_id: number
  datum_erstellt: string
}

const isLoading = ref<boolean>(false)
const errorMessage = ref<string | null>(null)
const submissions = ref<Submission[]>([])

// Daten laden
async function fetchSubmissions() {
  isLoading.value = true
  errorMessage.value = null

  try {
    const response = await axios.get<Submission[]>(API_URL)
    submissions.value = response.data
  } catch (error: any) {
    errorMessage.value =
        error?.response?.data?.message ||
        'Die Abrechnungen konnten nicht geladen werden.'
  } finally {
    isLoading.value = false
  }
}

// Hilfsfunktion: Farbe basierend auf Status-ID (Beispiel-Logik)
function getStatusColor(statusId: number): string {
  // IDs basierend auf deinem System (10=Offen, 11=Eingereicht, 21=AL genehmigt, 22=GS genehmigt)
  if (statusId === 10) return 'grey'            // Offen / Entwurf
  if (statusId === 11) return 'blue'            // Eingereicht
  if (statusId === 21) return 'orange-darken-1' // AL Freigabe (Teilweise fertig)
  if (statusId === 22) return 'green'           // GS Freigabe (Fertig)
  return 'grey-darken-1'
}

onMounted(() => {
  fetchSubmissions()
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
        <h3 class="ma-0">Meine Abrechnungen</h3>
        <v-btn
            size="small"
            variant="text"
            color="primary"
            :loading="isLoading"
            @click="fetchSubmissions"
        >
          Aktualisieren
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <div v-if="isLoading" class="placeholder">
          <v-progress-circular indeterminate color="primary" class="mb-2"></v-progress-circular>
          Lade Historie ...
        </div>

        <v-alert v-else-if="errorMessage" type="error" variant="tonal" class="mb-4">
          {{ errorMessage }}
        </v-alert>

        <div v-else-if="submissions.length > 0">
          <v-table density="comfortable" hover>
            <thead>
            <tr>
              <th class="text-left">Zeitraum</th>
              <th class="text-left">Eingereicht am</th>
              <th class="text-right">Stunden</th>
              <th class="text-center">Status</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in submissions" :key="item.id">
              <td class="font-weight-medium">{{ item.zeitraum }}</td>
              <td class="text-medium-emphasis">{{ item.datum_erstellt }}</td>
              <td class="text-right">{{ item.stunden.toLocaleString('de-DE') }} Std.</td>
              <td class="text-center">
                <v-chip
                    :color="getStatusColor(item.status_id)"
                    size="small"
                    variant="flat"
                    class="font-weight-bold text-white"
                >
                  {{ item.status }}
                </v-chip>
              </td>
            </tr>
            </tbody>
          </v-table>
        </div>

        <div v-else class="placeholder">
          <v-icon icon="mdi-file-document-outline" size="large" class="mb-2 opacity-50"></v-icon>
          Du hast noch keine Abrechnungen eingereicht.
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.page {
  padding: 24px;
  max-width: 800px; /* Etwas breiter für die Tabelle */
  margin: 0 auto;
}

.placeholder {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: rgba(0, 0, 0, 0.6);
  font-size: 1rem;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.02);
  margin-top: 8px;
  padding: 16px;
  text-align: center;
}
</style>