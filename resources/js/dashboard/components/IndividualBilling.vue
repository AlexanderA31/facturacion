<template>
  <div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Facturación Individual</h2>

      <!-- Establishment and Emission Point -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <BaseSelect
          id="establecimiento-select"
          label="Establecimiento"
          v-model="selectedEstablecimientoId"
          :options="establecimientoOptions"
          placeholder="Seleccione un establecimiento"
        />
        <BaseSelect
          id="punto-emision-select"
          label="Punto de Emisión"
          v-model="selectedPuntoEmisionId"
          :options="puntoEmisionOptions"
          :disabled="!selectedEstablecimientoId"
          placeholder="Seleccione un punto de emisión"
        />
      </div>

      <!-- Client Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <label for="ruc" class="block text-sm font-medium text-gray-700">RUC/CI</label>
          <input type="text" id="ruc" v-model="client.ruc" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Razón Social / Nombres</label>
          <input type="text" id="name" v-model="client.name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
          <input type="text" id="address" v-model="client.address" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" id="email" v-model="client.email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
      </div>

      <!-- Invoice Items -->
      <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Items de la Factura</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unitario</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descuento</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impuestos</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Eliminar</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(item, index) in items" :key="index">
                <td class="px-6 py-4 whitespace-nowrap">
                  <input type="text" v-model="item.description" class="w-full border-gray-300 rounded-md shadow-sm">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <input type="number" v-model.number="item.quantity" class="w-24 border-gray-300 rounded-md shadow-sm">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <input type="number" v-model.number="item.price" class="w-32 border-gray-300 rounded-md shadow-sm">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <input type="number" v-model.number="item.discount" class="w-24 border-gray-300 rounded-md shadow-sm">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select v-model="item.tax" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option v-for="tax in taxOptions" :key="tax.value" :value="tax.value">{{ tax.text }}</option>
                  </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ calculateItemTotal(item) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button @click="removeItem(index)" class="text-red-600 hover:text-red-900">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <button @click="addItem" class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          Agregar Item
        </button>
      </div>

      <!-- Totals -->
      <div class="flex justify-end mb-6">
        <div class="w-full max-w-sm">
          <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-600">Subtotal:</span>
            <span class="font-bold text-gray-800">{{ totals.subtotal }}</span>
          </div>
          <div class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-600">Descuento:</span>
            <span class="font-bold text-gray-800">{{ totals.discount }}</span>
          </div>
          <div v-for="(tax, code) in totals.iva" :key="code" class="flex justify-between py-2 border-b">
            <span class="font-medium text-gray-600">IVA ({{ getTarifaFromCodigoPorcentaje(code) }}%):</span>
            <span class="font-bold text-gray-800">{{ tax.valor.toFixed(2) }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-xl font-bold text-gray-800">Total:</span>
            <span class="text-xl font-bold text-gray-800">{{ totals.total }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end">
        <button @click="generateInvoice" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
          Generar Factura
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BaseSelect from './BaseSelect.vue';

export default {
  name: 'IndividualBilling',
  components: {
    BaseSelect,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      client: {
        ruc: '',
        name: '',
        address: '',
        email: ''
      },
      items: [
        {
          description: '',
          quantity: 1,
          price: 0,
          discount: 0,
          taxes: []
        }
      ],
      establecimientos: [],
      puntosEmision: [],
      selectedEstablecimientoId: null,
      selectedPuntoEmisionId: null,
      userProfile: {
        tipo_impuesto: '2',
        codigo_porcentaje_iva: '2',
      },
      taxOptions: [
        { value: '2', text: 'IVA 12%' },
        { value: '0', text: 'IVA 0%' },
        { value: '8', text: 'IVA 5%' },
      ]
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
    totals() {
      let subtotal = 0;
      let discount = 0;
      const iva = {};

      this.items.forEach(item => {
        const itemSubtotal = item.quantity * item.price;
        subtotal += itemSubtotal;
        discount += item.discount;
        const taxRate = this.getTarifaFromCodigoPorcentaje(item.tax) / 100;
        const taxValue = (itemSubtotal - item.discount) * taxRate;
        if (!iva[item.tax]) {
          iva[item.tax] = {
            base: 0,
            valor: 0,
          };
        }
        iva[item.tax].base += itemSubtotal - item.discount;
        iva[item.tax].valor += taxValue;
      });

      const totalIva = Object.values(iva).reduce((acc, tax) => acc + tax.valor, 0);

      return {
        subtotal: subtotal.toFixed(2),
        discount: discount.toFixed(2),
        iva: iva,
        total: (subtotal - discount + totalIva).toFixed(2),
      };
    }
  },
  watch: {
    selectedEstablecimientoId() {
      this.selectedPuntoEmisionId = null;
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchEstablecimientos();
    this.fetchPuntosEmision();
  },
  methods: {
    addItem() {
      this.items.push({
        description: '',
        quantity: 1,
        price: 0,
        discount: 0,
        tax: this.userProfile.codigo_porcentaje_iva
      });
    },
    removeItem(index) {
      this.items.splice(index, 1);
    },
    calculateItemTotal(item) {
      const subtotal = item.quantity * item.price;
      const total = subtotal - item.discount;
      const taxRate = this.getTarifaFromCodigoPorcentaje(item.tax) / 100;
      return (total * (1 + taxRate)).toFixed(2);
    },
    getTarifaFromCodigoPorcentaje(codigo) {
        const map = {
            '0': 0,
            '2': 12,
            '3': 14,
            '6': 0,
            '7': 0,
            '8': 5,
            '9': 15,
        };
        return map[codigo] || 0;
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api/profile', {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        this.userProfile = response.data.data;
        // Set default tax for new items
        this.items.forEach(item => {
          if (!item.tax) {
            item.tax = this.userProfile.codigo_porcentaje_iva;
          }
        });
      } catch (error) {
        console.error('Error fetching user profile:', error);
      }
    },
    async fetchEstablecimientos() {
      try {
        const response = await axios.get('/api/establecimientos', {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });
        this.establecimientos = response.data.data.data;
      } catch (error) {
        console.error('Error fetching establecimientos:', error);
      }
    },
    async fetchPuntosEmision() {
      try {
        const response = await axios.get('/api/puntos-emision', {
          headers: { 'Authorization': 'Bearer ' + this.token }
        });
        this.puntosEmision = response.data.data.data;
      } catch (error) {
        console.error('Error fetching puntos de emision:', error);
      }
    },
    async generateInvoice() {
      if (!this.selectedPuntoEmisionId) {
        this.$emitter.emit('show-alert', { type: 'error', message: 'Por favor, seleccione un punto de emisión.' });
        return;
      }

      const totalSinImpuestos = this.items.reduce((acc, item) => acc + (item.quantity * item.price) - item.discount, 0);

      const totalConImpuestos = Object.entries(this.totals.iva).map(([codigoPorcentaje, tax]) => ({
        codigo: this.userProfile.tipo_impuesto,
        codigoPorcentaje: codigoPorcentaje,
        baseImponible: tax.base.toFixed(2),
        valor: tax.valor.toFixed(2),
      }));

      const detalles = this.items.map(item => {
        const itemSubtotal = item.quantity * item.price;
        const taxRate = this.getTarifaFromCodigoPorcentaje(item.tax);
        const taxValue = (itemSubtotal - item.discount) * (taxRate / 100);
        return {
          codigoPrincipal: 'PROD' + Date.now(), // This should be improved
          descripcion: item.description,
          cantidad: item.quantity,
          precioUnitario: item.price,
          descuento: item.discount,
          precioTotalSinImpuesto: (itemSubtotal - item.discount).toFixed(2),
          impuestos: [{
            codigo: this.userProfile.tipo_impuesto,
            codigoPorcentaje: item.tax,
            tarifa: taxRate,
            baseImponible: (itemSubtotal - item.discount).toFixed(2),
            valor: taxValue.toFixed(2),
          }],
        };
      });

      const payload = {
        tipoIdentificacionComprador: String(this.client.ruc).length === 13 ? '04' : '05',
        razonSocialComprador: this.client.name,
        identificacionComprador: this.client.ruc,
        direccionComprador: this.client.address,
        totalSinImpuestos: totalSinImpuestos.toFixed(2),
        totalDescuento: this.totals.discount,
        totalConImpuestos: totalConImpuestos,
        importeTotal: this.totals.total,
        pagos: [{ formaPago: '01', total: this.totals.total }], // Assuming cash payment for now
        detalles: detalles,
        infoAdicional: { email: this.client.email },
      };

      try {
        await axios.post(`/api/comprobantes/factura/${this.selectedPuntoEmisionId}`, payload, {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });
        this.$emitter.emit('show-alert', { type: 'success', message: 'Factura generada exitosamente. Se está procesando.' });
        // Reset form
        this.client = { ruc: '', name: '', address: '', email: '' };
        this.items = [{ description: '', quantity: 1, price: 0, discount: 0, tax: this.userProfile.codigo_porcentaje_iva }];
      } catch (error) {
        console.error('Error generating invoice:', error);
        const errorMessage = error.response?.data?.message || 'Error al generar la factura.';
        this.$emitter.emit('show-alert', { type: 'error', message: errorMessage });
      }
    }
  }
};
</script>
