<template>
  <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">

    <div :class="`w-12 h-12 rounded-lg flex items-center justify-center text-2xl ${classesDeFundo[cor]}`">
      {{ icone }}
    </div>

    <div>
      <p class="text-2xl font-bold text-gray-800">{{ valor }}</p>
      <p class="text-sm text-gray-500">{{ titulo }}</p>
    </div>

  </div>
</template>

<script setup>
defineProps({
  titulo: { type: String, required: true },
  valor:  { type: Number, default: 0 },
  icone:  { type: String, default: '📦' },
  cor:    { type: String, default: 'blue' },
})

const classesDeFundo = {
  blue:   'bg-blue-50',
  green:  'bg-green-50',
  yellow: 'bg-yellow-50',
  red:    'bg-red-50',
}
</script>
