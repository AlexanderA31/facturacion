<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="relative mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl leading-6 font-medium text-gray-900">{{ formTitle }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Invoice Header -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <BaseSelect id="proveedor-select" label="Proveedor" v-model="form.proveedor_id" :options="supplierOptions" placeholder="Seleccione un proveedor" required />
            <BaseSelect id="tipo-comprobante-select" label="Tipo Comprobante" v-model="form.tipo_comprobante" :options="comprobanteOptions" placeholder="Seleccione un tipo" required />
            <BaseSelect id="cod-sustento-select" label="Cód. Sustento" v-model="form.cod_sustento" :options="sustentoOptions" placeholder="Seleccione un sustento" required />
            <input type="text" v-model="form.establecimiento" placeholder="Establecimiento (001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.punto_emision" placeholder="Punto Emisión (001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.secuencial" placeholder="Secuencial (000000001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="date" v-model="form.fecha_emision" placeholder="Fecha Emisión" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="date" v-model="form.fecha_registro" placeholder="Fecha Registro" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.autorizacion" placeholder="Autorización" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <BaseSelect id="parte-relacionada-select" label="Parte Relacionada" v-model="form.parte_relacionada" :options="parteRelacionadaOptions" required />
          </div>

          <!-- Invoice Items -->
          <div class="mt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-2">Items</h4>
              <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-5 gap-2 mb-2 items-center">
                  <input type="text" v-model="item.descripcion" placeholder="Descripción" class="col-span-2 mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="item.cantidad" placeholder="Cantidad" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="item.precio_unitario" placeholder="Precio Unitario" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <button @click.prevent="removeItem(index)" class="text-red-500 hover:text-red-700">Eliminar</button>
              </div>
              <button @click.prevent="addItem" class="mt-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-1 px-2 rounded text-sm">Agregar Item</button>
          </div>

          <!-- Invoice Taxes -->
          <div class="mt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-2">Impuestos</h4>
              <div v-for="(tax, index) in form.taxes" :key="index" class="grid grid-cols-5 gap-2 mb-2 items-center">
                  <input type="text" v-model="tax.codigo_impuesto" placeholder="Cód. Impuesto" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="text" v-model="tax.codigo_porcentaje" placeholder="Cód. %" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="tax.base_imponible" placeholder="Base Imponible" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="tax.valor" placeholder="Valor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <button @click.prevent="removeTax(index)" class="text-red-500 hover:text-red-700">Eliminar</button>
              </div>
              <button @click.prevent="addTax" class="mt-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-1 px-2 rounded text-sm">Agregar Impuesto</button>
          </div>
        </form>

        <div class="mt-6 flex justify-end space-x-4">
          <BaseButton @click="$emit('close')" variant="secondary">Cancelar</BaseButton>
          <BaseButton @click="handleSubmit" :is-loading="isLoading" variant="primary">{{ isEditing ? 'Guardar Cambios' : 'Crear' }}</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BaseButton from './BaseButton.vue';
import BaseSelect from './BaseSelect.vue';

export default {
  name: 'PurchaseInvoiceModal',
  components: { BaseButton, BaseSelect },
  props: {
    show: { type: Boolean, required: true },
    purchase: { type: Object, default: null },
    isLoading: { type: Boolean, default: false },
  },
  data() {
    return {
      form: this.getInitialForm(),
      suppliers: [],
      comprobanteOptions: [
        { value: '01', text: 'Factura' },
        { value: '04', text: 'Nota de Crédito' },
        { value: '05', text: 'Nota de Débito' },
      ],
      sustentoOptions: [
        { value: '01', text: 'Crédito Tributario' },
        { value: '02', text: 'Costo o Gasto' },
      ],
      parteRelacionadaOptions: [
        { value: 'SI', text: 'Sí' },
        { value: 'NO', text: 'No' },
      ],
      token: localStorage.getItem('jwt_token'),
    };
  },
  computed: {
    isEditing() {
      return !!this.purchase;
    },
    formTitle() {
      return this.isEditing ? 'Editar Factura de Compra' : 'Crear Nueva Factura de Compra';
    },
    supplierOptions() {
        return this.suppliers.map(s => ({ value: s.id, text: s.razon_social }));
    }
  },
  watch: {
    purchase: {
      handler(newVal) {
        if (newVal) {
          this.form = { ...this.getInitialForm(), ...newVal };
        } else {
          this.form = this.getInitialForm();
        }
      },
      immediate: true,
      deep: true,
    },
  },
  async created() {
      await this.fetchSuppliers();
  },
  methods: {
    getInitialForm() {
        return {
            proveedor_id: null,
            tipo_comprobante: '01',
            establecimiento: '',
            punto_emision: '',
            secuencial: '',
            fecha_emision: new Date().toISOString().slice(0, 10),
            fecha_registro: new Date().toISOString().slice(0, 10),
            autorizacion: '',
            parte_relacionada: 'NO',
            cod_sustento: '01',
            items: [{ descripcion: '', cantidad: 1, precio_unitario: 0, descuento: 0 }],
            taxes: [{ codigo_impuesto: '2', codigo_porcentaje: '2', base_imponible: 0, valor: 0 }],
        };
    },
    async fetchSuppliers() {
        try {
            const response = await axios.get('/api/suppliers', {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            this.suppliers = response.data.data.data;
        } catch (error) {
            console.error('Error fetching suppliers:', error);
        }
    },
    addItem() {
      this.form.items.push({ descripcion: '', cantidad: 1, precio_unitario: 0, descuento: 0 });
    },
    removeItem(index) {
      this.form.items.splice(index, 1);
    },
    addTax() {
      this.form.taxes.push({ codigo_impuesto: '', codigo_porcentaje: '', base_imponible: 0, valor: 0 });
    },
    removeTax(index) {
      this.form.taxes.splice(index, 1);
    },
    handleSubmit() {
      this.$emit('save', { ...this.form });
    },
  },
};
</script>
