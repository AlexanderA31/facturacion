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
  watch: {
    currentTab() {
      this.currentPage = 1;
    },
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
      if (this.filteredInvoices.length === 0) {
        this.$emitter.emit('show-alert', { type: 'info', message: 'No hay facturas autorizadas para descargar.' });
        return;
      }

      this.$emitter.emit('show-alert', { type: 'info', message: `Iniciando la descarga de ${this.filteredInvoices.length} facturas. Esto puede tardar un momento...` });

      const zip = new JSZip();
      const downloadPromises = this.filteredInvoices.map(async (invoice) => {
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
