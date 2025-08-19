<template>
  <div class="absolute inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl leading-6 font-medium text-gray-900">{{ formTitle }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <form @submit.prevent="saveUser" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" v-model="form.name" id="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                <p v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name[0] }}</p>
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" v-model="form.email" id="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                <p v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email[0] }}</p>
              </div>
              <div>
                <label for="ruc" class="block text-sm font-medium text-gray-700">RUC</label>
                <input type="text" v-model="form.ruc" id="ruc" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                <p v-if="formErrors.ruc" class="text-red-500 text-xs mt-1">{{ formErrors.ruc[0] }}</p>
              </div>
              <div>
                <label for="razonSocial" class="block text-sm font-medium text-gray-700">Razón Social</label>
                <input type="text" v-model="form.razonSocial" id="razonSocial" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                <p v-if="formErrors.razonSocial" class="text-red-500 text-xs mt-1">{{ formErrors.razonSocial[0] }}</p>
              </div>
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" v-model="form.password" id="password" :required="!isEditMode" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Dejar en blanco para no cambiar">
                <p v-if="formErrors.password" class="text-red-500 text-xs mt-1">{{ formErrors.password[0] }}</p>
              </div>
            </div>
          </form>
          <div class="mt-6 flex justify-end space-x-4">
            <BaseButton @click="$emit('close')" variant="secondary">Cancelar</BaseButton>
            <BaseButton @click="saveUser" variant="primary">{{ isEditMode ? 'Guardar Cambios' : 'Crear Usuario' }}</BaseButton>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import axios from 'axios';
import BaseButton from './BaseButton.vue';

export default {
  name: 'UserModal',
  components: { BaseButton },
  props: {
    user: {
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
        tarifa: 'comprobante',
        ambiente: '1',
      },
      formErrors: {},
    };
  },
  computed: {
    isEditMode() {
      return !!this.user;
    },
    formTitle() {
      return this.isEditMode ? 'Editar Usuario' : 'Crear Usuario';
    },
  },
  watch: {
    user: {
      handler(newVal) {
        this.formErrors = {};
        if (newVal) {
          this.form = { ...this.form, ...newVal };
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
            name: '',
            email: '',
            password: '',
            ruc: '',
            razonSocial: '',
            nombreComercial: '',
            dirMatriz: '',
            obligadoContabilidad: false,
            tarifa: 'comprobante',
            ambiente: '1',
        };
    },
    async saveUser() {
      this.formErrors = {};
      const method = this.isEditMode ? 'put' : 'post';
      const url = this.isEditMode ? `/api/admin/users/${this.user.id}` : '/api/admin/users';

      const payload = { ...this.form };
      if (this.isEditMode && !payload.password) {
        delete payload.password;
      }

      try {
        await axios[method](url, payload, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.$emitter.emit('show-alert', { type: 'success', message: 'Usuario guardado con éxito.' });
        this.$emit('user-saved');
        this.$emit('close');
      } catch (error) {
        if (error.response && (error.response.status === 400 || error.response.status === 422) && error.response.data.errors) {
            this.formErrors = error.response.data.errors;
        } else {
            console.error('Error saving user:', error);
            this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo guardar el usuario.' });
        }
      }
    },
  },
};
</script>
