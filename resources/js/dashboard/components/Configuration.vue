<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Configuración</h2>
    <p class="text-gray-600 mb-6">Administre la configuración general, establecimientos y puntos de emisión.</p>

    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'general'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'general' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                General
            </a>
            <a href="#" @click.prevent="activeTab = 'establecimientos'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'establecimientos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Establecimientos
            </a>
            <a href="#" @click.prevent="activeTab = 'puntos_emision'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'puntos_emision' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Puntos de Emisión
            </a>
        </nav>
    </div>

    <div>
      <div v-if="activeTab === 'general'">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración General</h3>
            <form @submit.prevent="saveConfiguration">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="ambiente-select" class="block text-sm font-medium text-gray-700">Ambiente de Facturación</label>
                        <select id="ambiente-select" v-model="form.ambiente" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="1">Pruebas</option>
                            <option value="2">Producción</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Seleccione el ambiente para la emisión de comprobantes.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
      </div>
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
import axios from 'axios';

export default {
  name: 'Configuration',
  components: {
    EstablecimientosManager,
    PuntosEmisionManager,
  },
  data() {
    return {
      activeTab: 'general',
      form: {
        ambiente: '1',
      },
      token: localStorage.getItem('jwt_token'),
    };
  },
  mounted() {
    this.loadConfiguration();
  },
  methods: {
    async loadConfiguration() {
      try {
        const response = await axios.get('/api/profile', {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        const profile = response.data.data;
        this.form.ambiente = profile.ambiente;
      } catch (error) {
        console.error('Error al cargar la configuración:', error);
        alert('No se pudo cargar la configuración del perfil.');
      }
    },
    async saveConfiguration() {
      try {
        await axios.put('/api/profile', this.form, {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        alert('Configuración guardada exitosamente.');
      } catch (error) {
        console.error('Error al guardar la configuración:', error);
        alert('No se pudo guardar la configuración.');
      }
    },
  },
};
</script>
