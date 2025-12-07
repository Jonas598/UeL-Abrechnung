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

// --- API: ABTEILUNGEN LADEN ---
async function fetchUserDepartments() {
  try {
    // KEIN manueller Header mehr nötig!
    const response = await axios.get('http://127.0.0.1:8000/api/meine-uel-abteilungen')

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
async function onSubmit(targetStatusId: number) {

  const { valid } = await form.value?.validate() || { valid: false }
  if (!valid) return

  isLoading.value = true
  // const token ... (Global oder LocalStorage, wie du es hast)

  const payload = {
    datum: date.value,
    beginn: startTime.value,
    ende: endTime.value,
    kurs: course.value,
    fk_abteilung: selectedDepartment.value,

    // NEU: Wir schicken mit, welcher Button geklickt wurde
    status_id: targetStatusId
  }

  try {
    await axios.post('http://127.0.0.1:8000/api/stundeneintrag', payload) // Header global oder hier

    // Text anpassen je nach Aktion
    const msg = targetStatusId === 4 ? 'Als Entwurf gespeichert!' : 'Erfolgreich eingereicht!'
    alert(msg)

    // Reset Formular
    form.value?.reset()
    if (departments.value.length === 1) {
      selectedDepartment.value = departments.value[0].id
    }

  } catch (error: any) {
    // ... dein Error Handling bleibt gleich ...
    console.error(error)
    alert('Fehler beim Speichern.')
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
      </v-card-title>

      <v-card-text class="pa-0">
        <v-form ref="form" @submit.prevent="onSubmit">

          <v-select
              v-model="selectedDepartment"
              :items="departments"
              item-title="name"
              item-value="id"
              label="Abteilung"
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
              label="Kurs"
              variant="outlined"
              density="comfortable"
              placeholder="z.B. Training U14"
              class="mb-4"
          ></v-combobox>

          <div class="d-flex justify-center mt-6 flex-wrap" style="gap: 16px;">

            <v-btn
                color="blue-grey-lighten-4"
                variant="flat"
                class="submit-btn text-blue-grey-darken-4"
                style="min-width:140px"
                prepend-icon="mdi-content-save-outline"
                :loading="isLoading"
                :disabled="isLoading"
                @click="onSubmit(4)"
            >
              Entwurf
            </v-btn>

            <v-btn
                color="primary"
                class="submit-btn"
                style="min-width:140px"
                prepend-icon="mdi-send"
                :loading="isLoading"
                :disabled="isLoading"
                @click="onSubmit(2)"
            >
              Abschicken
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