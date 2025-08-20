<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-800">Mis Comprobantes</h2>
        <div class="flex items-center space-x-2">
            <!-- Download Button -->
            <div v-if="currentTab === 'authorized'" class="relative inline-block text-left">
                <div>
                    <button type="button" @click="isDropdownOpen = !isDropdownOpen" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500" id="options-menu" aria-haspopup="true" aria-expanded="true">
                        Descargar todo
                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div v-if="isDropdownOpen" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                        <a href="#" @click.prevent="downloadAll('xml')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Descargar todo (XML)</a>
                        <a href="#" @click.prevent="downloadAll('pdf')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Descargar todo (PDF)</a>
                    </div>
                </div>
            </div>
            <RefreshButton :is-loading="isLoading" @click="getInvoices(false)" />
        </div>
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

    <div class="flex justify-end my-4">
        <div class="relative w-full max-w-xs">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="text" v-model="searchQuery" placeholder="Buscar..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg">
      <TableSkeleton v-if="isLoading" />
      <div v-else class="flex flex-col">
          <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
              <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                  <DataTable :data="paginatedInvoices" :headers="headers" :sort-key="sortKey" :sort-order="sortOrder" @sort="sortBy" @download-xml="downloadXml" @download-pdf="downloadPdf" @toggle-error-expansion="toggleErrorExpansion" />
              </div>
          </div>
          <div v-if="totalPages > 1" class="py-4 px-6 flex justify-center">
              <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
          </div>
      </div>
    </div>
  </div>
</template>

<script>
import JSZip from 'jszip';
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
      isDropdownOpen: false,
      searchQuery: '',
      sortKey: 'fecha_emision',
      sortOrder: 'desc',
    };
  },
  computed: {
    headers() {
        const baseHeaders = [
            { text: 'Número de Factura', value: 'numero_factura' },
            { text: 'Cliente', value: 'cliente' },
            { text: 'Fecha de Emisión', value: 'fecha_emision' },
            { text: 'Valor', value: 'valor' },
            { text: 'Estado', value: 'estado' },
        ];

        let finalHeaders = [...baseHeaders];

        if (this.currentTab === 'authorized') {
            finalHeaders.push({ text: 'Fecha de Autorización', value: 'fecha_autorizacion' });
        }

        if (this.currentTab === 'unauthorized') {
            finalHeaders.push({ text: 'Mensaje de Error', value: 'error_message' });
        }

        if (this.currentTab === 'all' || this.currentTab === 'authorized') {
            finalHeaders.push({ text: 'Acciones', value: 'acciones' });
        }

        return finalHeaders;
    },
    processedInvoices() {
      let processed = [...this.invoices];

      // 1. Filter by tab
      switch (this.currentTab) {
        case 'authorized':
          processed = processed.filter(i => i.estado === 'autorizado');
          break;
        case 'pending':
          processed = processed.filter(i => ['pendiente', 'procesando', 'firmado'].includes(i.estado));
          break;
        case 'unauthorized':
          processed = processed.filter(i =>
            ['rechazado', 'fallido'].includes(i.estado) &&
            i.error_message !== 'ERROR SECUENCIAL REGISTRADO'
          );
          break;
        case 'duplicates':
          processed = processed.filter(i =>
            i.estado === 'rechazado' &&
            i.error_message === 'ERROR SECUENCIAL REGISTRADO'
          );
          break;
        // 'all' tab doesn't need filtering
      }

      // 2. Filter by search query
      if (this.searchQuery && this.searchQuery.trim() !== '') {
        const lowerCaseQuery = this.searchQuery.trim().toLowerCase();
        processed = processed.filter(invoice => {
          const numeroFactura = (invoice.numero_factura || '').toLowerCase();
          const cliente = (invoice.cliente || '').toLowerCase();
          const valor = String(invoice.valor || '').toLowerCase();
          return numeroFactura.includes(lowerCaseQuery) || cliente.includes(lowerCaseQuery) || valor.includes(lowerCaseQuery);
        });
      }

      // 3. Sort
      if (this.sortKey) {
        processed.sort((a, b) => {
          let valA = a[this.sortKey];
          let valB = b[this.sortKey];

          // Simple comparison for numbers and strings
          if (valA < valB) return this.sortOrder === 'asc' ? -1 : 1;
          if (valA > valB) return this.sortOrder === 'asc' ? 1 : -1;
          return 0;
        });
      }

      return processed;
    },
    totalPages() {
      if (!this.processedInvoices) return 1;
      return Math.ceil(this.processedInvoices.length / this.itemsPerPage);
    },
    paginatedInvoices() {
      if (!this.processedInvoices) return [];
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.processedInvoices.slice(start, end);
    },
  },
  mounted() {
    this.getInvoices();
    this.polling = setInterval(() => {
        this.getInvoices(true); // Pass true to indicate a background poll
    }, 10000); // Poll every 10 seconds
  },
  watch: {
    currentTab() {
      this.currentPage = 1;
    },
    searchQuery() {
      this.currentPage = 1;
    },
  },
  beforeUnmount() {
    clearInterval(this.polling);
  },
  methods: {
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortOrder = 'asc';
      }
    },
    formatDateTime(dateTimeString) {
      if (!dateTimeString) return 'N/A';
      const date = new Date(dateTimeString);
      if (isNaN(date)) return dateTimeString; // Return original if invalid

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');

      return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    async getInvoices(isPolling = false) {
      if (!isPolling) {
        this.isLoading = true;
      }
      try {
        const response = await axios.get('/api/comprobantes', {
          headers: { 'Authorization': `Bearer ${this.token}` },
          params: { per_page: 100 } // Fetch a good number of invoices
        });
      
        this.invoices = response.data.data.data.map(invoice => {
          let payload = {};
          try {
            if (typeof invoice.payload === 'string') {
                payload = JSON.parse(invoice.payload);
            } else {
                payload = invoice.payload || {};
            }
          } catch (e) {
            console.error('Error parsing invoice payload:', e);
            payload = {}; // Ensure payload is an object on error
          }

          return {
            ...invoice,
            fecha_emision: this.formatDateTime(invoice.fecha_emision),
            numero_factura: `${invoice.establecimiento}-${invoice.punto_emision}-${invoice.secuencial}`,
            cliente: payload.razonSocialComprador || 'N/A',
            valor: payload.importeTotal || 0,
            isErrorExpanded: false,
          };
        });
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
            this.$emitter.emit('show-alert', { type: 'error', message: 'Descarga no disponible: Comprobante duplicado.' });
        } else {
            const message = error.response?.data?.message || 'Error desconocido';
            this.$emitter.emit('show-alert', { type: 'error', message: `No se pudo descargar el XML: ${message}` });
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

        // Get filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let fileName = `${claveAcceso}.pdf`; // Default filename
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="(.+)"/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.download = fileName;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        console.error('Error downloading PDF:', error);
        if (error.response?.status === 409 && error.response?.data?.message?.includes('Comprobante no autorizado')) {
            this.$emitter.emit('show-alert', { type: 'error', message: 'Descarga no disponible: Comprobante duplicado.' });
        } else {
            const message = error.response?.data?.message || 'Error desconocido';
            this.$emitter.emit('show-alert', { type: 'error', message: `No se pudo descargar el PDF: ${message}` });
        }
      }
    },
    async downloadAll(format) {
      this.isDropdownOpen = false;
      if (this.processedInvoices.length === 0) {
        this.$emitter.emit('show-alert', { type: 'info', message: 'No hay facturas autorizadas para descargar.' });
        return;
      }

      this.$emitter.emit('show-alert', { type: 'info', message: `Iniciando la descarga de ${this.processedInvoices.length} facturas. Esto puede tardar un momento...` });

      const zip = new JSZip();
      const downloadPromises = this.processedInvoices.map(async (invoice) => {
        try {
          const url = `/api/comprobantes/${invoice.clave_acceso}/${format}`;
          const response = await axios.get(url, {
            headers: { 'Authorization': `Bearer ${this.token}` },
            responseType: format === 'pdf' ? 'blob' : 'json', // 'json' for xml, 'blob' for pdf
          });

          let fileContent;
          let fileName;

          if (format === 'xml') {
            fileContent = response.data.data.xml;
            fileName = `${invoice.clave_acceso}.xml`;
          } else { // pdf
            fileContent = response.data;
            const contentDisposition = response.headers['content-disposition'];
            const fileNameMatch = contentDisposition ? contentDisposition.match(/filename="(.+)"/) : null;
            fileName = (fileNameMatch && fileNameMatch.length === 2) ? fileNameMatch[1] : `${invoice.clave_acceso}.pdf`;
          }

          zip.file(fileName, fileContent);
        } catch (error) {
          console.error(`Error descargando ${invoice.clave_acceso}.${format}:`, error);
          this.$emitter.emit('show-alert', { type: 'error', message: `Error al descargar ${invoice.numero_factura}` });
        }
      });

      try {
        await Promise.all(downloadPromises);
        if (Object.keys(zip.files).length === 0) {
            this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudo descargar ninguna factura.' });
            return;
        }
        const zipBlob = await zip.generateAsync({ type: 'blob' });

        const link = document.createElement('a');
        link.href = URL.createObjectURL(zipBlob);
        link.download = `facturas_autorizadas.zip`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.$emitter.emit('show-alert', { type: 'success', message: 'Descarga completada.' });
      } catch (error) {
        console.error('Error generando el archivo ZIP:', error);
        this.$emitter.emit('show-alert', { type: 'error', message: 'Ocurrió un error al generar el archivo ZIP.' });
      }
    },
  },
};
</script>
