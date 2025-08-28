<template>
  <div class="bg-gray-100 p-8 font-sans text-xs">
    <div class="max-w-4xl mx-auto bg-white shadow-lg p-8 border border-gray-200">

      <!-- Header -->
      <div class="flex justify-between items-start mb-4 pb-4 border-b">
        <!-- Emitter Info -->
        <div class="w-1/2 pr-4">
          <img v-if="userProfile.logo_path" :src="`/storage/${userProfile.logo_path}`" alt="Logo" class="max-w-xs max-h-20 mb-4">
          <p><span class="font-bold">Emisor:</span> {{ userProfile.name }}</p>
          <p><span class="font-bold">RUC:</span> {{ userProfile.ruc }}</p>
          <p><span class="font-bold">Matriz:</span> {{ userProfile.direccion }}</p>
          <p><span class="font-bold">Obligado a llevar contabilidad:</span> {{ userProfile.obligado_contabilidad ? 'SI' : 'NO' }}</p>
        </div>

        <!-- Invoice Info -->
        <div class="w-1/2 pl-4 border-l">
          <div class="border border-gray-300 rounded-lg p-4">
            <p class="text-lg font-bold">FACTURA</p>
            <p class="text-red-600 font-bold text-lg">{{ getEstablecimientoCode() }}-{{ getPuntoEmisionCode() }}-{{ proximoSecuencial }}</p>
            <p class="font-bold mt-4">Número de Autorización:</p>
            <p class="text-xs break-all">--</p>
            <p><span class="font-bold">Ambiente:</span> {{ userProfile.ambiente == '1' ? 'PRUEBAS' : 'PRODUCCIÓN' }}</p>
            <p><span class="font-bold">Emisión:</span> NORMAL</p>
          </div>
        </div>
      </div>

      <!-- Client Information -->
      <div class="mb-4 pb-4 border-b bg-gray-50 p-4 rounded-lg border">
        <div class="flex justify-between">
          <div class="w-1/2 pr-4">
            <p><span class="font-bold">Razón Social:</span> <input type="text" v-model="client.name" class="form-input-pdf-inline"></p>
            <p><span class="font-bold">RUC/CI:</span> <input type="text" v-model="client.ruc" class="form-input-pdf-inline"></p>
            <p><span class="font-bold">Dirección:</span> <input type="text" v-model="client.address" class="form-input-pdf-inline"></p>
          </div>
          <div class="w-1/2 pl-4">
            <p><span class="font-bold">Fecha Emisión:</span> <input type="date" v-model="client.fechaEmision" class="form-input-pdf-inline"></p>
            <p><span class="font-bold">Correo:</span> <input type="email" v-model="client.email" class="form-input-pdf-inline"></p>
            <p><span class="font-bold">Teléfono:</span> <input type="tel" v-model="client.telefono" class="form-input-pdf-inline"></p>
          </div>
        </div>
      </div>

      <!-- Invoice Items -->
      <div class="mb-4">
        <table class="w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-2 py-2 text-left font-bold border">Código</th>
              <th class="px-2 py-2 text-left font-bold border w-2/5">Descripción</th>
              <th class="px-2 py-2 text-left font-bold border">Cantidad</th>
              <th class="px-2 py-2 text-left font-bold border">P. Unitario</th>
              <th class="px-2 py-2 text-left font-bold border">Descuento</th>
              <th class="px-2 py-2 text-left font-bold border">Total</th>
              <th class="px-2 py-2 text-left font-bold border"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in items" :key="index">
              <td class="border px-2 py-1"><input type="text" v-model="item.codigoPrincipal" class="w-full form-input-table"></td>
              <td class="border px-2 py-1 relative">
                <input type="text" v-model="item.description" @input="searchProducts(index)" @focus="activeAutocomplete = index" class="w-full form-input-table">
                <div v-if="activeAutocomplete === index && filteredProducts.length" class="absolute z-10 w-full bg-white border rounded shadow-lg mt-1">
                  <ul><li v-for="p in filteredProducts" :key="p.id" @click="selectProduct(index, p)" class="px-3 py-2 cursor-pointer hover:bg-gray-100">{{ p.description }}</li></ul>
                </div>
              </td>
              <td class="border px-2 py-1"><input type="number" v-model.number="item.quantity" class="w-20 form-input-table text-right"></td>
              <td class="border px-2 py-1"><input type="number" v-model.number="item.price" class="w-24 form-input-table text-right"></td>
              <td class="border px-2 py-1"><input type="number" v-model.number="item.discount" class="w-20 form-input-table text-right"></td>
              <td class="border px-2 py-1 text-right">{{ calculateItemTotal(item) }}</td>
              <td class="border px-2 py-1 text-center"><button @click="removeItem(index)" class="text-red-500">&times;</button></td>
            </tr>
          </tbody>
        </table>
        <button @click="addItem" class="mt-2 text-sm text-indigo-600 font-bold">+ Agregar Item</button>
      </div>

      <!-- Footer -->
      <div class="flex justify-between mt-4">
        <!-- Additional Info & Payment -->
        <div class="w-1/2 pr-4">
          <div class="border rounded-lg p-4 bg-gray-50">
            <p class="font-bold">Información Adicional</p>
            <div v-for="(info, index) in additionalInfo" :key="index" class="flex items-center">
              <input type="text" v-model="info.name" class="form-input-pdf-inline w-1/3" placeholder="Nombre">
              <input type="text" v-model="info.value" class="form-input-pdf-inline w-2/3" placeholder="Valor">
              <button @click="removeAdditionalInfo(index)" class="text-red-500 ml-2">&times;</button>
            </div>
            <button @click="addAdditionalInfo" class="mt-2 text-sm text-indigo-600 font-bold">+ Agregar Info</button>
          </div>
          <div class="border rounded-lg p-4 bg-gray-50 mt-4">
            <p class="font-bold">Formas de Pago</p>
            <BaseSelect v-model="selectedPaymentMethod" :options="paymentMethodOptions" class="w-full mt-2"/>
          </div>
        </div>

        <!-- Totals -->
        <div class="w-2/5">
          <table class="w-full border">
            <tr v-for="(tax, code) in totals.iva" :key="code">
              <td class="px-2 py-1 border font-bold">Subtotal {{ getTarifaFromCodigoPorcentaje(code) }}%</td>
              <td class="px-2 py-1 border text-right">${{ tax.base.toFixed(2) }}</td>
            </tr>
            <tr><td class="px-2 py-1 border font-bold">Descuento</td><td class="px-2 py-1 border text-right">${{ totals.discount }}</td></tr>
            <tr v-for="(tax, code) in totals.iva" :key="code">
              <td class="px-2 py-1 border font-bold">IVA {{ getTarifaFromCodigoPorcentaje(code) }}%</td>
              <td class="px-2 py-1 border text-right">${{ tax.valor.toFixed(2) }}</td>
            </tr>
            <tr class="bg-gray-100"><td class="px-2 py-2 border font-bold text-lg">Valor Total</td><td class="px-2 py-2 border text-right font-bold text-lg">${{ totals.total }}</td></tr>
          </table>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end mt-8">
        <button @click="generateInvoice" :disabled="isSubmitting" class="bg-green-500 text-white font-bold py-2 px-4 rounded">
          <span v-if="isSubmitting">Generando...</span><span v-else>Generar Factura</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BaseSelect from './BaseSelect.vue';
import { paymentMethodOptions } from '../utils/paymentMethods.js';

export default {
  name: 'IndividualBilling',
  components: { BaseSelect },
  props: { token: { type: String, required: true } },
  data() {
    return {
      client: {
        ruc: '', name: '', address: '', email: '', telefono: '',
        fechaEmision: new Date().toISOString().slice(0,10),
      },
      items: [{ description: '', quantity: 1, price: 0, discount: 0, tax: '4', codigoPrincipal: '' }],
      establecimientos: [],
      puntosEmision: [],
      selectedEstablecimientoId: null,
      selectedPuntoEmisionId: null,
      selectedPaymentMethod: '01',
      paymentMethodOptions: paymentMethodOptions,
      additionalInfo: [{ name: '', value: '' }],
      userProfile: { tipo_impuesto: '2', codigo_porcentaje_iva: '4', ambiente: '1' },
      taxOptions: [
        { value: '4', text: 'IVA 15%' }, { value: '5', text: 'IVA 5%' }, { value: '8', text: 'IVA 8%' },
        { value: '0', text: 'IVA 0%' }, { value: '6', text: 'No objeto de IVA' }, { value: '7', text: 'Exento de IVA' },
      ],
      products: [],
      activeAutocomplete: null,
      filteredProducts: [],
    };
  },
  computed: {
    establecimientoOptions() { return this.establecimientos.map(e => ({ value: e.id, text: `${e.numero} - ${e.nombre}` })); },
    puntoEmisionOptions() {
      if (!this.selectedEstablecimientoId) return [];
      return this.puntosEmision.filter(p => p.establecimiento_id == this.selectedEstablecimientoId).map(p => ({ value: p.id, text: `${p.numero} - ${p.nombre}` }));
    },
    proximoSecuencial() {
      if (!this.selectedPuntoEmisionId) return '000000000';
      const pto = this.puntosEmision.find(p => p.id === this.selectedPuntoEmisionId);
      return pto ? String(pto.proximo_secuencial).padStart(9, '0') : '000000000';
    },
    totals() {
      let subtotal = 0, discount = 0, iva = {};
      this.items.forEach(item => {
        const itemSubtotal = item.quantity * item.price;
        subtotal += itemSubtotal;
        discount += item.discount;
        const taxRate = this.getTarifaFromCodigoPorcentaje(item.tax) / 100;
        const taxValue = (itemSubtotal - item.discount) * taxRate;
        if (!iva[item.tax]) iva[item.tax] = { base: 0, valor: 0 };
        iva[item.tax].base += itemSubtotal - item.discount;
        iva[item.tax].valor += taxValue;
      });
      const totalIva = Object.values(iva).reduce((acc, tax) => acc + tax.valor, 0);
      return { subtotal: subtotal.toFixed(2), discount: discount.toFixed(2), iva, total: (subtotal - discount + totalIva).toFixed(2) };
    }
  },
  watch: {
    selectedEstablecimientoId() { this.selectedPuntoEmisionId = null; },
    puntoEmisionOptions(newOptions) { if (newOptions.length > 0 && !this.selectedPuntoEmisionId) this.selectedPuntoEmisionId = newOptions[0].value; }
  },
  mounted() {
    this.fetchUserProfile(); this.fetchEstablecimientos(); this.fetchPuntosEmision(); this.fetchProducts();
    this.$emitter.on('profile-updated', this.fetchUserProfile);
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    this.$emitter.off('profile-updated', this.fetchUserProfile);
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    getEstablecimientoCode() {
      const est = this.establecimientos.find(e => e.id === this.selectedEstablecimientoId);
      return est ? est.numero : '000';
    },
    getPuntoEmisionCode() {
      const pto = this.puntosEmision.find(p => p.id === this.selectedPuntoEmisionId);
      return pto ? pto.numero : '000';
    },
    handleClickOutside(event) { if (!this.$el.contains(event.target)) this.activeAutocomplete = null; },
    searchProducts(index) {
      const item = this.items[index];
      if (item.description.length < 1) { this.filteredProducts = []; return; }
      this.filteredProducts = this.products.filter(p => p.description.toLowerCase().includes(item.description.toLowerCase()));
    },
    selectProduct(index, product) {
      this.items[index] = { ...this.items[index], description: product.description, price: product.unit_price, tax: product.tax_code, codigoPrincipal: product.code };
      this.activeAutocomplete = null; this.filteredProducts = [];
    },
    addAdditionalInfo() { this.additionalInfo.push({ name: '', value: '' }); },
    removeAdditionalInfo(index) { this.additionalInfo.splice(index, 1); },
    addItem() { this.items.push({ description: '', quantity: 1, price: 0, discount: 0, tax: this.userProfile.codigo_porcentaje_iva, codigoPrincipal: '' }); },
    removeItem(index) { this.items.splice(index, 1); },
    calculateItemTotal(item) {
      const total = (item.quantity * item.price) - item.discount;
      return total.toFixed(2);
    },
    getTarifaFromCodigoPorcentaje(codigo) {
      const map = { '0': 0, '2': 12, '3': 14, '4': 15, '5': 5, '8': 8, '10': 13 };
      return map[codigo] || 0;
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api/profile', { headers: { 'Authorization': `Bearer ${this.token}` }});
        this.userProfile = response.data.data;
        this.items.forEach(item => { if (!item.tax) item.tax = this.userProfile.codigo_porcentaje_iva; });
      } catch (error) { console.error('Error fetching user profile:', error); }
    },
    async fetchEstablecimientos() {
      try {
        const response = await axios.get('/api/establecimientos', { headers: { 'Authorization': `Bearer ${this.token}` }});
        this.establecimientos = response.data.data.data;
        if (this.establecimientos.length > 0) this.selectedEstablecimientoId = this.establecimientos[0].id;
      } catch (error) { console.error('Error fetching establecimientos:', error); }
    },
    async fetchPuntosEmision() {
      try {
        const response = await axios.get('/api/puntos-emision', { headers: { 'Authorization': 'Bearer ' + this.token }});
        this.puntosEmision = response.data.data.data;
      } catch (error) { console.error('Error fetching puntos de emision:', error); }
    },
    async fetchProducts() {
      try {
        const response = await axios.get('/api/products', { headers: { 'Authorization': `Bearer ${this.token}` }});
        this.products = response.data.data;
      } catch (error) { console.error('Error fetching products:', error); }
    },
    async generateInvoice() {
      if (this.isSubmitting) return;
      this.isSubmitting = true;
      try {
        const totalSinImpuestos = this.items.reduce((acc, item) => acc + (item.quantity * item.price) - item.discount, 0);
        const detalles = this.items.map((item, index) => {
          const itemSubtotal = item.quantity * item.price;
          const taxRate = this.getTarifaFromCodigoPorcentaje(item.tax);
          const taxValue = (itemSubtotal - item.discount) * (taxRate / 100);
          return {
            codigoPrincipal: item.codigoPrincipal || 'PROD' + Date.now() + '-' + index,
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
          fechaEmision: this.client.fechaEmision,
          tipoIdentificacionComprador: String(this.client.ruc).length === 13 ? '04' : '05',
          razonSocialComprador: this.client.name,
          identificacionComprador: this.client.ruc,
          direccionComprador: this.client.address,
          totalSinImpuestos: totalSinImpuestos.toFixed(2),
          totalDescuento: this.totals.discount,
          totalConImpuestos: Object.values(this.totals.iva).map(tax => ({
            codigo: this.userProfile.tipo_impuesto,
            codigoPorcentaje: Object.keys(this.totals.iva).find(key => this.totals.iva[key] === tax),
            baseImponible: tax.base.toFixed(2),
            valor: tax.valor.toFixed(2),
          })),
          importeTotal: this.totals.total,
          pagos: [{ formaPago: this.selectedPaymentMethod, total: this.totals.total }],
          detalles: detalles,
          infoAdicional: { email: this.client.email, telefono: this.client.telefono, ...this.additionalInfo.reduce((acc, info) => { if(info.name) acc[info.name] = info.value; return acc; }, {})},
        };
        await axios.post(`/api/comprobantes/factura/${this.selectedPuntoEmisionId}`, payload, { headers: { 'Authorization': `Bearer ${this.token}` }});
        this.$emitter.emit('show-alert', { type: 'success', message: 'Factura generada exitosamente.' });
      } catch (error) {
        const message = error.response?.data?.message || 'Error al generar la factura.';
        this.$emitter.emit('show-alert', { type: 'error', message: message });
      } finally {
        this.isSubmitting = false;
      }
    }
  }
};
</script>

<style scoped>
.form-input-pdf-inline {
  border-width: 0;
  border-bottom-width: 1px;
  border-color: #d1d5db; /* gray-300 */
  background-color: transparent;
  padding: 2px 4px;
  margin-left: 6px;
  width: 70%;
}
.form-input-pdf-inline:focus {
  outline: none;
  border-color: #6366f1; /* indigo-500 */
}
.form-input-table {
  border-width: 0;
  background-color: transparent;
  padding: 2px;
}
.form-input-table:focus {
  outline: none;
  background-color: #eff6ff; /* blue-50 */
}
</style>
