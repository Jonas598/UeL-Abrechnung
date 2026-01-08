<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import type { VForm } from 'vuetify/components'

const route = useRoute()
const router = useRouter()

const form = ref<VForm | null>(null)
const isLoading = ref(false)
const showPassword = ref(false)

// Daten aus der URL (Query Parameter)
const token = ref('')
const email = ref('')

// Formular-Daten
const password = ref('')
const passwordConfirmation = ref('')

// Regeln
const passwordRules = [
  (v: string) => !!v || 'Passwort ist erforderlich',
  (v: string) => v.length >= 8 || 'Mindestens 8 Zeichen',
]

const confirmationRules = [
  (v: string) => v === password.value || 'Passwörter stimmen nicht überein'
]

onMounted(() => {
  // Token und Email aus der URL holen
  token.value = route.query.token as string
  email.value = route.query.email as string

  if (!token.value || !email.value) {
    alert('Ungültiger Link! Token oder E-Mail fehlt.')
  }
})

async function onSubmit() {
  const { valid } = await form.value?.validate() || { valid: false }
  if (!valid) return

  isLoading.value = true

  try {
    // Anfrage an Laravel senden
    await axios.post(import.meta.env.VITE_API_URL + '/api/set-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })

    alert('Passwort erfolgreich gespeichert! Du kannst dich jetzt einloggen.')
    router.push('/login') // Weiterleitung zum Login

  } catch (error: any) {
    console.error(error)
    const msg = error.response?.data?.message || 'Fehler beim Speichern.'
    alert(msg)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <v-card elevation="6" class="pa-4 auth-card">
      <v-card-title class="text-center pb-4">
        <h3>Passwort festlegen</h3>
        <div class="text-caption">Für {{ email }}</div>
      </v-card-title>

      <v-card-text>
        <v-form ref="form" @submit.prevent="onSubmit">

          <!-- E-Mail (Readonly zur Info) -->
          <v-text-field
              v-model="email"
              label="E-Mail Adresse"
              prepend-inner-icon="mdi-email"
              variant="outlined"
              density="comfortable"
              disabled
              class="mb-2"
          ></v-text-field>

          <!-- Neues Passwort -->
          <v-text-field
              v-model="password"
              label="Neues Passwort"
              prepend-inner-icon="mdi-lock"
              :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
              :type="showPassword ? 'text' : 'password'"
              @click:append-inner="showPassword = !showPassword"
              :rules="passwordRules"
              variant="outlined"
              density="comfortable"
              class="mb-2"
          ></v-text-field>

          <!-- Passwort wiederholen -->
          <v-text-field
              v-model="passwordConfirmation"
              label="Passwort wiederholen"
              prepend-inner-icon="mdi-lock-check"
              type="password"
              :rules="confirmationRules"
              variant="outlined"
              density="comfortable"
          ></v-text-field>

          <v-btn
              color="primary"
              block
              size="large"
              type="submit"
              class="mt-4"
              :loading="isLoading"
          >
            Passwort speichern
          </v-btn>

        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  padding: 16px;
}
.auth-card {
  width: 100%;
  max-width: 400px;
  border-radius: 12px;
}
</style>