<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center text-gray-900">Iniciar Sesión</h1>
      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input type="email" v-model="loginForm.email" id="email" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
          <input type="password" v-model="loginForm.password" id="password" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div v-if="loginError" class="text-sm text-red-600">
          {{ loginError }}
        </div>
        <div>
          <button type="submit" :disabled="isLoggingIn" class="w-full px-4 py-2 font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-blue-400">
            {{ isLoggingIn ? 'Iniciando...' : 'Entrar' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Login',
  data() {
    return {
      loginForm: {
        email: 'client@example.com', // Default for easy testing
        password: '123456789',   // Default for easy testing
      },
      loginError: '',
      isLoggingIn: false,
    };
  },
  methods: {
    async handleLogin() {
      this.isLoggingIn = true;
      this.loginError = '';
      try {
        const response = await axios.post('/api/login', this.loginForm);
        const token = response.data.data.token;
        this.$emit('login-success', token);
      } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
          this.loginError = error.response.data.message;
        } else {
          this.loginError = 'Ocurrió un error al iniciar sesión.';
        }
      } finally {
        this.isLoggingIn = false;
      }
    },
  },
};
</script>
