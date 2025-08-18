<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="mt-3 text-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ formTitle }}</h3>
        <form @submit.prevent="saveUser" class="mt-2 space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" v-model="form.name" id="name" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm">
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" v-model="form.email" id="email" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm">
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" v-model="form.password" id="password" :required="!isEditMode" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm" placeholder="Leave blank to keep current password">
          </div>
          <div class="items-center px-4 py-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
              Save
            </button>
          </div>
        </form>
        <div class="items-center px-4 py-3">
          <button @click="$emit('close')" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
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
      },
    };
  },
  computed: {
    isEditMode() {
      return !!this.user;
    },
    formTitle() {
      return this.isEditMode ? 'Edit User' : 'Create User';
    },
  },
  created() {
    if (this.user) {
      this.form.name = this.user.name;
      this.form.email = this.user.email;
    }
  },
  methods: {
    async saveUser() {
      const method = this.isEditMode ? 'put' : 'post';
      const url = this.isEditMode ? `/api/admin/users/${this.user.id}` : '/api/admin/users';

      const payload = { ...this.form };
      if (this.isEditMode && !payload.password) {
        delete payload.password; // Don't send empty password on update
      }

      try {
        await axios[method](url, payload, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.$emit('user-saved');
      } catch (error) {
        console.error('Error saving user:', error);
        alert('Failed to save user. Check console for details.');
      }
    },
  },
};
</script>
