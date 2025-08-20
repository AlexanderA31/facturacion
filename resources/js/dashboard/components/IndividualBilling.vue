<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Facturación Individual</h2>
    <p class="text-gray-600 mb-6">Complete el formulario para emitir una nueva factura.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Left Column: Invoice Details -->
      <div class="p-6 bg-gray-50 rounded-lg">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Detalles de la Factura</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <BaseSelect
              id="individual-establecimiento-select"
              label="Establecimiento"
              v-model="selectedEstablecimientoId"
              :options="establecimientoOptions"
              placeholder="Seleccione un establecimiento"
              required
            />
          </div>
          <div>
            <BaseSelect
              id="individual-punto-emision-select"
              label="Punto de Emisión"
              v-model="selectedPuntoEmisionId"
              :options="puntoEmisionOptions"
              :disabled="!selectedEstablecimientoId"
              placeholder="Seleccione un punto de emisión"
              required
            />
          </div>
          <div class="md:col-span-2">
            <BaseInput
              id="codigo"
              label="Código del Producto/Servicio"
              v-model="form.codigo"
              placeholder="Ej: PROD001"
              required
            />
          </div>
          <div class="md:col-span-2">
            <BaseInput
              id="evento"
              label="Descripción"
              v-model="form.evento"
              placeholder="Ej: Servicio de consultoría"
              required
            />
          </div>
           <div class="md:col-span-2">
            <BaseInput
              id="precio"
              label="Precio Total (incl. IVA)"
              type="number"
              v-model="form.precio"
              placeholder="0.00"
              step="0.01"
              required
            />
          </div>
        </div>
      </div>

      <!-- Right Column: Client Details -->
      <div class="p-6 bg-gray-50 rounded-lg">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Detalles del Cliente</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <BaseInput
              id="nombres"
              label="Nombres y Apellidos / Razón Social"
              v-model="form.nombres"
              placeholder="Ej: Juan Pérez"
              required
            />
          </div>
          <div>
            <BaseInput
              id="cedula"
              label="Cédula / RUC"
              v-model="form.cedula"
              placeholder="Ej: 1234567890"
              required
            />
          </div>
           <div>
            <BaseInput
              id="telefono"
              label="Teléfono"
              v-model="form.telefono"
              placeholder="Ej: 0987654321"
              required
            />
          </div>
          <div class="md:col-span-2">
            <BaseInput
              id="email"
              label="Correo Electrónico"
              type="email"
              v-model="form.email"
              placeholder="ej: cliente@dominio.com"
              required
            />
          </div>
          <div class="md:col-span-2">
            <BaseInput
              id="direccion"
              label="Dirección"
              v-model="form.direccion"
              placeholder="Ej: Av. Principal 123"
              required
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-8 flex justify-end">
      <BaseButton @click="submitInvoice" :disabled="isSubmitting" variant="primary">
        <template #icon v-if="!isSubmitting">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </template>
        <template #icon v-if="isSubmitting">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </template>
        {{ isSubmitting ? 'Enviando...' : 'Crear Factura' }}
      </BaseButton>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BaseInput from './BaseInput.vue';
import BaseButton from './BaseButton.vue';
import BaseSelect from './BaseSelect.vue';
import { parsePaymentMethods } from '../utils/paymentMethods.js';

export default {
  name: 'IndividualBilling',
  components: {
    BaseInput,
    BaseButton,
    BaseSelect,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
    establecimientos: {
      type: Array,
      required: true,
    },
    puntosEmision: {
        type: Array,
        required: true,
    }
  },
  data() {
    return {
      userProfile: {
        tipo_impuesto: '2',
        codigo_porcentaje_iva: '2',
      },
      form: {
        nombres: '',
        cedula: '',
        direccion: '',
        email: '',
        telefono: '',
        codigo: '',
        evento: '',
        precio: null,
      },
      selectedEstablecimientoId: null,
      selectedPuntoEmisionId: null,
      isSubmitting: false,
    };
  },
  computed: {
    establecimientoOptions() {
      return this.establecimientos.map(est => ({
        value: est.id,
        text: `${est.numero} - ${est.nombre}`,
      }));
    },
    puntoEmisionOptions() {
      if (!this.selectedEstablecimientoId) {
        return [];
      }
      return this.puntosEmision
        .filter(p => p.establecimiento_id == this.selectedEstablecimientoId)
        .map(pto => ({
          value: pto.id,
          text: `${pto.numero} - ${pto.nombre}`,
        }));
    },
  },
  watch: {
    selectedEstablecimientoId() {
        this.selectedPuntoEmisionId = null;
    }
  },
  mounted() {
    this.fetchUserProfile();
  },
  methods: {
    getTarifaFromCodigoPorcentaje(codigo) {
        const map = {
            '0': 0, '2': 12, '3': 14, '6': 0, '7': 0, '8': 5, '9': 15,
        };
        return map[codigo] || 0;
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api/profile', {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        this.userProfile = response.data.data;
      } catch (error) {
        console.error('Error fetching user profile:', error);
        this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo cargar el perfil de usuario.' });
      }
    },
    createInvoicePayload() {
      const { nombres, cedula, direccion, email, telefono, codigo, evento, precio } = this.form;
      const formatToString = (value, decimals = 2) => {
          const num = parseFloat(String(value).replace(',', '.'));
          return isNaN(num) ? "0.00" : num.toFixed(decimals);
      };

      if (!cedula || (String(cedula).length !== 10 && String(cedula).length !== 13)) {
        throw new Error('Cédula no válida. Debe tener 10 o 13 dígitos.');
      }
      let cleanedTelefono = String(telefono || '').replace(/\s+/g, '');
      if (cleanedTelefono.length === 9) cleanedTelefono = '0' + cleanedTelefono;
      if (cleanedTelefono.length !== 10) {
        throw new Error('Teléfono no válido. Debe tener 10 dígitos.');
      }
      if (!precio || precio <= 0) throw new Error('Precio no válido. Debe ser mayor a 0.');
      if (!nombres || !codigo || !evento || !email || !direccion) {
        throw new Error('Todos los campos son requeridos.');
      }

      const tarifa = this.getTarifaFromCodigoPorcentaje(this.userProfile.codigo_porcentaje_iva);
      const taxRate = 1 + (tarifa / 100);
      const totalSinImpuestos = parseFloat(precio) / taxRate;
      const iva = parseFloat(precio) - totalSinImpuestos;

      return {
        tipoIdentificacionComprador: String(cedula).length === 13 ? '04' : '05',
        razonSocialComprador: nombres,
        identificacionComprador: String(cedula),
        direccionComprador: direccion,
        totalSinImpuestos: formatToString(totalSinImpuestos),
        totalDescuento: "0.00",
        totalConImpuestos: [{
            codigo: this.userProfile.tipo_impuesto,
            codigoPorcentaje: this.userProfile.codigo_porcentaje_iva,
            baseImponible: formatToString(totalSinImpuestos),
            valor: formatToString(iva)
        }],
        importeTotal: formatToString(precio),
        pagos: [{ formaPago: "01", total: formatToString(precio) }], // Default to "SIN UTILIZACION DEL SISTEMA FINANCIERO"
        detalles: [{
          codigoPrincipal: String(codigo),
          descripcion: evento,
          cantidad: "1.00",
          precioUnitario: formatToString(totalSinImpuestos, 6),
          descuento: "0.00",
          precioTotalSinImpuesto: formatToString(totalSinImpuestos),
          impuestos: [{
                codigo: this.userProfile.tipo_impuesto,
                codigoPorcentaje: this.userProfile.codigo_porcentaje_iva,
                tarifa: tarifa,
                baseImponible: formatToString(totalSinImpuestos),
                valor: formatToString(iva)
            }],
        }],
        infoAdicional: { email: email, telefono: cleanedTelefono },
      };
    },
    async submitInvoice() {
      if (!this.selectedPuntoEmisionId) {
        this.$emitter.emit('show-alert', { type: 'error', message: 'Debe seleccionar un punto de emisión.' });
        return;
      }
      this.isSubmitting = true;
      try {
        const payload = this.createInvoicePayload();
        await axios.post(`/api/comprobantes/factura/${this.selectedPuntoEmisionId}`, payload, {
          headers: { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' },
        });
        this.$emitter.emit('show-alert', { type: 'success', message: '¡Factura enviada con éxito! Puede ver su estado en "Mis Comprobantes".' });
        this.resetForm();
      } catch (error) {
        const errorMessage = error.response?.data?.message || error.message;
        this.$emitter.emit('show-alert', { type: 'error', message: `Error al crear la factura: ${errorMessage}` });
      } finally {
        this.isSubmitting = false;
      }
    },
    resetForm() {
        this.form = {
            nombres: '',
            cedula: '',
            direccion: '',
            email: '',
            telefono: '',
            codigo: '',
            evento: '',
            precio: null,
        };
        // Do not reset establishment and emission point
    }
  },
};
</script>
