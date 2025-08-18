<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="mt-3 text-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Upload Signature for {{ client.name }}</h3>
        <form @submit.prevent="uploadSignature" class="mt-2 space-y-4">
          <div>
            <label for="signatureFile" class="block text-sm font-medium text-gray-700">Signature File (.p12)</label>
            <input type="file" @change="handleFileChange" id="signatureFile" required accept=".p12" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm">
          </div>
          <div>
            <label for="signaturePassword" class="block text-sm font-medium text-gray-700">Signature Password</label>
            <input type="password" v-model="password" id="signaturePassword" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm">
          </div>
          <div class="items-center px-4 py-3">
            <button type="submit" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700">
              Upload
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
  name: 'SignatureUploadModal',
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
        this.$emit('signature-uploaded');
        this.$emit('close');
      } catch (error) {
        console.error('Error uploading signature:', error);
        alert('Failed to upload signature. Check console for details.');
      }
    },
  },
};
</script>
