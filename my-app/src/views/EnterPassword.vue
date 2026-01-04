<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios' // Wir nutzen Axios direkt hier

const router = useRouter()
const route = useRoute()

// E-Mail aus der URL Query holen
const email = ref<string>((route.query.email as string) || '')
const password = ref('')
const form = ref<any>(null)
const passwordError = ref('')
const isLoading = ref(false)

// Validierung: Nur prüfen, ob etwas eingegeben wurde
const passwordRules = [
  (v: string) => !!v || 'Bitte Passwort eintragen',
]

onMounted(() => {
  // Falls keine E-Mail in der URL ist -> zurück zum Start
  if (!email.value) {
    router.push({ name: 'login' }) // Achte darauf, dass der Name in router/index.js stimmt
  }
})

async function submitPassword() {
  passwordError.value = ''

  // 1. UI Validierung (Ist das Feld leer?)
  if (form.value?.validate) {
    const result = await Promise.resolve(form.value.validate())
    // Vuetify 3 gibt oft ein Objekt zurück, wir prüfen sicherheitshalber beides
    const isValid = typeof result === 'boolean' ? result : result?.valid
    if (!isValid) return
  }

  isLoading.value = true

  try {
    // 2. Login-Anfrage direkt an Laravel senden
    // Passe die URL an, falls dein Server woanders läuft
    const response = await axios.post(import.meta.env.VITE_API_URL + '/api/login', {
      email: email.value,
      password: password.value
    })

    // 3. Token aus der Antwort holen
    const token = response.data.token

    // 4. Token im Browser speichern (localStorage)
    localStorage.setItem('auth_token', token)

    // 5. Token für zukünftige Anfragen als Standard setzen
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

    // 6. Weiterleitung ins Dashboard
    // WICHTIG: Der Name muss exakt so sein wie in router/index.js (Groß/Klein beachten!)
    router.push({ name: 'Dashboard' })

  } catch (error: any) {
    // 4. Fehlerbehandlung (z.B. falsches Passwort)
    if (error.response && error.response.status === 401) {
      passwordError.value = 'Falsches Passwort oder E-Mail.'
    } else {
      passwordError.value = 'Ein technischer Fehler ist aufgetreten.'
      console.error(error)
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <v-card elevation="6" class="pa-4 auth-card">
      <v-form ref="form" @submit.prevent="submitPassword">
        <v-card-title class="pa-0 pb-4">
          <div>
            <h3 class="ma-0">Anmeldung</h3>
            <div class="caption">
              Passwort für <strong>{{ email }}</strong> eingeben
            </div>
          </div>
        </v-card-title>

        <v-card-text class="pa-0">
          <v-text-field
              v-model="password"
              label="Passwort"
              type="password"
              :rules="passwordRules"
              :error="!!passwordError"
              :error-messages="passwordError ? [passwordError] : []"
              prepend-inner-icon="mdi-lock"
              density="comfortable"
              placeholder="Passwort"
              required
              autofocus
          />
        </v-card-text>

        <v-card-actions class="pa-0 mt-4 d-flex justify-center">
          <v-btn
              color="primary"
              class="mx-auto"
              style="min-width:160px"
              type="submit"
              :loading="isLoading"
          >
            Anmelden
          </v-btn>
        </v-card-actions>
      </v-form>
      <p>Wenn deine E-Mail-Adresse schon registriert wurde, hast du eine E-Mail zur Passwortfestlegung bekommen.<br>Schaue auch im Spam Ordner nach!</p>
    </v-card>
  </div>
</template>

<style scoped>
.auth-page { max-height: 100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
.auth-card { width:100%; max-width:420px; border-radius:12px; }
</style>