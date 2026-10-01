<template>
  <select
    :value="modelValue ?? ''"
    class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
    @change="$emit('update:modelValue', $event.target.value === '' ? null : Number($event.target.value))"
  >
    <option value="">{{ placeholder }}</option>
    <option v-for="c in centros" :key="c.id_centro_custo" :value="c.id_centro_custo">
      {{ c.codigo }} - {{ c.nome }}
    </option>
  </select>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCentrosCusto } from '@/composables/useCentrosCusto'

defineProps({
  modelValue: { type: [Number, null], default: null },
  placeholder: { type: String, default: 'Sem centro de custo' },
})
defineEmits(['update:modelValue'])

const { centros, carregar } = useCentrosCusto()
onMounted(carregar)
</script>