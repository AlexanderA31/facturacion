<template>
  <div class="p-4 border rounded-lg shadow-sm">
    <div v-if="message" :class="messageType === 'success' ? 'text-green-500' : 'text-red-500'" class="mb-4">
      {{ message }}
    </div>
    <form @submit.prevent="uploadSignature">
      <div class="mb-4">
        <label for="signatureFile" class="block text-sm font-medium text-gray-700">Signature File (.p12)</label>
        <input type="file" id="signatureFile" @change="handleFileChange" accept=".p12" required class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
      </div>
      <div class="mb-4">
        <label for="signaturePassword" class="block text-sm font-medium text-gray-700">Signature Password</label>
        <input type="password" id="signaturePassword" v-model="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
      </div>
      <button type="submit" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-400">
        {{ loading ? 'Uploading...' : 'Upload Signature' }}
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
    };
  },
  methods: {
    handleFileChange(event) {
      this.file = event.target.files[0];
    },
    async uploadSignature() {
      if (!this.file) {
        this.message = 'Please select a file.';
        this.messageType = 'error';
        return;
      }

      this.loading = true;
      this.message = '';
      this.messageType = '';

      const formData = new FormData();
      formData.append('signature', this.file);
      formData.append('password', this.password);

      try {
        // We need to get a token first. For now, let's assume we have it.
        // In a real app, you would get this from a login process.
        // I will mock the token for now and handle login later.
        const token = localStorage.getItem('jwt_token'); // This will be null for now

        const response = await axios.post('/api/profile/signature', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${token}`
          },
        });

        this.message = response.data.message;
        this.messageType = 'success';
      } catch (error) {
        if (error.response) {
          this.message = error.response.data.message || 'An error occurred during upload.';
        } else {
          this.message = 'An error occurred. Please check the console.';
        }
        this.messageType = 'error';
        console.error('Signature upload error:', error);
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
