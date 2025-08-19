<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
      <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">{{ formTitle }}</h3>
          <form @submit.prevent="saveUser" class="mt-2 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <input type="text" v-model="form.name" placeholder="Nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p v-if="formErrors.name" class="text-red-500 text-xs mt-1 text-left">{{ formErrors.name[0] }}</p>
              </div>
              <div>
                <input type="email" v-model="form.email" placeholder="Correo" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p v-if="formErrors.email" class="text-red-500 text-xs mt-1 text-left">{{ formErrors.email[0] }}</p>
              </div>
              <div>
                <input type="text" v-model="form.ruc" placeholder="RUC" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p v-if="formErrors.ruc" class="text-red-500 text-xs mt-1 text-left">{{ formErrors.ruc[0] }}</p>
              </div>
              <div>
                <input type="text" v-model="form.razonSocial" placeholder="Razón Social" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p v-if="formErrors.razonSocial" class="text-red-500 text-xs mt-1 text-left">{{ formErrors.razonSocial[0] }}</p>
              </div>
              <input type="text" v-model="form.nombreComercial" placeholder="Nombre Comercial" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <input type="text" v-model="form.dirMatriz" placeholder="Dirección Matriz" class="w-full px-3 py-2 border border-gray-300 rounded-md">
              <div>
                <input type="password" v-model="form.password" placeholder="Contraseña" :required="!isEditMode" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p v-if="formErrors.password" class="text-red-500 text-xs mt-1 text-left">{{ formErrors.password[0] }}</p>
              </div>
              <div>
                  <select v-model="form.tarifa" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                      <option value="comprobante">Tarifa por Comprobante</option>
                      <option value="establecimiento">Tarifa por Establecimiento</option>
                  </select>
              </div>
              <div>
                  <select v-model="form.ambiente" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                      <option value="1">Pruebas</option>
                      <option value="2">Producción</option>
                  </select>
              </div>
              <div class="flex items-center">
                <input type="checkbox" v-model="form.obligadoContabilidad" id="obligadoContabilidadUser" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="obligadoContabilidadUser" class="ml-2 block text-sm text-gray-900">Obligado Contabilidad</label>
              </div>
            </div>
            <div class="items-center px-4 py-3">
              <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700">
                Guardar Usuario
              </button>
            </div>
          </form>
          <div class="items-center px-4 py-3">
            <button @click="$emit('close')" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UserModal',
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
  created() {
    if (this.user) {
      this.form = { ...this.form, ...this.user };
    }
  },
  methods: {
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
