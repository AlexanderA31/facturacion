<template>
  <div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Dashboard de Facturación</h1>
        <button @click="handleLogout" class="px-4 py-2 font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
            Cerrar Sesión
        </button>
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
</template>

<script>
import SignatureUpload from './SignatureUpload.vue';
import FileUpload from './FileUpload.vue';
import DataTable from './DataTable.vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  components: {
    SignatureUpload,
    FileUpload,
    DataTable,
  },
  props: {
    token: {
        type: String,
        required: true,
    }
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
    handleLogout() {
        this.$emit('logout');
    },
    handleFileParsed(data) {
      this.tableData = data.map(row => ({
        ...row,
        id: Math.random().toString(36).substr(2, 9),
        Estado: 'Pendiente',
        clave_acceso: null,
        errorInfo: null,
      }));
    },
    createInvoicePayload(row) {
      const findValue = (keyToFind) => {
        if (!keyToFind) return undefined;
        const normalizedKey = keyToFind.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        for (const key in row) {
          const normalizedObjKey = key.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
          if (normalizedObjKey === normalizedKey) return row[key];
        }
        return undefined;
      };

      const formatToNumber = (value, decimals = 2) => {
          const num = parseFloat(String(value).replace(',', '.'));
          return isNaN(num) ? 0 : parseFloat(num.toFixed(decimals));
      };

      const cedula = findValue('Cédula');
      const nombres = findValue('Nombres');
      const direccion = findValue('Dirección');
      const codigo = findValue('Código');
      const evento = findValue('Evento');
      const email = findValue('Email');
      const telefono = findValue('Teléfono');
      const precio = formatToNumber(findValue('Precio'));

      if (!cedula || !nombres || !precio || !codigo || !evento) {
        throw new Error('Una o más columnas requeridas (Cédula, Nombres, Precio, Código, Evento) no se encontraron o están vacías en el archivo.');
      }

      // Use 6 decimal places for intermediate calculations to maintain precision
      const totalSinImpuestos = formatToNumber(precio / 1.15, 6);
      const iva = formatToNumber(precio - totalSinImpuestos, 2);

      return {
        // fechaEmision is now set by the server
        tipoIdentificacionComprador: String(cedula).length === 13 ? '04' : '05',
        razonSocialComprador: nombres,
        identificacionComprador: String(cedula),
        direccionComprador: direccion,
        totalSinImpuestos: formatToNumber(totalSinImpuestos, 2),
        totalDescuento: 0,
        totalConImpuestos: [{ codigo: 2, codigoPorcentaje: 4, baseImponible: formatToNumber(totalSinImpuestos, 2), valor: iva }],
        importeTotal: precio,
        pagos: [{ formaPago: '01', total: precio }],
        detalles: [{
          codigoPrincipal: String(codigo),
          descripcion: evento,
          cantidad: 1,
          precioUnitario: formatToNumber(totalSinImpuestos, 2), // Format to 2 decimal places
          descuento: 0,
          precioTotalSinImpuesto: formatToNumber(totalSinImpuestos, 2),
          impuestos: [{ codigo: 2, codigoPorcentaje: 4, tarifa: 15.00, baseImponible: formatToNumber(totalSinImpuestos, 2), valor: iva }],
        }],
        infoAdicional: { email: email, telefono: telefono },
      };
    },
    async startBilling() {
      this.isBilling = true;
      const billingPromises = this.tableData
        .filter(row => row.Estado === 'Pendiente')
        .map(row => {
          return (async () => {
            try {
              this.updateRowStatus(row.id, 'Procesando');
              const payload = this.createInvoicePayload(row);
              if (!payload) return;
              await axios.post(`/api/comprobantes/factura/${this.puntoEmisionId}`, payload, {
                headers: { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' },
              });
            } catch (error) {
              console.error('Billing error for row:', row, error);
              this.updateRowStatus(row.id, 'No Facturado', error.message);
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
      const rowsToUpdate = this.tableData.filter(row => ['Procesando', 'Pendiente'].includes(row.Estado));
      if (rowsToUpdate.length === 0) {
        this.stopPolling();
        return;
      }
      try {
        const response = await axios.get('/api/comprobantes', {
          headers: { 'Authorization': `Bearer ${this.token}` },
          params: { per_page: 50 }
        });
        const comprobantes = response.data.data.data;
        rowsToUpdate.forEach(row => {
          const findValue = (keyToFind) => {
            if (!keyToFind) return undefined;
            const normalizedKey = keyToFind.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            for (const key in row) {
              const normalizedObjKey = key.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
              if (normalizedObjKey === normalizedKey) return row[key];
            }
            return undefined;
          };
          const match = comprobantes.find(c => c.cliente_ruc === findValue('Cédula') && JSON.parse(c.payload)?.detalles[0]?.codigoPrincipal === findValue('Código'));
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
        case 'firmado': return 'Enviado';
        case 'pendiente':
        case 'procesando':
        default: return 'Procesando';
      }
    },
    updateRowFromComprobante(rowId, comprobante) {
      const index = this.tableData.findIndex(r => r.id === rowId);
      if (index !== -1) {
        this.tableData[index].Estado = this.mapBackendStatus(comprobante.estado);
        this.tableData[index].clave_acceso = comprobante.clave_acceso;
        if(comprobante.error_message) this.tableData[index].errorInfo = comprobante.error_message;
      }
    },
    updateRowStatus(rowId, status, errorInfo = null) {
      const index = this.tableData.findIndex(r => r.id === rowId);
      if (index !== -1) {
        this.tableData[index].Estado = status;
        this.tableData[index].errorInfo = errorInfo;
      }
    },
  },
  beforeUnmount() {
    this.stopPolling();
  },
};
</script>
