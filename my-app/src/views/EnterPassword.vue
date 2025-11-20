<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const email = ref<string>((route.query.email as string) || '')
const password = ref('')
const form = ref<any>(null)
const passwordError = ref('')

// check for the required password
function isCorrectPassword(value: string) {
  return (value ?? '').trim() === '123456'
}

const passwordRules = [
  (v: string) => !!v || 'Bitte Passwort eintragen',
  (v: string) => isCorrectPassword(v) || 'Falsches Passwort'
]

onMounted(() => {
  console.log('enter password for', email.value)
})

async function submitPassword() {
  passwordError.value = ''

  if (form.value?.validate) {
    const valid = await Promise.resolve(form.value.validate())
    if (!valid) {
      // ensure the specific wrong-password message is shown when validation fails
      if (!isCorrectPassword(password.value)) passwordError.value = 'Falsches Passwort'
      return
    }
  } else {
    // fallback: manual checks and explicit wrong-password message
    const val = password.value ?? ''
    if (!val || val.length < 6) return
    if (!isCorrectPassword(val)) {
      passwordError.value = 'Falsches Passwort'
      return
    }
  }

  // only here when password === "123456"
  localStorage.setItem('auth_token', 'mock-token')
  router.push({ name: 'Dashboard' })
}
</script>

<template>
  <div class="auth-page">
    <v-card elevation="6" class="pa-4 auth-card">
      <form ref="form" lazy-validation>
        <v-card-title class="pa-0 pb-4">
          <div>
            <h3 class="ma-0">Anmeldung</h3>
            <div class="caption">Bitte trage dein Passwort ein:</div>
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
          />
        </v-card-text>

        <v-card-actions class="pa-0 mt-4 d-flex justify-center">
          <v-btn color="primary" class="mx-auto" style="min-width:160px" @click="submitPassword">
            Anmelden
          </v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </div>
</template>

<style scoped>
.auth-page { max-height: 100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
.auth-card { width:100%; max-width:420px; border-radius:12px; }
</style>
