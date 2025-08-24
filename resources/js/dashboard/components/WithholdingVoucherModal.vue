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
          <!-- Voucher Header -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <BaseSelect id="proveedor-select" label="Proveedor" v-model="form.proveedor_id" :options="supplierOptions" placeholder="Seleccione un proveedor" required />
            <input type="text" v-model="form.establecimiento" placeholder="Establecimiento (001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.punto_emision" placeholder="Punto Emisión (001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.secuencial" placeholder="Secuencial (000000001)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="date" v-model="form.fecha_emision" placeholder="Fecha Emisión" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <input type="text" v-model="form.autorizacion" placeholder="Autorización" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
          </div>

          <!-- Income Lines -->
          <div class="mt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-2">Retenciones de Renta</h4>
              <div v-for="(item, index) in form.incomeLines" :key="index" class="grid grid-cols-5 gap-2 mb-2 items-center">
                  <input type="text" v-model="item.cod_ret_air" placeholder="Cód. Ret." class="col-span-1 mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="item.base_imponible" placeholder="Base Imponible" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="item.porcentaje" placeholder="Porcentaje" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="item.valor" placeholder="Valor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <button @click.prevent="removeIncomeLine(index)" class="text-red-500 hover:text-red-700">Eliminar</button>
              </div>
              <button @click.prevent="addIncomeLine" class="mt-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-1 px-2 rounded text-sm">Agregar Retención Renta</button>
          </div>

          <!-- VAT Lines -->
          <div class="mt-6">
              <h4 class="text-lg font-medium text-gray-800 mb-2">Retenciones de IVA</h4>
              <div v-for="(tax, index) in form.vatLines" :key="index" class="grid grid-cols-5 gap-2 mb-2 items-center">
                  <input type="text" v-model="tax.cod_ret_iva" placeholder="Cód. Ret." class="col-span-1 mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="tax.base_imponible" placeholder="Base Imponible" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="tax.porcentaje" placeholder="Porcentaje" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <input type="number" v-model.number="tax.valor" placeholder="Valor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                  <button @click.prevent="removeVatLine(index)" class="text-red-500 hover:text-red-700">Eliminar</button>
              </div>
              <button @click.prevent="addVatLine" class="mt-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-1 px-2 rounded text-sm">Agregar Retención IVA</button>
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
  name: 'WithholdingVoucherModal',
  components: { BaseButton, BaseSelect },
  props: {
    show: { type: Boolean, required: true },
    withholding: { type: Object, default: null },
    isLoading: { type: Boolean, default: false },
  },
  data() {
    return {
      form: this.getInitialForm(),
      suppliers: [],
      token: localStorage.getItem('jwt_token'),
    };
  },
  computed: {
    isEditing() {
      return !!this.withholding;
    },
    formTitle() {
      return this.isEditing ? 'Editar Retención' : 'Crear Nueva Retención';
    },
    supplierOptions() {
        return this.suppliers.map(s => ({ value: s.id, text: s.razon_social }));
    }
  },
  watch: {
    withholding: {
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
            establecimiento: '',
            punto_emision: '',
            secuencial: '',
            fecha_emision: new Date().toISOString().slice(0, 10),
            autorizacion: '',
            incomeLines: [],
            vatLines: [],
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
    addIncomeLine() {
      this.form.incomeLines.push({ cod_ret_air: '', base_imponible: 0, porcentaje: 0, valor: 0 });
    },
    removeIncomeLine(index) {
      this.form.incomeLines.splice(index, 1);
    },
    addVatLine() {
      this.form.vatLines.push({ cod_ret_iva: '', base_imponible: 0, porcentaje: 0, valor: 0 });
    },
    removeVatLine(index) {
      this.form.vatLines.splice(index, 1);
    },
    handleSubmit() {
      this.$emit('save', { ...this.form });
    },
  },
};
</script>
