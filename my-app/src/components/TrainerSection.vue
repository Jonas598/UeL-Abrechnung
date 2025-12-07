<script setup lang="ts">
// Eventuell Props übergeben, falls nötig
</script>

<template>
  <v-card color="blue-lighten-5" elevation="2" class="mb-4 border-s-lg-blue">
    <v-card-item>
      <template v-slot:prepend>
        <v-icon icon="mdi-whistle" color="blue-darken-2" size="large"></v-icon>
      </template>
      <v-card-title>Übungsleiter-Bereich</v-card-title>
      <v-card-subtitle>Zeiterfassung und Kurse</v-card-subtitle>
    </v-card-item>

    <v-card-text>
      <v-row>
        <v-col cols="12" sm="6">
          <v-btn
              block
              color="primary"
              prepend-icon="mdi-clock-plus-outline"
              :to="{ name: 'Timesheet' }"
          >
            Stunden erfassen
          </v-btn>
        </v-col>
        <v-col cols="12" sm="6">
          <v-btn block variant="outlined" color="primary">Meine Einträge ansehen</v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.border-s-lg-blue { border-left: 4px solid #1976D2; }
</style>