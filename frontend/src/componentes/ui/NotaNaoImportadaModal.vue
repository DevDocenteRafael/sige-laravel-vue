<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" data-testid="nota-nao-importada">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-lg p-6">
      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <FileText class="text-yellow-600 dark:text-yellow-400" :size="20" />
          <div>
            <h2 class="text-slate-900 dark:text-white font-bold">Nota ainda não cadastrada</h2>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Esta chave não corresponde a nenhuma nota importada</p>
          </div>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="emit('fechar')">
          <X :size="18" />
        </button>
      </div>

      <p class="font-mono text-xs break-all text-slate-600 dark:text-slate-300 mb-4" data-testid="chave-bipada">{{ chave }}</p>

      <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">O que a chave de acesso informa:</p>
      <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm mb-4">
        <dt class="text-slate-500 dark:text-slate-400">Número da NF</dt>
        <dd class="text-slate-900 dark:text-white">{{ dados.numero }}</dd>
        <dt class="text-slate-500 dark:text-slate-400">Série</dt>
        <dd class="text-slate-900 dark:text-white">{{ dados.serie }}</dd>
        <dt class="text-slate-500 dark:text-slate-400">Emissão (mês/ano)</dt>
        <dd class="text-slate-900 dark:text-white">{{ dados.emissao }}</dd>
        <dt class="text-slate-500 dark:text-slate-400">UF do emitente</dt>
        <dd class="text-slate-900 dark:text-white">{{ dados.uf || 'código ' + dados.codigoUf }}</dd>
        <dt class="text-slate-500 dark:text-slate-400">CNPJ do emitente</dt>
        <dd class="text-slate-900 dark:text-white">{{ dados.cnpjFormatado }}</dd>
      </dl>

      <div class="rounded-lg border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-3 mb-6 text-sm text-yellow-800 dark:text-yellow-200">
        O nome do fornecedor, os itens, as quantidades e os valores não estão na chave de acesso.
        Só o XML da nota traz esses dados, e eles são necessários para a conferência.
      </div>

      <p v-if="!podeImportar" class="text-sm text-slate-500 dark:text-slate-400 mb-4">
        Seu perfil não pode importar notas. Peça a um usuário com permissão para enviar o XML desta nota.
      </p>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          @click="emit('fechar')"
        >
          Fechar
        </button>
        <button
          v-if="podeImportar"
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium"
          data-testid="enviar-xml"
          @click="emit('enviar-xml')"
        >
          Enviar XML desta nota
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FileText, X } from 'lucide-vue-next'

defineProps({
  chave:       { type: String,  required: true },
  dados:       { type: Object,  required: true },
  podeImportar: { type: Boolean, default: false },
})

const emit = defineEmits(['enviar-xml', 'fechar'])
</script>