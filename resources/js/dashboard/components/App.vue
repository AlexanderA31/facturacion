<template>
  <div class="min-h-screen bg-gray-100 text-gray-800">
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">

      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900">Dashboard de Facturación Masiva</h1>
        <p class="text-lg text-gray-600 mt-2">Carga, gestiona y emite tus facturas de forma sencilla.</p>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-4 border-b pb-3">Configuración</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <h3 class="text-xl font-semibold mb-3 text-gray-700">Firma Electrónica</h3>
            <SignatureUpload />
          </div>
          <div>
            <h3 class="text-xl font-semibold mb-3 text-gray-700">Cargar Archivo de Datos</h3>
            <FileUpload @file-parsed="handleFileParsed" />
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
            <h2 class="text-2xl font-semibold mb-3 sm:mb-0">Estado de Facturación</h2>
            <button @click="startBilling" :disabled="isBilling || tableData.length === 0"
                    class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 text-white font-medium text-sm leading-tight uppercase rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out disabled:bg-gray-400 disabled:cursor-not-allowed">
                <span v-if="isBilling">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Facturando...
                </span>
                <span v-else>Iniciar Facturación</span>
            </button>
        </div>
        <DataTable :data="tableData" :headers="tableHeaders" />
      </div>

    </div>
  </div>
</template>

<script>
import SignatureUpload from './SignatureUpload.vue';
import FileUpload from './FileUpload.vue';
import DataTable from './DataTable.vue';
import axios from 'axios';

export default {
  name: 'App',
  components: {
    SignatureUpload,
    FileUpload,
    DataTable,
  },
  data() {
    return {
      tableData: [],
      tableHeaders: ['Código', 'Cédula', 'Nombres', 'Dirección', 'Teléfono', 'Email', 'Evento', 'Precio', 'Estado', 'Método de pago'],
      puntoEmisionId: 1,
      isBilling: false,
      pollingIntervalId: null,
    };
  },
  methods: {
    handleFileParsed(data) {
      this.tableData = data.map(row => ({
        ...row,
        id: Math.random().toString(36).substr(2, 9),
        Estado: 'Pendiente',
        clave_acceso: null,
      }));
    },
    createInvoicePayload(row) {
      const precio = parseFloat(row.Precio);
      const totalSinImpuestos = parseFloat((precio / 1.12).toFixed(2));
      const iva = parseFloat((precio - totalSinImpuestos).toFixed(2));

      return {
        fechaEmision: new Date().toISOString().split('T')[0],
        tipoIdentificacionComprador: row.Cédula.length === 13 ? '04' : '05',
        razonSocialComprador: row.Nombres,
        identificacionComprador: row.Cédula,
        direccionComprador: row.Dirección,
        totalSinImpuestos: totalSinImpuestos,
        totalDescuento: 0,
        totalConImpuestos: [{ codigo: 2, codigoPorcentaje: 2, baseImponible: totalSinImpuestos, valor: iva }],
        importeTotal: precio,
        pagos: [{ formaPago: '01', total: precio }],
        detalles: [{
          codigoPrincipal: row.Código,
          descripcion: row.Evento,
          cantidad: 1,
          precioUnitario: totalSinImpuestos,
          descuento: 0,
          precioTotalSinImpuesto: totalSinImpuestos,
          impuestos: [{ codigo: 2, codigoPorcentaje: 2, tarifa: 12.00, baseImponible: totalSinImpuestos, valor: iva }],
        }],
        infoAdicional: { email: row.Email, telefono: row.Teléfono },
      };
    },
    async startBilling() {
      this.isBilling = true;
      const token = localStorage.getItem('jwt_token');
      const billingPromises = this.tableData
        .filter(row => row.Estado === 'Pendiente')
        .map(row => {
          return (async () => {
            try {
              this.updateRowStatus(row.id, 'Procesando');
              const payload = this.createInvoicePayload(row);
              await axios.post(`/api/comprobantes/factura/${this.puntoEmisionId}`, payload, {
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
              });
            } catch (error) {
              console.error('Billing error for row:', row, error);
              this.updateRowStatus(row.id, 'No Facturado');
            }
          })();
        });
      await Promise.all(billingPromises);
      this.isBilling = false;
      this.startPolling();
    },
    startPolling() {
      if (this.pollingIntervalId) clearInterval(this.pollingIntervalId);
      this.pollingIntervalId = setInterval(this.pollInvoiceStatus, 5000);
    },
    stopPolling() {
      if (this.pollingIntervalId) {
        clearInterval(this.pollingIntervalId);
        this.pollingIntervalId = null;
      }
    },
    async pollInvoiceStatus() {
      const token = localStorage.getItem('jwt_token');
      const rowsToUpdate = this.tableData.filter(row => ['Procesando', 'Pendiente'].includes(row.Estado));
      if (rowsToUpdate.length === 0) {
        this.stopPolling();
        return;
      }
      try {
        const response = await axios.get('/api/comprobantes', {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { per_page: 50 }
        });
        const comprobantes = response.data.data.data;
        rowsToUpdate.forEach(row => {
          const match = comprobantes.find(c => c.cliente_ruc === row.Cédula && JSON.parse(c.payload)?.detalles[0]?.codigoPrincipal === row.Código);
          if (match) this.updateRowFromComprobante(row.id, match);
        });
      } catch (error) {
        console.error('Error polling statuses:', error);
      }
    },
    mapBackendStatus(status) {
      switch (status) {
        case 'autorizado': return 'Facturado';
        case 'rechazado': case 'fallido': return 'No Facturado';
        default: return 'Procesando';
      }
    },
    updateRowFromComprobante(rowId, comprobante) {
      const index = this.tableData.findIndex(r => r.id === rowId);
      if (index !== -1) {
        this.tableData[index].Estado = this.mapBackendStatus(comprobante.estado);
        this.tableData[index].clave_acceso = comprobante.clave_acceso;
      }
    },
    updateRowStatus(rowId, status) {
      const index = this.tableData.findIndex(r => r.id === rowId);
      if (index !== -1) this.tableData[index].Estado = status;
    },
  },
  beforeUnmount() {
    this.stopPolling();
  },
};
</script>
