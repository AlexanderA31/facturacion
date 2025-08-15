<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Mis Comprobantes</h2>

    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#" @click.prevent="currentTab = 'all'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', currentTab === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Todos
            </a>
            <a href="#" @click.prevent="currentTab = 'authorized'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', currentTab === 'authorized' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Autorizados
            </a>
            <a href="#" @click.prevent="currentTab = 'pending'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', currentTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Pendientes
            </a>
            <a href="#" @click.prevent="currentTab = 'unauthorized'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', currentTab === 'unauthorized' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                No Autorizados
            </a>
        </nav>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <DataTable :data="paginatedInvoices" :headers="headers" @download-xml="downloadXml" @download-pdf="downloadPdf" @toggle-error-expansion="toggleErrorExpansion" />
      <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';
import Pagination from './Pagination.vue';

export default {
  name: 'MyInvoices',
  components: {
    DataTable,
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
      invoices: [],
      headers: ['Clave de Acceso', 'Estado', 'Fecha', 'Mensaje de Error', 'Acciones'],
      currentPage: 1,
      itemsPerPage: 10,
      currentTab: 'all',
    };
  },
  computed: {
    filteredInvoices() {
        switch (this.currentTab) {
            case 'authorized':
                return this.invoices.filter(i => i.estado === 'autorizado');
            case 'pending':
                return this.invoices.filter(i => ['pendiente', 'procesando', 'firmado'].includes(i.estado));
            case 'unauthorized':
                return this.invoices.filter(i => ['rechazado', 'fallido'].includes(i.estado));
            case 'all':
            default:
                return this.invoices;
        }
    },
    totalPages() {
      return Math.ceil(this.filteredInvoices.length / this.itemsPerPage);
    },
    paginatedInvoices() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredInvoices.slice(start, end);
    },
  },
  mounted() {
    this.getInvoices();
  },
  methods: {
    async getInvoices() {
      try {
        const response = await axios.get('/api/comprobantes', {
          headers: { 'Authorization': `Bearer ${this.token}` },
          params: { per_page: 100 } // Fetch a good number of invoices
        });
        this.invoices = response.data.data.data.map(invoice => ({
          ...invoice,
          isErrorExpanded: false,
        }));
      } catch (error) {
        console.error('Error fetching invoices:', error);
      }
    },
    toggleErrorExpansion(invoiceId) {
      const invoice = this.invoices.find(i => i.id === invoiceId);
      if (invoice) {
        invoice.isErrorExpanded = !invoice.isErrorExpanded;
      }
    },
    async downloadXml(claveAcceso) {
      try {
        const response = await axios.get(`/api/sri/comprobante/${claveAcceso}`, {
          headers: { 'Authorization': `Bearer ${this.token}` },
          responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/xml' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${claveAcceso}.xml`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        console.error('Error downloading XML:', error);
        alert('No se pudo descargar el archivo XML.');
      }
    },
    async downloadPdf(claveAcceso) {
      try {
        const response = await axios.get(`/api/sri/comprobante/${claveAcceso}/pdf`, {
          headers: { 'Authorization': `Bearer ${this.token}` },
          responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${claveAcceso}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        console.error('Error downloading PDF:', error);
        alert('No se pudo descargar el archivo PDF.');
      }
    },
  },
};
</script>
