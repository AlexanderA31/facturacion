<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold">Gestionar Usuarios Administradores</h2>
      <div class="flex space-x-2">
        <BaseButton @click="fetchUsers(1)" title="Refrescar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9a9 9 0 0114.65-4.65l-2.12 2.12a5 5 0 00-9.4 3.53"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 15a9 9 0 01-14.65 4.65l2.12-2.12a5 5 0 009.4-3.53"></path></svg>
        </BaseButton>
        <BaseButton @click="showUserModal = true">
            <template #icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </template>
            Crear Usuario
        </BaseButton>
      </div>
    </div>

    <TableSkeleton v-if="isLoading" />
    <DataTable v-else :headers="headers" :data="users">
      <template #cell(actions)="{ row }">
        <div class="flex items-center space-x-2">
          <button @click="editUser(row)" title="Editar" class="p-1 text-yellow-600 hover:text-yellow-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
          </button>
          <button @click="deleteUser(row)" title="Eliminar" class="p-1 text-red-600 hover:text-red-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
          </button>
        </div>
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
import TableSkeleton from './TableSkeleton.vue';

export default {
  name: 'AdminUsers',
  components: {
    DataTable,
    BaseButton,
    UserModal,
    Pagination,
    TableSkeleton,
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
        { text: 'Nombre', value: 'name' },
        { text: 'Correo', value: 'email' },
        { text: 'Acciones', value: 'actions' },
      ],
      showUserModal: false,
      selectedUser: null,
      isLoading: false,
      pagination: {
        currentPage: 1,
        totalPages: 1,
      },
    };
  },
  mounted() {
    this.fetchUsers(this.pagination.currentPage);
  },
  unmounted() {
    this.users = [];
    this.pagination.currentPage = 1;
    this.pagination.totalPages = 1;
  },
  methods: {
    async fetchUsers(page) {
      this.isLoading = true;
      try {
        const response = await axios.get(`/api/admin/users?page=${page}`, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        const paginatedData = response.data.data;
        this.users = paginatedData.data;
        this.pagination.currentPage = paginatedData.current_page;
        this.pagination.totalPages = paginatedData.last_page;
        this.showUserModal = false;
        this.selectedUser = null;
      } catch (error) {
        console.error('Error fetching users:', error);
      } finally {
        this.isLoading = false;
      }
    },
    editUser(user) {
      this.selectedUser = { ...user };
      this.showUserModal = true;
    },
    async deleteUser(user) {
      if (confirm(`¿Está seguro de que desea eliminar a ${user.name}?`)) {
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
