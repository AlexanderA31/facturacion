<template>
  <div>
    <!-- Sidebar -->
    <div :class="['fixed inset-y-0 left-0 z-30 bg-gray-800 text-white transition-all duration-300 ease-in-out flex flex-col h-full', isSidebarOpen ? 'w-64' : 'w-20']">
      <div class="px-8 py-6 text-center overflow-hidden">
        <h2 class="text-2xl font-semibold whitespace-nowrap transition-all duration-300" :class="isSidebarOpen ? 'max-w-xs' : 'opacity-0 max-w-0'">{{ sidebarTitle }}</h2>
      </div>
      <nav class="flex-grow px-4 py-2 space-y-2">
        <a v-for="item in navigation" :key="item.name" href="#" @click.prevent="$emit('navigate', item.view)"
           :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentView === item.view ? 'bg-gray-700' : 'hover:bg-gray-700']">
          <component :is="item.icon" :class="['w-6 h-6 transition-all duration-300', isSidebarOpen ? 'mr-3' : 'mx-auto']" />
          <span class="whitespace-nowrap transition-all duration-300 overflow-hidden" :class="isSidebarOpen ? 'max-w-xs' : 'opacity-0 max-w-0'">{{ item.name }}</span>
        </a>
      </nav>
      <div class="px-4 py-4 mt-auto">
        <button @click="$emit('logout')" class="w-full flex items-center justify-center px-4 py-2 font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200">
          <svg :class="['w-5 h-5 transition-all duration-300', isSidebarOpen ? 'mr-2' : 'mx-auto']" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          <span class="whitespace-nowrap transition-all duration-300 overflow-hidden" :class="isSidebarOpen ? 'max-w-xs' : 'opacity-0 max-w-0'">Cerrar Sesión</span>
        </button>
      </div>
    </div>

    <div class="relative z-10 flex flex-col flex-1 transition-all duration-300 ease-in-out" :class="isSidebarOpen ? 'ml-64' : 'ml-20'">
      <!-- Header -->
      <header class="flex justify-between items-center p-4 bg-white border-b-2 border-gray-200">
        <button @click="$emit('toggle-sidebar')" class="text-gray-500 focus:outline-none">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="text-xl font-semibold">{{ headerTitle }}</h1>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 py-8">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppLayout',
  props: {
    navigation: {
      type: Array,
      required: true,
    },
    currentView: {
      type: String,
      required: true,
    },
    headerTitle: {
      type: String,
      default: 'Dashboard',
    },
    sidebarTitle: {
        type: String,
        default: 'Dashboard'
    },
    isSidebarOpen: {
        type: Boolean,
        required: true,
    }
  },
  emits: ['navigate', 'logout', 'toggle-sidebar'],
};
</script>
