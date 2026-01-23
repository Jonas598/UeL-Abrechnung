<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import logo from '../assets/ac-weinheim.png'

const router = useRouter()

function goHome() {
  router.push({ name: 'Dashboard' })
}
</script>

<template>
  <v-app>
    <v-app-bar app color="white" height="64" elevation="1">

      <div
          class="d-flex align-center cursor-pointer ml-4"
          @click="goHome"
          title="Zum Dashboard"
      >
        <v-img
            :src="logo"
            alt="AC Weinheim"
            height="44"
            width="44"
            class="rounded mr-4"
        />

        <div class="text-h6 text-primary font-weight-medium text-no-wrap">
          ÜL Abrechnung AC Weinheim
        </div>
      </div>

      <v-spacer></v-spacer>

    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-4" style="max-width: 1200px; margin: 0 auto">
        <RouterView />
      </v-container>
    </v-main>

    <v-footer app height="56" class="pa-2">
      <v-row justify="center" align="center" style="width: 100%">
        <span class="text-subtitle-2" style="color: rgba(107, 114, 128, 1)">
          Programmiert in Kooperation mit der FH Aachen
        </span>
      </v-row>
    </v-footer>
  </v-app>
</template>

<style>
.cursor-pointer {
  cursor: pointer;
  user-select: none;
}

.rounded {
  border-radius: 6px;
  background: white;
  padding: 4px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
</style>