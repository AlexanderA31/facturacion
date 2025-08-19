<template>
  <div class="absolute inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl leading-6 font-medium text-gray-900">Cargar Firma para {{ client.name }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <form @submit.prevent="uploadSignature" class="space-y-4">
            <div>
              <label for="signatureFile" class="block text-sm font-medium text-gray-700">Archivo de Firma (.p12)</label>
              <input type="file" @change="handleFileChange" id="signatureFile" required accept=".p12" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div>
              <label for="signaturePassword" class="block text-sm font-medium text-gray-700">Contraseña de la Firma</label>
              <input type="password" v-model="password" id="signaturePassword" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
          </form>
          <div class="mt-6 flex justify-end space-x-4">
            <BaseButton @click="$emit('close')" variant="secondary">Cancelar</BaseButton>
            <BaseButton @click="uploadSignature" variant="primary">Cargar Firma</BaseButton>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import axios from 'axios';
import BaseButton from './BaseButton.vue';

export default {
  name: 'SignatureUploadModal',
  components: { BaseButton },
  props: {
    client: {
      type: Object,
      required: true,
    },
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      file: null,
      password: '',
    };
  },
  methods: {
    handleFileChange(event) {
      this.file = event.target.files[0];
    },
    async uploadSignature() {
      if (!this.file) {
        alert('Please select a signature file.');
        return;
      }

      const formData = new FormData();
      formData.append('signature', this.file);
      formData.append('password', this.password);

      try {
        await axios.post(`/api/admin/clients/${this.client.id}/load_signature`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            Authorization: `Bearer ${this.token}`,
          },
        });
        this.$emitter.emit('show-alert', { type: 'success', message: 'Firma cargada con éxito.' });
        this.$emit('signature-uploaded');
        this.$emit('close');
      } catch (error) {
        console.error('Error uploading signature:', error);
        this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo cargar la firma.' });
      }
    },
  },
};
</script>
