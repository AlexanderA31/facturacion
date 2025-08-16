<template>
  <div>
    <!-- Sidebar -->
    <div :class="['fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white transform transition duration-300 ease-in-out', isSidebarOpen ? 'translate-x-0' : '-translate-x-full']">
      <div class="px-8 py-6 text-center">
        <h2 class="text-2xl font-semibold">Dashboard</h2>
      </div>
      <nav class="flex-1 px-4 py-2 space-y-2">
        <a href="#" @click.prevent="currentDashboardView = 'billing'"
           :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentDashboardView === 'billing' ? 'bg-gray-700' : 'hover:bg-gray-700']">
          <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          Facturación Masiva
        </a>
        <a href="#" @click.prevent="currentDashboardView = 'status'"
           :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentDashboardView === 'status' ? 'bg-gray-700' : 'hover:bg-gray-700']">
          <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          Estado del Sistema
        </a>
        <a href="#" @click.prevent="currentDashboardView = 'my-invoices'"
           :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentDashboardView === 'my-invoices' ? 'bg-gray-700' : 'hover:bg-gray-700']">
          <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Mis Comprobantes
        </a>
      </nav>
      <div class="px-4 py-4">
        <button @click="handleLogout" class="w-full flex items-center justify-center px-4 py-2 font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
          Cerrar Sesión
        </button>
      </div>
    </div>

    <div class="relative z-10 flex flex-col flex-1" :class="{'md:ml-64': isSidebarOpen}">
      <!-- Header -->
      <header class="flex justify-between items-center p-4 bg-white border-b-2 border-gray-200">
        <button @click="isSidebarOpen = !isSidebarOpen" class="text-gray-500 focus:outline-none">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="text-xl font-semibold">Facturación</h1>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8">
        <!-- Billing Section -->
        <div v-if="currentDashboardView === 'billing'">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-6">
              <h3 class="text-xl font-bold mb-4 text-gray-800 border-b pb-3">Firma Electrónica</h3>
              <p class="text-gray-600 mb-4">Cargue su certificado de firma electrónica para poder emitir comprobantes.</p>
              <SignatureUpload />
            </div>
            <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
              <h3 class="text-xl font-bold mb-4 text-gray-800 border-b pb-3">Cargar Archivo de Datos</h3>
              <p class="text-gray-600 mb-4">Seleccione un archivo de Excel (.xlsx, .xls) con los datos de los clientes y las facturas a emitir.</p>
              <FileUpload @file-parsed="handleFileParsed" />
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
              <div>
                <h2 class="text-2xl font-bold text-gray-800">Facturación Masiva</h2>
                <p class="text-gray-600">Revise los datos cargados y proceda a emitir las facturas.</p>
              </div>
              <div class="flex items-center space-x-4">
                <div class="relative">
                  <label for="status-filter" class="sr-only">Filtrar por estado</label>
                  <select id="status-filter" v-model="filterStatus" class="appearance-none mt-4 sm:mt-0 block w-full sm:w-auto pl-4 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <option value="Todos">Todos los Estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Procesando">Procesando</option>
                    <option value="Facturado">Facturado</option>
                    <option value="No Facturado">No Facturado</option>
                    <option value="Enviado">Enviado</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                  </div>
                </div>
                <button @click="startBilling" :disabled="isBilling || tableData.length === 0"
                        class="w-full sm:w-auto mt-4 sm:mt-0 px-6 py-3 bg-blue-600 text-white font-medium text-lg leading-tight uppercase rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center">
                  <span v-if="isBilling">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Facturando...
                  </span>
                  <span v-else class="flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Iniciar Facturación
                  </span>
                </button>
              </div>
            </div>
            <DataTable :data="paginatedData" :headers="tableHeaders" @toggle-expansion="toggleRowExpansion" />
            <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
          </div>
        </div>

        <!-- Status Section -->
        <div v-if="currentDashboardView === 'status'">
          <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold mb-4 text-gray-800 border-b pb-3">Estado del Sistema</h3>
            <p class="text-gray-600 mb-4">Verifique el estado de los servicios del SRI y la configuración de su cuenta.</p>
            <StatusChecker :token="token" />
          </div>
        </div>

        <!-- My Invoices Section -->
        <div v-if="currentDashboardView === 'my-invoices'">
          <MyInvoices :token="token" />
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import SignatureUpload from './SignatureUpload.vue';
import FileUpload from './FileUpload.vue';
import DataTable from './DataTable.vue';
import StatusChecker from './StatusChecker.vue';
import MyInvoices from './MyInvoices.vue';
import Pagination from './Pagination.vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  components: {
    SignatureUpload,
    FileUpload,
    DataTable,
    StatusChecker,
    MyInvoices,
    Pagination,
  },
  props: {
    token: {
        type: String,
        required: true,
    }
  },
  data() {
    return {
      isSidebarOpen: false,
      currentDashboardView: 'billing',
      tableData: [],
      tableHeaders: [
        { text: 'Nombres', value: 'Nombres' },
        { text: 'Cédula', value: 'Cédula' },
        { text: 'Evento', value: 'Evento' },
        { text: 'Precio', value: 'Precio' },
        { text: 'Estado', value: 'Estado' },
      ],
      puntoEmisionId: 1,
      isBilling: false,
      pollingIntervalId: null,
      filterStatus: 'Todos',
      currentPage: 1,
      itemsPerPage: 10,
    };
  },
  computed: {
    filteredData() {
      if (this.filterStatus === 'Todos') {
        return this.tableData;
      }
      return this.tableData.filter(row => row.Estado === this.filterStatus);
    },
    totalPages() {
      return Math.ceil(this.filteredData.length / this.itemsPerPage);
    },
    paginatedData() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredData.slice(start, end);
    },
  },
  methods: {
    toggleRowExpansion(rowId) {
      const row = this.tableData.find(r => r.id === rowId);
      if (row) {
        row.isExpanded = !row.isExpanded;
      }
    },
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
        isExpanded: false,
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
      // We operate on a copy to avoid issues with iterating and modifying the same array
      const rowsToBill = [...this.tableData.filter(row => row.Estado === 'Pendiente')];

      for (const row of rowsToBill) {
        try {
          this.updateRowStatus(row.id, 'Procesando');
          const payload = this.createInvoicePayload(row);

          await axios.post(`/api/comprobantes/factura/${this.puntoEmisionId}`, payload, {
            headers: { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' },
          });

          // If successful, remove the row from the main data array
          this.tableData = this.tableData.filter(item => item.id !== row.id);

        } catch (error) {
            if (error.message.includes('columnas requeridas')) {
                console.warn(`Skipping row due to incomplete data: ${error.message}`, row);
                this.updateRowStatus(row.id, 'Pendiente', 'Datos incompletos');
            } else {
                console.error('Billing error for row:', row, error);
                this.updateRowStatus(row.id, 'No Facturado', error.response?.data?.message || error.message);
            }
        }
      }

      this.isBilling = false;
      // Polling is still useful for the "My Invoices" tab
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
