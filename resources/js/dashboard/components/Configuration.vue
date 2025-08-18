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
                        <BaseSelect
                            id="ambiente-select"
                            label="Ambiente de Facturación"
                            v-model="form.ambiente"
                            :options="ambienteOptions"
                        />
                        <p class="mt-2 text-sm text-gray-500">Seleccione el ambiente para la emisión de comprobantes.</p>
                    </div>
                    <div>
                        <label for="send-email-toggle" class="block text-sm font-medium text-gray-700">Enviar Factura por Correo</label>
                        <button
                            type="button"
                            @click="form.enviar_factura_por_correo = !form.enviar_factura_por_correo"
                            :class="[form.enviar_factura_por_correo ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 mt-1']"
                            role="switch"
                            :aria-checked="form.enviar_factura_por_correo"
                        >
                            <span class="sr-only">Use setting</span>
                            <span
                                aria-hidden="true"
                                :class="[form.enviar_factura_por_correo ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"
                            ></span>
                        </button>
                        <p class="mt-2 text-sm text-gray-500">Si se activa, se enviará una copia de la factura al correo del cliente.</p>
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
        <EstablecimientosManager :is-sidebar-open="isSidebarOpen" />
      </div>
      <div v-if="activeTab === 'puntos_emision'">
        <PuntosEmisionManager :is-sidebar-open="isSidebarOpen" />
      </div>
    </div>
  </div>
</template>

<script>
import EstablecimientosManager from './EstablecimientosManager.vue';
import PuntosEmisionManager from './PuntosEmisionManager.vue';
import BaseSelect from './BaseSelect.vue';
import axios from 'axios';

export default {
  name: 'Configuration',
  props: {
    isSidebarOpen: {
      type: Boolean,
      default: false,
    }
  },
  components: {
    EstablecimientosManager,
    PuntosEmisionManager,
    BaseSelect,
  },
  data() {
    return {
      activeTab: 'general',
      form: {
        razonSocial: '',
        nombreComercial: '',
        dirMatriz: '',
        contribuyenteEspecial: '',
        obligadoContabilidad: false,
        ambiente: '1',
        enviar_factura_por_correo: true,
      },
      ambienteOptions: [
        { value: '1', text: 'Pruebas' },
        { value: '2', text: 'Producción' },
      ],
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
        // Assign all relevant profile data to the form
        const profile = response.data.data;
        this.form = {
            ...this.form, // Keep defaults for fields not in profile
            razonSocial: profile.razonSocial,
            nombreComercial: profile.nombreComercial,
            dirMatriz: profile.dirMatriz,
            contribuyenteEspecial: profile.contribuyenteEspecial,
            obligadoContabilidad: profile.obligadoContabilidad,
            ambiente: profile.ambiente,
            enviar_factura_por_correo: profile.enviar_factura_por_correo,
        };
      } catch (error) {
        console.error('Error al cargar la configuración:', error);
        this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo cargar la configuración del perfil.' });
      }
    },
    async saveConfiguration() {
      try {
        await axios.put('/api/profile', this.form, {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        this.$emitter.emit('show-alert', { type: 'success', message: 'Configuración guardada exitosamente.' });
      } catch (error) {
        console.error('Error al guardar la configuración:', error);
        if (error.response && error.response.status === 422) {
            this.$emitter.emit('show-alert', { type: 'error', message: 'Error de validación. Revise los datos e intente de nuevo.' });
        } else {
            this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo guardar la configuración.' });
        }
      }
    },
  },
};
</script>
