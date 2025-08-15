<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Mis Comprobantes</h2>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <DataTable :data="invoices" :headers="headers" :currentPage="1" :totalPages="1" @download-xml="downloadXml" />
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';

export default {
  name: 'MyInvoices',
  components: {
    DataTable,
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
      headers: ['Clave de Acceso', 'Estado', 'Fecha', 'Acciones'],
    };
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
        this.invoices = response.data.data.data;
      } catch (error) {
        console.error('Error fetching invoices:', error);
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
  },
};
</script>
