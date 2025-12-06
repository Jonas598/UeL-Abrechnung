<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import type { VForm } from 'vuetify/components'

type Department = {
  id: number
  name: string
}

const router = useRouter()
const form = ref<VForm | null>(null)
const isLoading = ref(false)

// State Variablen
const date = ref('')
const startTime = ref('')
const endTime = ref('')
const course = ref('')
const selectedDepartment = ref<number | null>(null)

const departments = ref<Department[]>([])
// Beispiel-Optionen für die Combobox
const courseOptions = ['Training', 'Wettkampf', 'Vorstandssitzung', 'Pflegearbeiten']

// --- API: ABTEILUNGEN LADEN ---
async function fetchUserDepartments() {
  try {
    // KEIN manueller Header mehr nötig!
    const response = await axios.get('http://127.0.0.1:8000/api/meine-abteilungen')

    departments.value = response.data

    // Komfort: Bei nur einer Abteilung direkt auswählen
    if (departments.value.length === 1) {
      selectedDepartment.value = departments.value[0].id
    }

  } catch (error: any) {
    console.error('Fehler beim Laden der Abteilungen:', error)

    // Wenn 401 kommt, ist der globale Token abgelaufen/nicht gesetzt
    if (error.response && error.response.status === 401) {
      router.push({ name: 'Login' })
    }
  }
}

onMounted(() => {
  fetchUserDepartments()
})

// --- VALIDIERUNG ---
const requiredRule = [
  (v: any) => !!v || 'Dieses Feld ist erforderlich'
]

const endTimeRules = [
  (v: string) => !!v || 'Endzeit ist erforderlich',
  (v: string) => {
    if (!startTime.value) return true
    return v > startTime.value || 'Ende muss nach Beginn liegen'
  }
]

// --- SUBMIT ---
async function onSubmit() {
  const { valid } = await form.value?.validate() || { valid: false }
  if (!valid) return

  isLoading.value = true

  const payload = {
    datum: date.value,
    beginn: startTime.value,
    ende: endTime.value,
    kurs: course.value,
    fk_abteilung: selectedDepartment.value
  }

  try {
    // KEIN manueller Header mehr nötig!
    await axios.post('http://127.0.0.1:8000/api/stundeneintrag', payload)

    alert('Stundeneintrag erfolgreich gespeichert!')

    // Reset
    form.value?.reset()
    if (departments.value.length === 1) {
      selectedDepartment.value = departments.value[0].id
    }

  } catch (error: any) {
    console.error('API Error:', error)

    let errorMsg = 'Fehler beim Speichern.'

    if (error.response) {
      if (error.response.status === 401) {
        router.push({ name: 'Login' }) // Nicht eingeloggt
        return
      }
      if (error.response.status === 422) {
        errorMsg = 'Überprüfung fehlgeschlagen:\n' + JSON.stringify(error.response.data.errors, null, 2)
      }
      else if (error.response.data && error.response.data.message) {
        errorMsg = error.response.data.message
      }
    }
    alert(errorMsg)
  } finally {
    isLoading.value = false
  }
}

function goBack() {
  router.push({ name: 'Dashboard' })
}
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
    <v-card elevation="6" class="pa-4 auth-card">

      <v-progress-linear
          v-if="isLoading"
          indeterminate
          color="primary"
          absolute
          top
      ></v-progress-linear>

      <v-card-title class="pa-0 pb-4 d-flex align-center justify-space-between">
        <div>
          <h3 class="ma-0">Stundenerfassung</h3>
          <div class="caption">Zeit und Tätigkeit eintragen</div>
        </div>
        <v-btn
            icon="mdi-close"
            variant="text"
            density="comfortable"
            @click="goBack"
            title="Abbrechen"
        ></v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <v-form ref="form" @submit.prevent="onSubmit">

          <v-select
              v-model="selectedDepartment"
              :items="departments"
              item-title="name"
              item-value="id"
              label="Abteilung / Bereich"
              variant="outlined"
              density="comfortable"
              :rules="requiredRule"
              placeholder="Bitte wählen..."
              no-data-text="Keine Zuweisungen gefunden"
              class="mb-4"
          ></v-select>

          <v-text-field
              v-model="date"
              label="Datum"
              type="date"
              variant="outlined"
              density="comfortable"
              :rules="requiredRule"
              class="mb-4"
          ></v-text-field>

          <div class="d-flex" style="gap: 16px;">
            <v-text-field
                v-model="startTime"
                label="Beginn"
                type="time"
                variant="outlined"
                density="comfortable"
                :rules="requiredRule"
                class="mb-4 flex-grow-1"
            ></v-text-field>

            <v-text-field
                v-model="endTime"
                label="Ende"
                type="time"
                variant="outlined"
                density="comfortable"
                :rules="endTimeRules"
                class="mb-4 flex-grow-1"
            ></v-text-field>
          </div>

          <v-combobox
              v-model="course"
              :items="courseOptions"
              label="Kurs / Tätigkeit"
              variant="outlined"
              density="comfortable"
              placeholder="z.B. Training U14"
              class="mb-4"
              hint="Tippen oder Auswählen"
              persistent-hint
          ></v-combobox>

          <div class="d-flex justify-center mt-6" style="gap: 16px;">

            <v-btn
                variant="text"
                color="grey-darken-1"
                @click="goBack"
            >
              Abbrechen
            </v-btn>

            <v-btn
                color="primary"
                class="submit-btn"
                style="min-width:160px"
                type="submit"
                :loading="isLoading"
                :disabled="isLoading"
            >
              Speichern
            </v-btn>
          </div>

        </v-form>
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
</style>