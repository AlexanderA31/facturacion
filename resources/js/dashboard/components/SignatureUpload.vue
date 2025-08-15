<template>
  <div>
    <div v-if="message" class="mb-4 p-3 rounded-md" :class="messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
      <p class="font-semibold">{{ messageTitle }}</p>
      <ul v-if="errorDetails.length" class="list-disc list-inside text-sm">
        <li v-for="(detail, index) in errorDetails" :key="index">{{ detail }}</li>
      </ul>
    </div>
    <form @submit.prevent="uploadSignature" class="space-y-4">
      <div>
        <label for="signatureFile" class="block text-sm font-medium text-gray-700 mb-1">Archivo de Firma (.p12)</label>
        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
          <div class="space-y-1 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="flex text-sm text-gray-600">
              <label for="signatureFile" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                <span>Sube un archivo</span>
                <input id="signatureFile" ref="fileInput" name="signatureFile" type="file" class="sr-only" @change="handleFileChange" accept=".p12">
              </label>
              <p class="pl-1">o arrástralo aquí</p>
            </div>
            <p class="text-xs text-gray-500">Solo archivos .p12</p>
            <p v-if="file" class="text-sm text-gray-900 mt-2">{{ file.name }}</p>
          </div>
        </div>
      </div>
      <div>
        <label for="signaturePassword" class="block text-sm font-medium text-gray-700">Contraseña de la Firma</label>
        <input type="password" id="signaturePassword" v-model="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
      </div>
      <button type="submit" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-400">
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
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

        this.$nextTick(() => {
          if (this.$refs.fileInput) {
            this.$refs.fileInput.value = '';
          }
        });

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
