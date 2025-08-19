<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold">Gestionar Usuarios Clientes</h2>
      <BaseButton @click="showClientModal = true">
        <template #icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </template>
        Crear Cliente
      </BaseButton>
    </div>

    <DataTable :headers="headers" :data="clients">
      <template #cell(actions)="{ row }">
        <div class="flex items-center space-x-2">
            <button @click="editClient(row)" title="Editar" class="p-1 text-yellow-600 hover:text-yellow-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </button>
            <button @click="deleteClient(row)" title="Eliminar" class="p-1 text-red-600 hover:text-red-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
            <button @click="openSignatureModal(row)" title="Cargar Firma" class="p-1 text-green-600 hover:text-green-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
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

    <ClientModal
      v-if="showClientModal"
      :client="selectedClient"
      :token="token"
      @close="closeClientModal"
      @client-saved="fetchClients(1)"
    />

    <SignatureUploadModal
      v-if="showSignatureModal"
      :client="selectedClient"
      :token="token"
      @close="closeSignatureModal"
      @signature-uploaded="fetchClients(pagination.currentPage)"
    />
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';
import BaseButton from './BaseButton.vue';
import ClientModal from './ClientModal.vue';
import SignatureUploadModal from './SignatureUploadModal.vue';
import Pagination from './Pagination.vue';

export default {
  name: 'AdminClients',
  components: {
    DataTable,
    BaseButton,
    ClientModal,
    SignatureUploadModal,
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
      clients: [],
      headers: [
        { text: 'Nombre', value: 'name' },
        { text: 'Correo', value: 'email' },
        { text: 'RUC', value: 'ruc' },
        { text: 'Acciones', value: 'actions' },
      ],
      showClientModal: false,
      showSignatureModal: false,
      selectedClient: null,
      pagination: {
        currentPage: 1,
        totalPages: 1,
      },
    };
  },
  mounted() {
    this.fetchClients(1);
  },
  unmounted() {
    this.clients = [];
    this.pagination.currentPage = 1;
    this.pagination.totalPages = 1;
  },
  methods: {
    async fetchClients(page) {
      try {
        const response = await axios.get(`/api/admin/clients?page=${page}`, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        const paginatedData = response.data.data;
        this.clients = paginatedData.data;
        this.pagination.currentPage = paginatedData.current_page;
        this.pagination.totalPages = paginatedData.last_page;
      } catch (error) {
        console.error('Error fetching clients:', error);
      }
    },
    editClient(client) {
      this.selectedClient = { ...client };
      this.showClientModal = true;
    },
    async deleteClient(client) {
      if (confirm(`¿Está seguro de que desea eliminar a ${client.name}?`)) {
        try {
          await axios.delete(`/api/admin/clients/${client.id}`, {
            headers: { Authorization: `Bearer ${this.token}` },
          });
          this.fetchClients(this.pagination.currentPage);
        } catch (error) {
          console.error('Error deleting client:', error);
          alert('Failed to delete client.');
        }
      }
    },
    openSignatureModal(client) {
      this.selectedClient = { ...client };
      this.showSignatureModal = true;
    },
    closeClientModal() {
      this.showClientModal = false;
      this.selectedClient = null;
    },
    closeSignatureModal() {
      this.showSignatureModal = false;
      this.selectedClient = null;
    },
    handlePrevPage() {
      if (this.pagination.currentPage > 1) {
        this.fetchClients(this.pagination.currentPage - 1);
      }
    },
    handleNextPage() {
      if (this.pagination.currentPage < this.pagination.totalPages) {
        this.fetchClients(this.pagination.currentPage + 1);
      }
    },
  },
};
</script>
