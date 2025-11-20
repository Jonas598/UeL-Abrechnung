<!-- File: `my-app/src/views/Login.vue` (script section only) -->
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const form = ref<any>(null)

const emailRules = [
  (v: string) => !!v?.trim() || 'Email is required',
  (v: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v?.trim()) || 'Enter a valid email'
]

async function onSubmit() {
  // Hier später: API mit email aufrufen (z.B. Magic Link / Code versenden)
  // placeholder: call API and store token
  router.push({ name: 'EnterPassword' })
}
</script>



<template>
  <div class="auth-page">
    <v-card elevation="6" class="pa-4 auth-card">
      <form @submit.prevent="onSubmit">
        <v-card-title class="pa-0 pb-4">
          <div>
            <h3 class="ma-0">Anmeldung</h3>
            <div class="caption">Bitte trage deine E-Mail ein:</div>
          </div>
        </v-card-title>

        <v-card-text class="pa-0">
          <v-text-field
              v-model="email"
              label="Email"
              type="email"
              :rules="emailRules"
              prepend-inner-icon="mdi-email"
              density="comfortable"
              autocomplete="email"
              placeholder="E-Mail"
              required
          />
        </v-card-text>

        <v-card-actions class="pa-0 mt-4 d-flex justify-center">
          <v-btn
              color="primary"
              class="mx-auto"
              style="min-width:160px"
              type="submit"
          >
            Weiter
          </v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </div>
</template>

<style scoped>
.auth-page {
  max-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  box-sizing: border-box;
  overflow: hidden;
}

.auth-card {
  width: 100%;
  max-width: 420px;
  border-radius: 12px;
}

.brand {
  font-size: 1.1rem;
  letter-spacing: 0.2px;
}
</style>
