<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold">Manage Admin Users</h2>
      <BaseButton @click="showUserModal = true">Create User</BaseButton>
    </div>

    <DataTable :headers="headers" :data="users">
      <template #cell(actions)="{ row }">
        <button @click="editUser(row)" class="text-blue-600 hover:text-blue-900">Edit</button>
        <button @click="deleteUser(row)" class="text-red-600 hover:text-red-900 ml-4">Delete</button>
      </template>
    </DataTable>

    <Pagination
      :currentPage="pagination.currentPage"
      :totalPages="pagination.totalPages"
      @prev-page="handlePrevPage"
      @next-page="handleNextPage"
    />

    <UserModal
      v-if="showUserModal"
      :user="selectedUser"
      :token="token"
      @close="showUserModal = false"
      @user-saved="fetchUsers(1)"
    />
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';
import BaseButton from './BaseButton.vue';
import UserModal from './UserModal.vue';
import Pagination from './Pagination.vue';

export default {
  name: 'AdminUsers',
  components: {
    DataTable,
    BaseButton,
    UserModal,
    Pagination,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      users: [],
      headers: [
        { text: 'Name', value: 'name' },
        { text: 'Email', value: 'email' },
        { text: 'Actions', value: 'actions' },
      ],
      showUserModal: false,
      selectedUser: null,
      pagination: {
        currentPage: 1,
        totalPages: 1,
      },
    };
  },
  mounted() {
    this.fetchUsers(this.pagination.currentPage);
  },
  methods: {
    async fetchUsers(page) {
      try {
        const response = await axios.get(`/api/admin/users?page=${page}`, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.users = response.data.data;
        this.pagination.currentPage = response.data.meta.current_page;
        this.pagination.totalPages = response.data.meta.last_page;
        this.showUserModal = false;
        this.selectedUser = null;
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    },
    editUser(user) {
      this.selectedUser = { ...user };
      this.showUserModal = true;
    },
    async deleteUser(user) {
      if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        try {
          await axios.delete(`/api/admin/users/${user.id}`, {
            headers: { Authorization: `Bearer ${this.token}` },
          });
          this.fetchUsers(this.pagination.currentPage);
        } catch (error) {
          console.error('Error deleting user:', error);
          alert('Failed to delete user.');
        }
      }
    },
    handlePrevPage() {
      if (this.pagination.currentPage > 1) {
        this.fetchUsers(this.pagination.currentPage - 1);
      }
    },
    handleNextPage() {
      if (this.pagination.currentPage < this.pagination.totalPages) {
        this.fetchUsers(this.pagination.currentPage + 1);
      }
    },
  },
};
</script>
