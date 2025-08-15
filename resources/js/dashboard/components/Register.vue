<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-lg p-8 space-y-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center text-gray-900">Crear una cuenta</h2>
      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
          <input v-model="form.name" id="name" name="name" type="text" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
          <input v-model="form.email" id="email" name="email" type="email" autocomplete="email" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
          <input v-model="form.password" id="password" name="password" type="password" autocomplete="new-password" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="ruc" class="block text-sm font-medium text-gray-700">RUC</label>
          <input v-model="form.ruc" id="ruc" name="ruc" type="text" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="razonSocial" class="block text-sm font-medium text-gray-700">Razón Social</label>
          <input v-model="form.razonSocial" id="razonSocial" name="razonSocial" type="text" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="nombreComercial" class="block text-sm font-medium text-gray-700">Nombre Comercial</label>
          <input v-model="form.nombreComercial" id="nombreComercial" name="nombreComercial" type="text"
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label for="dirMatriz" class="block text-sm font-medium text-gray-700">Dirección Matriz</label>
          <input v-model="form.dirMatriz" id="dirMatriz" name="dirMatriz" type="text" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="flex items-center">
          <input v-model="form.obligadoContabilidad" id="obligadoContabilidad" name="obligadoContabilidad" type="checkbox"
                 class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
          <label for="obligadoContabilidad" class="block ml-2 text-sm text-gray-900">Obligado a llevar contabilidad</label>
        </div>
        <div>
          <button type="submit"
                  class="w-full px-4 py-2 font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Registrarse
          </button>
        </div>
      </form>
      <p class="text-sm text-center text-gray-600">
        ¿Ya tienes una cuenta?
        <button @click="$emit('show-login')" class="font-medium text-indigo-600 hover:text-indigo-500">
          Inicia sesión
        </button>
      </p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Register',
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
  methods: {
    async handleRegister() {
      try {
        await axios.post('/api/register', this.form);
        alert('Registro exitoso. Ahora puedes iniciar sesión.');
        this.$emit('show-login');
      } catch (error) {
        console.error('Error during registration:', error);
        alert('Error en el registro: ' + (error.response?.data?.message || error.message));
      }
    },
  },
};
</script>
