<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Billing Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <h2 class="text-xl font-semibold mb-2">Upload Signature</h2>
        <SignatureUpload />
      </div>
      <div>
        <h2 class="text-xl font-semibold mb-2">Upload File</h2>
        <FileUpload @file-parsed="handleFileParsed" />
      </div>
    </div>
    <div class="mt-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Billing Status</h2>
            <button @click="startBilling" :disabled="isBilling || tableData.length === 0" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400">
                {{ isBilling ? 'Billing in Progress...' : 'Start Billing' }}
            </button>
        </div>
      <DataTable :data="tableData" :headers="tableHeaders" />
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
      puntoEmisionId: 1, // Hardcoded for now
      isBilling: false,
      pollingIntervalId: null,
    };
  },
  methods: {
    handleFileParsed(data) {
      this.tableData = data.map(row => ({
        ...row,
        id: Math.random().toString(36).substr(2, 9), // Unique id for the row
        Estado: 'Pending',
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
        totalConImpuestos: [
          {
            codigo: 2,
            codigoPorcentaje: 2,
            baseImponible: totalSinImpuestos,
            valor: iva,
          },
        ],
        importeTotal: precio,
        pagos: [
          {
            formaPago: '01', // Default to "SIN UTILIZACION DEL SISTEMA FINANCIERO"
            total: precio,
          },
        ],
        detalles: [
          {
            codigoPrincipal: row.Código,
            descripcion: row.Evento,
            cantidad: 1,
            precioUnitario: totalSinImpuestos,
            descuento: 0,
            precioTotalSinImpuesto: totalSinImpuestos,
            impuestos: [
              {
                codigo: 2,
                codigoPorcentaje: 2,
                tarifa: 12.00,
                baseImponible: totalSinImpuestos,
                valor: iva,
              },
            ],
          },
        ],
        infoAdicional: {
          email: row.Email,
          telefono: row.Teléfono,
        },
      };
    },
    async startBilling() {
      this.isBilling = true;
      const token = localStorage.getItem('jwt_token');

      const billingPromises = this.tableData
        .filter(row => row.Estado === 'Pending')
        .map(row => {
          return (async () => {
            try {
              this.updateRowStatus(row.id, 'Processing');
              const payload = this.createInvoicePayload(row);
              await axios.post(`/api/comprobantes/factura/${this.puntoEmisionId}`, payload, {
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
              });
            } catch (error) {
              console.error('Billing error for row:', row, error);
              this.updateRowStatus(row.id, 'Not Billed');
            }
          })();
        });

      await Promise.all(billingPromises);

      this.isBilling = false;
      this.startPolling();
    },
    startPolling() {
      if (this.pollingIntervalId) {
        clearInterval(this.pollingIntervalId);
      }
      this.pollingIntervalId = setInterval(this.pollInvoiceStatus, 5000); // Poll every 5 seconds
    },
    stopPolling() {
      if (this.pollingIntervalId) {
        clearInterval(this.pollingIntervalId);
        this.pollingIntervalId = null;
      }
    },
    async pollInvoiceStatus() {
      const token = localStorage.getItem('jwt_token');
      const rowsToUpdate = this.tableData.filter(row => ['Processing', 'Pending'].includes(row.Estado));

      if (rowsToUpdate.length === 0) {
        this.stopPolling();
        return;
      }

      try {
        const response = await axios.get('/api/comprobantes', {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { per_page: 50 } // Fetch more to increase chance of finding matches
        });

        const comprobantes = response.data.data.data;

        rowsToUpdate.forEach(row => {
          const match = comprobantes.find(c =>
            c.cliente_ruc === row.Cédula &&
            JSON.parse(c.payload)?.detalles[0]?.codigoPrincipal === row.Código
          );

          if (match) {
            this.updateRowFromComprobante(row.id, match);
          }
        });

      } catch (error) {
        console.error('Error polling statuses:', error);
      }
    },
    mapBackendStatus(status) {
        switch (status) {
            case 'autorizado':
                return 'Billed';
            case 'rechazado':
            case 'fallido':
                return 'Not Billed';
            case 'pendiente':
            case 'procesando':
            case 'firmado':
            default:
                return 'Processing';
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
      if (index !== -1) {
        this.tableData[index].Estado = status;
      }
    },
  },
  beforeUnmount() {
    this.stopPolling();
  },
};
</script>
