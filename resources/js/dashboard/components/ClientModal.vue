<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">{{ formTitle }}</h3>
        <form @submit.prevent="saveClient" class="mt-2 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" v-model="form.name" placeholder="Name" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="email" v-model="form.email" placeholder="Email" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="text" v-model="form.ruc" placeholder="RUC" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="text" v-model="form.razonSocial" placeholder="Razon Social" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="text" v-model="form.nombreComercial" placeholder="Nombre Comercial" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="text" v-model="form.dirMatriz" placeholder="Dirección Matriz" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <input type="password" v-model="form.password" placeholder="Password" :required="!isEditMode" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            <div class="flex items-center">
              <input type="checkbox" v-model="form.obligadoContabilidad" id="obligadoContabilidad" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
              <label for="obligadoContabilidad" class="ml-2 block text-sm text-gray-900">Obligado Contabilidad</label>
            </div>
          </div>
          <div class="items-center px-4 py-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700">
              Save Client
            </button>
          </div>
        </form>
        <div class="items-center px-4 py-3">
          <button @click="$emit('close')" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ClientModal',
  props: {
    client: {
      type: Object,
      default: null,
    },
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        ruc: '',
        razonSocial: '',
        nombreComercial: '',
        dirMatriz: '',
        obligadoContabilidad: false,
      },
    };
  },
  computed: {
    isEditMode() {
      return !!this.client;
    },
    formTitle() {
      return this.isEditMode ? 'Edit Client' : 'Create Client';
    },
  },
  created() {
    if (this.client) {
      this.form = { ...this.client };
    }
  },
  methods: {
    async saveClient() {
      const method = this.isEditMode ? 'put' : 'post';
      const url = this.isEditMode ? `/api/admin/clients/${this.client.id}` : '/api/admin/clients';

      const payload = { ...this.form };
      if (this.isEditMode && !payload.password) {
        delete payload.password;
      }

      try {
        await axios[method](url, payload, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.$emit('client-saved');
        this.$emit('close');
      } catch (error) {
        console.error('Error saving client:', error);
        alert('Failed to save client.');
      }
    },
  },
};
</script>
