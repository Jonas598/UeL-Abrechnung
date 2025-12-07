<script setup lang="ts">
// Keine Logik nötig, nur Anzeige
</script>

<template>
  <v-card color="red-lighten-5" elevation="2" class="mb-4 border-s-lg-red">
    <v-card-item>
      <template v-slot:prepend>
        <v-icon icon="mdi-shield-crown" color="red-darken-2" size="large"></v-icon>
      </template>
      <v-card-title>Admin-Bereich</v-card-title>
      <v-card-subtitle>Verwaltung und Systemeinstellungen</v-card-subtitle>
    </v-card-item>

    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-btn
              block
              color="red-darken-1"
              prepend-icon="mdi-account-plus"
              :to="{ name: 'CreateUser' }"
          >
            Benutzer anlegen
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.border-s-lg-red {
  border-left: 4px solid #D32F2F; /* Vuetify red-darken-2 */
}
</style>