<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
      <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">Admin Panel</h1>
        <button @click="$emit('logout')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
          Logout
        </button>
      </div>
    </nav>

    <div class="container mx-auto p-4">
      <div class="flex border-b">
        <button @click="currentView = 'users'" :class="['py-2', 'px-4', currentView === 'users' ? 'border-b-2 border-blue-500' : '']">
          Manage Users
        </button>
        <button @click="currentView = 'clients'" :class="['py-2', 'px-4', currentView === 'clients' ? 'border-b-2 border-blue-500' : '']">
          Manage Clients
        </button>
      </div>

      <div class="mt-4">
        <AdminUsers v-if="currentView === 'users'" :token="token" />
        <AdminClients v-if="currentView === 'clients'" :token="token" />
      </div>
    </div>
  </div>
</template>

<script>
import AdminUsers from './AdminUsers.vue';
import AdminClients from './AdminClients.vue';

export default {
  name: 'AdminDashboard',
  components: {
    AdminUsers,
    AdminClients,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      currentView: 'users', // Default view
    };
  },
};
</script>
