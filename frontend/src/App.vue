<template>
  <RouterView />

  <WidgetAcessibilidade />

  <ContainerNotificacoes />
</template>

<script setup>
import { RouterView } from 'vue-router'
import WidgetAcessibilidade from '@/componentes/ui/WidgetAcessibilidade.vue'
import ContainerNotificacoes from '@/componentes/ui/ContainerNotificacoes.vue'
</script>