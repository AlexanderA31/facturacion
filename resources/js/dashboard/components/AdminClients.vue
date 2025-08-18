<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold">Manage Client Users</h2>
      <BaseButton @click="showClientModal = true">Create Client</BaseButton>
    </div>

    <DataTable :headers="headers" :data="clients">
      <template #cell(actions)="{ row }">
        <button @click="editClient(row)" class="text-blue-600 hover:text-blue-900">Edit</button>
        <button @click="deleteClient(row)" class="text-red-600 hover:text-red-900 ml-4">Delete</button>
        <button @click="openSignatureModal(row)" class="text-green-600 hover:text-green-900 ml-4">Upload Signature</button>
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
        { text: 'Name', value: 'name' },
        { text: 'Email', value: 'email' },
        { text: 'RUC', value: 'ruc' },
        { text: 'Actions', value: 'actions' },
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
  methods: {
    async fetchClients(page) {
      try {
        const response = await axios.get(`/api/admin/clients?page=${page}`, {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.clients = response.data.data;
        this.pagination.currentPage = response.data.meta.current_page;
        this.pagination.totalPages = response.data.meta.last_page;
      } catch (error) {
        console.error('Error fetching clients:', error);
      }
    },
    editClient(client) {
      this.selectedClient = { ...client };
      this.showClientModal = true;
    },
    async deleteClient(client) {
      if (confirm(`Are you sure you want to delete ${client.name}?`)) {
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
