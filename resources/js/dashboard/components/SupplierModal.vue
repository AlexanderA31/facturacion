<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl leading-6 font-medium text-gray-900">{{ formTitle }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label for="tipo_id" class="block text-sm font-medium text-gray-700">Tipo de Identificación</label>
            <select id="tipo_id" v-model="form.tipo_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                <option value="01">RUC</option>
                <option value="02">Cédula</option>
                <option value="03">Pasaporte</option>
            </select>
          </div>
          <div>
            <label for="identificacion" class="block text-sm font-medium text-gray-700">Identificación</label>
            <input type="text" id="identificacion" v-model="form.identificacion" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
          </div>
          <div>
            <label for="razon_social" class="block text-sm font-medium text-gray-700">Razón Social</label>
            <input type="text" id="razon_social" v-model="form.razon_social" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
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
import BaseButton from './BaseButton.vue';

export default {
  name: 'SupplierModal',
  components: { BaseButton },
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    supplier: {
      type: Object,
      default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    }
  },
  data() {
    return {
      form: {
        tipo_id: '01',
        identificacion: '',
        razon_social: '',
      },
    };
  },
  computed: {
    isEditing() {
      return !!this.supplier;
    },
    formTitle() {
      return this.isEditing ? 'Editar Proveedor' : 'Crear Nuevo Proveedor';
    },
  },
  watch: {
    supplier: {
      handler(newVal) {
        if (newVal) {
          this.form = { ...newVal };
        } else {
          this.resetForm();
        }
      },
      immediate: true,
    },
  },
  methods: {
    resetForm() {
      this.form = {
        tipo_id: '01',
        identificacion: '',
        razon_social: '',
      };
    },
    handleSubmit() {
      this.$emit('save', { ...this.form });
    },
  },
};
</script>
