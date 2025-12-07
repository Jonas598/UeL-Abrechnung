<template>
  <v-card color="green-lighten-5" elevation="2" class="mb-4 border-s-lg-green">
    <v-card-item>
      <template v-slot:prepend>
        <v-icon icon="mdi-account-tie" color="green-darken-2" size="large"></v-icon>
      </template>
      <v-card-title>Abteilungsleitung</v-card-title>
      <v-card-subtitle>Übersicht und Freigaben</v-card-subtitle>
    </v-card-item>

    <v-card-text>
      <p class="text-body-2 mb-2">Du leitest folgende Abteilungen:</p>
      <v-btn block variant="tonal" color="green-darken-2" prepend-icon="mdi-check-all">
        Stunden genehmigen (Coming Soon)
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.border-s-lg-green { border-left: 4px solid #388E3C; }
</style>