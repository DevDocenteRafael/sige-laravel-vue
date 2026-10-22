<template>
  <div class="flex h-screen bg-white dark:bg-black">
    <!-- ===== SIDEBAR ===== -->
    <aside
      class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 border-r flex flex-col transition-all duration-300"
      :class="expandido ? 'w-64' : 'w-16'"
    >
      <!-- Logo -->
      <div
        class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between min-h-[65px]"
      >
        <div v-if="expandido" class="flex items-center gap-3 min-w-0">
          <img :src="logo" alt="Senac" class="h-8 shrink-0" />
          <div class="min-w-0">
            <h1 class="text-base font-bold leading-tight text-slate-900 dark:text-white">SIGE</h1>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">Controle de estoque</p>
          </div>
        </div>
        <button
          class="transition p-1 rounded-lg ml-auto shrink-0 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800"
          :title="expandido ? 'Recolher menu' : 'Expandir menu'"
          @click="expandido = !expandido"
        >
          <Menu v-if="!expandido" :size="20" />
          <X v-else :size="20" />
        </button>
      </div>

      <!-- Usuário: um único card; clicar abre o painel com e-mail, perfil e "Editar Perfil" -->
      <div v-if="expandido" ref="perfilRef" class="relative px-3 py-3">
        <button
          class="group flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800"
          :aria-expanded="perfilAberto"
          @click="perfilAberto = !perfilAberto"
        >
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-sm font-bold text-white"
          >
            <img
              v-if="usuario?.foto_url"
              :src="usuario.foto_url"
              class="h-full w-full object-cover"
            />
            <span v-else>{{ iniciaisDoUsuario }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
              {{ usuario?.name }}
            </p>
            <p class="truncate text-xs capitalize text-slate-500 dark:text-slate-400">
              {{ usuario?.perfil }}
            </p>
          </div>
          <ChevronsUpDown
            :size="16"
            class="shrink-0 text-slate-400 transition group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300"
          />
        </button>

        <!-- Painel com as informações do usuário -->
        <Transition
          enter-active-class="transition ease-out duration-150"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="perfilAberto"
            class="absolute left-3 right-3 top-full z-50 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900"
          >
            <div class="flex items-center gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-sm font-bold text-white"
              >
                <img
                  v-if="usuario?.foto_url"
                  :src="usuario.foto_url"
                  class="h-full w-full object-cover"
                />
                <span v-else>{{ iniciaisDoUsuario }}</span>
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                  {{ usuario?.name }}
                </p>
                <p class="text-xs capitalize text-blue-600 dark:text-blue-400">
                  {{ usuario?.perfil }}
                </p>
              </div>
            </div>

            <div class="space-y-2 p-4 text-sm">
              <div class="flex justify-between gap-3">
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                  <Mail :size="13" /> E-mail
                </span>
                <span class="truncate text-slate-900 dark:text-white">{{ usuario?.email }}</span>
              </div>
              <div class="flex justify-between gap-3">
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                  <ShieldCheck :size="13" /> Perfil
                </span>
                <span class="capitalize text-slate-900 dark:text-white">{{ usuario?.perfil }}</span>
              </div>
            </div>

            <div class="border-t border-slate-200 p-2 dark:border-slate-800">
              <RouterLink
                to="/perfil"
                class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="perfilAberto = false"
              >
                <Settings :size="14" />
                Editar Perfil
              </RouterLink>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Avatar colapsado -->
      <div v-else class="flex justify-center py-3">
        <div
          class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-xs font-bold text-white"
        >
          <img
            v-if="usuario?.foto_url"
            :src="usuario.foto_url"
            class="h-full w-full object-cover"
          />
          <span v-else>{{ iniciaisDoUsuario }}</span>
        </div>
      </div>

      <!-- Menu agrupado -->
      <nav class="flex-1 overflow-y-auto px-2 pb-2">
        <div
          v-for="(grupo, indice) in gruposDoMenu"
          :key="grupo.nome ?? 'principal'"
          :class="indice > 0 && (expandido ? 'mt-4' : 'mt-2')"
        >
          <p
            v-if="grupo.nome && expandido"
            class="mb-1 px-3 text-xs font-medium text-slate-400 dark:text-slate-500"
          >
            {{ grupo.nome }}
          </p>
          <div
            v-else-if="indice > 0 && !expandido"
            class="mx-3 mb-2 border-t border-slate-200 dark:border-slate-800"
          />

          <ul class="space-y-0.5">
            <li v-for="item in grupo.itens" :key="item.rota">
              <RouterLink
                :to="item.rota"
                :title="!expandido ? item.nome : ''"
                :class="[
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
                  'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                  !expandido && 'justify-center',
                ]"
                active-class="bg-blue-600 !text-white font-medium hover:bg-blue-600"
              >
                <component :is="item.icone" :size="18" class="shrink-0" />
                <span v-if="expandido">{{ item.nome }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Rodapé (Modo Claro e Atalhos ficam no botão flutuante) -->
      <div class="p-2 border-t border-slate-200 dark:border-slate-800 space-y-0.5">
        <!-- Notificações -->
        <button
          :title="!expandido ? 'Notificações' : ''"
          :class="[
            'relative w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition text-sm',
            'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
            !expandido && 'justify-center',
          ]"
          @click="abrirNotificacoes"
        >
          <div class="relative shrink-0">
            <Bell :size="18" />
            <span
              v-if="totalNaoLidas > 0"
              class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
              >{{ totalNaoLidas > 9 ? '9+' : totalNaoLidas }}</span
            >
          </div>
          <span v-if="expandido">Notificações</span>
        </button>

        <!-- Sair -->
        <button
          :title="!expandido ? 'Sair' : ''"
          :class="[
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 dark:hover:text-red-300 transition text-sm',
            !expandido && 'justify-center',
          ]"
          @click="sair"
        >
          <LogOut :size="18" class="shrink-0" />
          <span v-if="expandido">Sair</span>
        </button>
      </div>
    </aside>

    <!-- ===== PAINEL DE NOTIFICAÇÕES ===== -->
    <transition name="slide">
      <div
        v-if="painelAberto"
        class="fixed right-0 top-0 h-full w-96 border-l z-50 flex flex-col shadow-2xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800"
      >
        <div
          class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800"
        >
          <h2 class="font-bold text-base text-slate-900 dark:text-white">Notificações</h2>
          <button
            class="transition text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
            @click="painelAberto = false"
          >
            <X :size="18" />
          </button>
        </div>

        <div class="flex border-b border-slate-200 dark:border-slate-800">
          <button
            :class="[
              'flex-1 py-2.5 text-sm font-medium transition',
              aba === 'todas'
                ? 'bg-blue-600 text-white'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
            @click="aba = 'todas'"
          >
            Todas ({{ notificacoes.length }})
          </button>
          <button
            :class="[
              'flex-1 py-2.5 text-sm font-medium transition',
              aba === 'nao_lidas'
                ? 'bg-blue-600 text-white'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
            @click="aba = 'nao_lidas'"
          >
            Não Lidas ({{ totalNaoLidas }})
          </button>
        </div>

        <div class="flex-1 overflow-y-auto">
          <div
            v-if="carregando"
            class="flex items-center justify-center h-40 text-sm text-slate-500 dark:text-slate-400"
          >
            Carregando...
          </div>
          <div
            v-else-if="notificacoesFiltradas.length === 0"
            class="flex flex-col items-center justify-center h-64 gap-3"
          >
            <div
              class="w-12 h-12 rounded-full flex items-center justify-center bg-slate-100 dark:bg-slate-800"
            >
              <Info :size="22" class="text-slate-400 dark:text-slate-500" />
            </div>
            <p class="font-medium text-sm text-slate-900 dark:text-white">Nenhuma notificação</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">
              Você não tem notificações no momento
            </p>
          </div>
          <div v-else class="divide-y divide-slate-200 dark:divide-slate-800">
            <div
              v-for="notif in notificacoesFiltradas"
              :key="notif.id"
              :class="[
                'px-5 py-4 cursor-pointer transition hover:bg-slate-100 dark:hover:bg-slate-800/60',
                !notif.lida && 'border-l-2 border-blue-500',
              ]"
              @click="marcarLida(notif)"
            >
              <div class="flex items-start gap-3">
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5',
                    notif.tipo === 'vencimento'
                      ? 'bg-orange-100 dark:bg-orange-900/40'
                      : 'bg-red-100 dark:bg-red-900/40',
                  ]"
                >
                  <AlertTriangle
                    :size="16"
                    :class="
                      notif.tipo === 'vencimento'
                        ? 'text-orange-600 dark:text-orange-400'
                        : 'text-red-600 dark:text-red-400'
                    "
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium leading-snug text-slate-900 dark:text-white">
                    {{ notif.titulo }}
                  </p>
                  <p class="text-xs mt-0.5 leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ notif.descricao }}
                  </p>
                  <p class="text-xs mt-1.5 text-slate-400 dark:text-slate-600">{{ notif.tempo }}</p>
                </div>
                <div
                  v-if="!notif.lida"
                  class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-2"
                ></div>

                <!-- Apagar notificação -->
                <button
                  title="Apagar notificação"
                  class="shrink-0 p-1 rounded-lg transition text-slate-400 dark:text-slate-500 hover:text-red-500 dark:hover:text-red-400 hover:bg-slate-200 dark:hover:bg-slate-700"
                  @click.stop="apagarNotificacao(notif)"
                >
                  <Trash2 :size="14" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="notificacoes.length > 0"
          class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <button
            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition"
            @click="marcarTodasLidas"
          >
            Marcar todas como lidas
          </button>
          <button
            class="text-xs text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 transition"
            @click="apagarTodas"
          >
            Apagar todas
          </button>
        </div>
      </div>
    </transition>

    <div v-if="painelAberto" class="fixed inset-0 z-40" @click="painelAberto = false" />

    <!-- ===== PAINEL DE ATALHOS ===== -->
    <transition name="slide">
      <div
        v-if="painelAtalhosAberto"
        class="fixed right-0 top-0 h-full w-96 border-l z-50 flex flex-col shadow-2xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800"
      >
        <div
          class="flex items-start justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-lg flex items-center justify-center bg-blue-100 dark:bg-blue-600/20"
            >
              <Keyboard :size="18" class="text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h2 class="font-bold text-base text-slate-900 dark:text-white">Atalhos de Teclado</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Acelere seu trabalho com atalhos de teclado
              </p>
            </div>
          </div>
          <button
            class="transition mt-1 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
            @click="painelAtalhosAberto = false"
          >
            <X :size="18" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-5">
          <div v-for="(grupo, label) in gruposAtalhos" :key="label">
            <p
              class="text-xs font-semibold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500"
            >
              {{ label }}
            </p>
            <div class="space-y-1">
              <div
                v-for="atalho in grupo"
                :key="atalho.acao"
                class="flex items-center justify-between rounded-lg px-3 py-2.5 bg-slate-100 dark:bg-slate-800"
              >
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ atalho.acao }}</span>
                <div class="flex items-center gap-1">
                  <kbd
                    v-for="tecla in atalho.teclas"
                    :key="tecla"
                    class="px-2 py-0.5 border rounded text-xs font-mono bg-slate-200 border-slate-300 text-slate-900 dark:bg-slate-700 dark:border-slate-600 dark:text-white"
                    >{{ tecla }}</kbd
                  >
                </div>
              </div>
            </div>
          </div>

          <div
            class="border rounded-lg px-4 py-3 bg-blue-100 dark:bg-blue-600/20 border-blue-300 dark:border-blue-600/40"
          >
            <div class="flex items-center gap-2 mb-1">
              <span class="text-base">💡</span>
              <span class="text-blue-600 dark:text-blue-400 text-sm font-semibold"
                >Dica Profissional</span
              >
            </div>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">
              Use
              <kbd
                class="px-1.5 py-0.5 border rounded text-xs font-mono bg-slate-200 border-slate-300 text-slate-900 dark:bg-slate-700 dark:border-slate-600 dark:text-white"
                >Ctrl+K</kbd
              >
              para acessar a busca global rapidamente de qualquer lugar do sistema.
            </p>
          </div>
        </div>

        <div
          class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <span class="text-xs text-slate-400 dark:text-slate-500"
            >{{ totalAtalhos }} atalhos disponíveis</span
          >
          <div class="flex items-center gap-2">
            <kbd
              class="px-2 py-0.5 border rounded text-xs font-mono bg-slate-200 border-slate-300 text-slate-900 dark:bg-slate-700 dark:border-slate-600 dark:text-white"
              >ESC</kbd
            >
            <button
              class="text-sm transition text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white"
              @click="painelAtalhosAberto = false"
            >
              Fechar
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div
      v-if="painelAtalhosAberto"
      class="fixed inset-0 z-40"
      @click="painelAtalhosAberto = false"
    />

    <!-- ===== CONTEÚDO ===== -->
    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>
    <GlobalBusca
      :is-open="buscaGlobalAberta"
      :itens-menu="itensDoMenu"
      @fechar="buscaGlobalAberta = false"
    />

    <ChatbotWidget />
    <MenuSuporte @abrir-atalhos="painelAtalhosAberto = true" />
  </div>
</template>

<script setup>
import ChatbotWidget from '@/componentes/ui/ChatbotWidget.vue';
import MenuSuporte from '@/componentes/ui/MenuSuporte.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import {
  LayoutDashboard,
  PackagePlus,
  ClipboardCheck,
  Package,
  Trash2,
  History,
  FileText,
  BarChart3,
  Download,
  Shield,
  ShieldCheck,
  Users,
  Building2,
  Settings,
  LogOut,
  Menu,
  X,
  Bell,
  Keyboard,
  AlertTriangle,
  Info,
  Mail,
  ChevronsUpDown,
} from 'lucide-vue-next';
import { useAutenticacaoStore } from '@/servicos/autenticacao.store';
import { perfilPodeAcessarRota } from '@/servicos/permissoes';
import { useAlertaVencimento } from '@/composables/useAlertaVencimento';
import logoSenac from '@/componentes/img/Senac_logo.svg.png';
import api from '@/servicos/api';
import GlobalBusca from '@/componentes/ui/GlobalBusca.vue';

const buscaGlobalAberta = ref(false);
const router = useRouter();
const autenticacao = useAutenticacaoStore();
const { dias: diasAlerta } = useAlertaVencimento();
const logo = logoSenac;
const expandido = ref(true);
const painelAberto = ref(false);
const painelAtalhosAberto = ref(false);
const perfilAberto = ref(false);
const perfilRef = ref(null);
const aba = ref('todas');
const carregando = ref(false);
const notificacoes = ref([]);

const usuario = computed(() => autenticacao.usuario);

const iniciaisDoUsuario = computed(() => {
  if (!usuario.value?.name) return '?';
  return usuario.value.name
    .split(' ')
    .slice(0, 2)
    .map((p) => p[0])
    .join('')
    .toUpperCase();
});

const itensDoMenu = computed(() => {
  // "grupo" só organiza a sidebar; a lista continua plana para a busca global
  const menus = [
    { nome: 'Dashboard', rota: '/dashboard', nomeRota: 'dashboard', icone: LayoutDashboard, grupo: null },
    { nome: 'Lotes', rota: '/lotes', nomeRota: 'lotes', icone: PackagePlus, grupo: 'Estoque' },
    { nome: 'Recebimentos', rota: '/recebimentos', nomeRota: 'recebimentos', icone: ClipboardCheck, grupo: 'Estoque' },
    { nome: 'Produtos', rota: '/produtos', nomeRota: 'produtos', icone: Package, grupo: 'Estoque' },
    { nome: 'Perdas', rota: '/perdas', nomeRota: 'perdas', icone: Trash2, grupo: 'Estoque' },
    { nome: 'Centros de Custo', rota: '/centros-custo', nomeRota: 'centros-custo', icone: Building2, grupo: 'Estoque' },
    { nome: 'Histórico', rota: '/historico', nomeRota: 'historico', icone: History, grupo: 'Análises' },
    { nome: 'Relatórios', rota: '/relatorios', nomeRota: 'relatorios', icone: FileText, grupo: 'Análises' },
    { nome: 'Rel. Avançados', rota: '/rel-avancados', nomeRota: 'rel-avancados', icone: BarChart3, grupo: 'Análises' },
    { nome: 'Import/Export', rota: '/importexport', nomeRota: 'importexport', icone: Download, grupo: 'Sistema' },
  ];

  const menusFiltrados = menus.filter((item) =>
    perfilPodeAcessarRota(autenticacao.perfil, item.nomeRota)
  );

  if (autenticacao.ehAdmin) {
    menusFiltrados.push({
      nome: 'Log',
      rota: '/log',
      nomeRota: 'log',
      icone: Shield,
      grupo: 'Sistema',
    });
    menusFiltrados.push({
      nome: 'Usuários',
      rota: '/usuarios',
      nomeRota: 'usuarios',
      icone: Users,
      grupo: 'Sistema',
    });
  }

  return menusFiltrados;
});

// Agrupa os itens já filtrados por permissão; grupos vazios nem aparecem
const gruposDoMenu = computed(() => {
  const grupos = [];
  itensDoMenu.value.forEach((item) => {
    let grupo = grupos.find((g) => g.nome === item.grupo);
    if (!grupo) {
      grupo = { nome: item.grupo, itens: [] };
      grupos.push(grupo);
    }
    grupo.itens.push(item);
  });
  return grupos;
});

// ===== ATALHOS =====
const atalhosBusca = [{ acao: 'Abrir busca global', teclas: ['Ctrl+K'] }];
const atalhoNavegacao = [
  { acao: 'Ir para Dashboard', teclas: ['Alt+D'] },
  { acao: 'Ir para Lotes', teclas: ['Alt+L'] },
  { acao: 'Ir para Produtos', teclas: ['Alt+P'] },
  { acao: 'Ir para Histórico', teclas: ['Alt+H'] },
  { acao: 'Ir para Relatórios Avançados', teclas: ['Alt+R'] },
  { acao: 'Ir para Log', teclas: ['Alt+A'] },
  { acao: 'Abrir notificações', teclas: ['Alt+N'] },
];
const atalhosAcoes = [{ acao: 'Mostrar atalhos de teclado', teclas: ['Shift+?'] }];

const gruposAtalhos = computed(() => ({
  Busca: atalhosBusca,
  Navegação: atalhoNavegacao,
  'Ações Rápidas': atalhosAcoes,
}));

const totalAtalhos = computed(
  () => atalhosBusca.length + atalhoNavegacao.length + atalhosAcoes.length
);

// ===== NOTIFICAÇÕES =====
const notificacoesFiltradas = computed(() =>
  aba.value === 'nao_lidas' ? notificacoes.value.filter((n) => !n.lida) : notificacoes.value
);
const totalNaoLidas = computed(() => notificacoes.value.filter((n) => !n.lida).length);

// IDs das notificações apagadas ficam salvos no navegador, porque a lista
// é recalculada a partir de /produtos toda vez que o painel abre.
const CHAVE_APAGADAS = 'sige:notificacoes-apagadas';

function lerApagadas() {
  try {
    return new Set(JSON.parse(localStorage.getItem(CHAVE_APAGADAS) || '[]'));
  } catch {
    return new Set();
  }
}
function salvarApagadas(set) {
  try {
    localStorage.setItem(CHAVE_APAGADAS, JSON.stringify([...set]));
  } catch (e) {
    console.error('Erro ao salvar notificações apagadas', e);
  }
}
const apagadas = lerApagadas();

function apagarNotificacao(notif) {
  apagadas.add(notif.id);
  salvarApagadas(apagadas);
  notificacoes.value = notificacoes.value.filter((n) => n.id !== notif.id);
}

function apagarTodas() {
  // Apaga TODAS as notificações, seja qual for a aba aberta
  notificacoes.value.forEach((n) => apagadas.add(n.id));
  salvarApagadas(apagadas);
  notificacoes.value = [];
  aba.value = 'todas';
}

// Lê "YYYY-MM-DD" como data local (new Date("YYYY-MM-DD") interpreta como UTC
// e, no Brasil, voltaria um dia).
function paraDataLocal(dataString) {
  const [ano, mes, dia] = String(dataString).split('T')[0].split('-').map(Number);
  return new Date(ano, mes - 1, dia);
}

function diasParaVencer(dataValidade) {
  if (!dataValidade) return null;
  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);
  const venc = paraDataLocal(dataValidade);
  return Math.ceil((venc - hoje) / (1000 * 60 * 60 * 24));
}

function tempoRelativo(dataValidade) {
  const dias = diasParaVencer(dataValidade);
  if (dias === null) return '';
  if (dias < 0) return `Vencido há ${Math.abs(dias)} dia(s)`;
  if (dias === 0) return 'Vence hoje';
  return `Vence em ${dias} dia(s)`;
}

async function carregarNotificacoes() {
  carregando.value = true;
  try {
    const { data } = await api.get('/produtos');
    const lista = [];
    const produtos = Array.isArray(data) ? data : (data.data ?? []);
    produtos.forEach((item) => {
      if (item.data_validade) {
        const dias = diasParaVencer(item.data_validade);
        if (dias !== null && dias <= diasAlerta.value) {
          lista.push({
            // A validade entra no ID: se a data do produto mudar, a notificação volta
            id: `venc-${item.id_produto}-${item.data_validade}`,
            tipo: 'vencimento',
            titulo:
              dias < 0
                ? `${item.nome} está vencido`
                : dias === 0
                  ? `${item.nome} vence hoje`
                  : `${item.nome} vence em breve`,
            descricao: `${tempoRelativo(item.data_validade)} · Estoque: ${item.quantidade} ${item.unidade_medida}`,
            tempo: tempoRelativo(item.data_validade),
            lida: false,
          });
        }
      }
      if (item.estoque_minimo && item.quantidade <= item.estoque_minimo) {
        lista.push({
          id: `estoque-${item.id_produto}`,
          tipo: 'estoque',
          titulo: `Estoque baixo: ${item.nome}`,
          descricao: `Quantidade atual (${item.quantidade} ${item.unidade_medida}) atingiu o mínimo de ${item.estoque_minimo}`,
          tempo: 'Agora',
          lida: false,
        });
      }
    });
    notificacoes.value = lista.filter((n) => !apagadas.has(n.id));
  } catch (e) {
    console.error('Erro ao carregar notificações', e);
  } finally {
    carregando.value = false;
  }
}

async function abrirNotificacoes() {
  painelAberto.value = true;
  aba.value = 'todas';
  await carregarNotificacoes();
}

function marcarLida(notif) {
  notif.lida = true;
}
function marcarTodasLidas() {
  notificacoes.value.forEach((n) => (n.lida = true));
}

// Se a janela de alerta mudar (30/60/90/120/personalizado), refaz a lista
watch(diasAlerta, carregarNotificacoes);

async function sair() {
  await autenticacao.fazerLogout();
  router.push('/login');
}

function irPara(nomeRota, caminho) {
  if (!perfilPodeAcessarRota(autenticacao.perfil, nomeRota)) return;
  router.push(caminho);
}

function fecharPerfilAoClicarFora(e) {
  if (perfilRef.value && !perfilRef.value.contains(e.target)) {
    perfilAberto.value = false;
  }
}

function handleKeyboard(e) {
  if (!e.key) return; // ← proteção contra e.key undefined
  const alt = e.altKey;
  const shift = e.shiftKey;
  const key = e.key.toLowerCase();

  if (e.ctrlKey && key === 'k') {
    e.preventDefault();
    buscaGlobalAberta.value = true;
  }
  if (alt && key === 'd') {
    e.preventDefault();
    irPara('dashboard', '/dashboard');
  }
  if (alt && key === 'l') {
    e.preventDefault();
    irPara('lotes', '/lotes');
  }
  if (alt && key === 'p') {
    e.preventDefault();
    irPara('produtos', '/produtos');
  }
  if (alt && key === 'h') {
    e.preventDefault();
    irPara('historico', '/historico');
  }
  if (alt && key === 'r') {
    e.preventDefault();
    irPara('rel-avancados', '/rel-avancados');
  }
  if (alt && key === 'a') {
    e.preventDefault();
    irPara('log', '/log');
  }
  if (alt && key === 'n') {
    e.preventDefault();
    abrirNotificacoes();
  }
  if (shift && e.key === '?') {
    e.preventDefault();
    painelAtalhosAberto.value = true;
  }
  if (e.key === 'Escape') {
    painelAberto.value = false;
    painelAtalhosAberto.value = false;
    buscaGlobalAberta.value = false;
    perfilAberto.value = false;
  }
}

onMounted(() => {
  carregarNotificacoes();
  window.addEventListener('keydown', handleKeyboard);
  document.addEventListener('click', fecharPerfilAoClicarFora);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboard);
  document.removeEventListener('click', fecharPerfilAoClicarFora);
});
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>