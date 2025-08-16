<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Configuración de Facturación</h2>
    <p class="text-gray-600 mb-6">Administre los establecimientos y puntos de emisión para su cuenta.</p>

    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'establecimientos'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'establecimientos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Establecimientos
            </a>
            <a href="#" @click.prevent="activeTab = 'puntos_emision'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'puntos_emision' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Puntos de Emisión
            </a>
        </nav>
    </div>

    <div>
      <div v-if="activeTab === 'establecimientos'">
        <EstablecimientosManager />
      </div>
      <div v-if="activeTab === 'puntos_emision'">
        <PuntosEmisionManager />
      </div>
    </div>
  </div>
</template>

<script>
import EstablecimientosManager from './EstablecimientosManager.vue';
import PuntosEmisionManager from './PuntosEmisionManager.vue';

export default {
  name: 'Configuration',
  components: {
    EstablecimientosManager,
    PuntosEmisionManager,
  },
  data() {
    return {
      activeTab: 'establecimientos', // Default tab
    };
  },
};
</script>
