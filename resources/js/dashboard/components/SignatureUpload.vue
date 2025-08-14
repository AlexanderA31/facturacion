<template>
  <div class="p-4 border rounded-lg shadow-sm">
    <div v-if="message" class="mb-4 p-3 rounded-md" :class="messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
      <p class="font-semibold">{{ messageTitle }}</p>
      <ul v-if="errorDetails.length" class="list-disc list-inside text-sm">
        <li v-for="(detail, index) in errorDetails" :key="index">{{ detail }}</li>
      </ul>
    </div>
    <form @submit.prevent="uploadSignature">
      <div class="mb-4">
        <label for="signatureFile" class="block text-sm font-medium text-gray-700">Archivo de Firma (.p12)</label>
        <input type="file" id="signatureFile" @change="handleFileChange" accept=".p12" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
      </div>
      <div class="mb-4">
        <label for="signaturePassword" class="block text-sm font-medium text-gray-700">Contraseña de la Firma</label>
        <input type="password" id="signaturePassword" v-model="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
      </div>
      <button type="submit" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-400">
        {{ loading ? 'Subiendo...' : 'Subir Firma' }}
      </button>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SignatureUpload',
  data() {
    return {
      file: null,
      password: '',
      loading: false,
      message: '',
      messageType: '',
      errorDetails: [],
    };
  },
  computed: {
      messageTitle() {
          if (this.messageType === 'success') return 'Éxito';
          if (this.messageType === 'error') return 'Error';
          return '';
      }
  },
  methods: {
    handleFileChange(event) {
      this.file = event.target.files[0];
    },
    validateInput() {
        this.message = '';
        this.messageType = '';
        this.errorDetails = [];

        let errors = [];
        if (!this.file) {
            errors.push('El archivo de firma es obligatorio.');
        }
        if (!this.password) {
            errors.push('La contraseña de la firma es obligatoria.');
        }

        if (errors.length > 0) {
            this.message = 'Por favor, corrige los siguientes errores:';
            this.messageType = 'error';
            this.errorDetails = errors;
            return false;
        }
        return true;
    },
    async uploadSignature() {
      if (!this.validateInput()) {
        return;
      }

      this.loading = true;

      const formData = new FormData();
      formData.append('signature_file', this.file);
      formData.append('signature_key', this.password);

      try {
        const token = localStorage.getItem('jwt_token');
        const response = await axios.post('/api/profile/signature', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${token}`
          },
        });

        this.message = response.data.message;
        this.messageType = 'success';
        this.errorDetails = [];
        this.file = null;
        this.password = '';
        this.$refs.fileInput.value = '';

      } catch (error) {
        this.messageType = 'error';
        if (error.response) {
            this.message = error.response.data.message || 'Ocurrió un error durante la subida.';
            if (error.response.status === 422 && error.response.data.errors) {
                this.errorDetails = Object.values(error.response.data.errors).flat();
            }
        } else {
            this.message = 'Ocurrió un error de red. Por favor, revisa la consola.';
        }
        console.error('Signature upload error:', error);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
