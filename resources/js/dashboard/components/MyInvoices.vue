<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-800">Mis Comprobantes</h2>
      <RefreshButton :is-loading="isLoading" @click="getInvoices(false)" />
    </div>

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
            <a href="#" @click.prevent="currentTab = 'duplicates'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', currentTab === 'duplicates' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                Duplicados
            </a>
        </nav>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <TableSkeleton v-if="isLoading" />
      <div v-else>
        <DataTable :data="paginatedInvoices" :headers="headers" @download-xml="downloadXml" @download-pdf="downloadPdf" @toggle-error-expansion="toggleErrorExpansion" />
        <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';
import Pagination from './Pagination.vue';
import TableSkeleton from './TableSkeleton.vue';
import RefreshButton from './RefreshButton.vue';

export default {
  name: 'MyInvoices',
  components: {
    DataTable,
    Pagination,
    TableSkeleton,
    RefreshButton,
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
      isLoading: false,
      polling: null,
      currentPage: 1,
      itemsPerPage: 10,
      currentTab: 'all',
    };
  },
  computed: {
    headers() {
        const baseHeaders = [
            { text: 'Clave de Acceso', value: 'clave_acceso' },
            { text: 'Estado', value: 'estado' },
            { text: 'Fecha', value: 'fecha_emision' },
        ];

        let finalHeaders = [...baseHeaders];

        if (this.currentTab === 'unauthorized') {
            finalHeaders.push({ text: 'Mensaje de Error', value: 'error_message' });
        }

        // Show actions on 'all', 'authorized', and 'duplicates' tabs
        if (this.currentTab === 'all' || this.currentTab === 'authorized' || this.currentTab === 'duplicates') {
            finalHeaders.push({ text: 'Acciones', value: 'acciones' });
        }

        return finalHeaders;
    },
    filteredInvoices() {
        switch (this.currentTab) {
            case 'authorized':
                return this.invoices.filter(i => i.estado === 'autorizado');
            case 'pending':
                return this.invoices.filter(i => ['pendiente', 'procesando', 'firmado'].includes(i.estado));
            case 'unauthorized':
                return this.invoices.filter(i =>
                    ['rechazado', 'fallido'].includes(i.estado) &&
                    i.error_message !== 'ERROR SECUENCIAL REGISTRADO'
                );
            case 'duplicates':
                return this.invoices.filter(i =>
                    i.estado === 'rechazado' &&
                    i.error_message === 'ERROR SECUENCIAL REGISTRADO'
                );
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
    this.polling = setInterval(() => {
        this.getInvoices(true); // Pass true to indicate a background poll
    }, 10000); // Poll every 10 seconds
  },
  beforeUnmount() {
    clearInterval(this.polling);
  },
  methods: {
    async getInvoices(isPolling = false) {
      if (!isPolling) {
        this.isLoading = true;
      }
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
      } finally {
        if (!isPolling) {
            this.isLoading = false;
        }
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
        const response = await axios.get(`/api/comprobantes/${claveAcceso}/xml`, {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });

        const xmlContent = response.data.data.xml;
        const blob = new Blob([xmlContent], { type: 'application/xml' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${claveAcceso}.xml`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        console.error('Error downloading XML:', error);
        if (error.response?.status === 409 && error.response?.data?.message?.includes('Comprobante no autorizado')) {
            alert('Descarga no disponible: Este es un comprobante duplicado. Por favor, busque la factura original en la pestaña de "Autorizados" para descargar el archivo.');
        } else {
            alert('No se pudo descargar el archivo XML. Razón: ' + (error.response?.data?.message || 'Error desconocido'));
        }
      }
    },
    async downloadPdf(claveAcceso) {
      try {
        const response = await axios.get(`/api/comprobantes/${claveAcceso}/pdf`, {
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
        if (error.response?.status === 409 && error.response?.data?.message?.includes('Comprobante no autorizado')) {
            alert('Descarga no disponible: Este es un comprobante duplicado. Por favor, busque la factura original en la pestaña de "Autorizados" para descargar el archivo.');
        } else {
            alert('No se pudo descargar el archivo PDF. Razón: ' + (error.response?.data?.message || 'Error desconocido'));
        }
      }
    },
  },
};
</script>
