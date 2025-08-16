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
        <a href="#" @click.prevent="currentDashboardView = 'corrective'"
            :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentDashboardView === 'corrective' ? 'bg-gray-700' : 'hover:bg-gray-700']">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Facturación Correctiva
        </a>
        <a href="#" @click.prevent="currentDashboardView = 'configuration'"
            :class="['flex items-center px-4 py-2 rounded-md transition-colors', currentDashboardView === 'configuration' ? 'bg-gray-700' : 'hover:bg-gray-700']">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Configuración
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
              <FileUpload @file-parsed="handleFileParsed" @parsing-start="isParsingFile = true" @parsing-complete="isParsingFile = false" />
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="establecimiento-select" class="block text-sm font-medium text-gray-700">Establecimiento</label>
                    <select id="establecimiento-select" v-model="selectedEstablecimientoId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option :value="null" disabled>Seleccione un establecimiento</option>
                        <option v-for="est in establecimientos" :key="est.id" :value="est.id">
                            {{ est.codigo }} - {{ est.nombre }}
                        </option>
                    </select>
                </div>
                <div>
                    <label for="punto-emision-select" class="block text-sm font-medium text-gray-700">Punto de Emisión</label>
                    <select id="punto-emision-select" v-model="selectedPuntoEmisionId" :disabled="!selectedEstablecimientoId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md disabled:bg-gray-200">
                        <option :value="null" disabled>Seleccione un punto de emisión</option>
                        <option v-for="pto in availablePuntosEmision" :key="pto.id" :value="pto.id">
                            {{ pto.codigo }} - {{ pto.nombre }}
                        </option>
                    </select>
                </div>
            </div>
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
                <!-- Start Button -->
                <BaseButton v-if="!isBilling" @click="startBilling" :disabled="tableData.length === 0 || !selectedPuntoEmisionId" variant="primary">
                    <template #icon>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </template>
                    Iniciar Facturación
                </BaseButton>
                <!-- Billing In Progress Controls -->
                <div v-if="isBilling" class="flex items-center space-x-4">
                    <div class="flex items-center text-lg font-medium text-gray-700">
                        <svg class="animate-spin mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Facturando... ({{ currentIndex + 1 }} / {{ rowsToBill.length }})</span>
                    </div>
                    <BaseButton v-if="!isPaused" @click="pauseBilling" variant="warning">Pausar</BaseButton>
                    <BaseButton v-if="isPaused" @click="resumeBilling" variant="success">Reanudar</BaseButton>
                    <BaseButton @click="cancelBilling" variant="danger">Cancelar</BaseButton>
                </div>
              </div>
            </div>
            <TableSkeleton v-if="isParsingFile" />
            <template v-else>
              <DataTable :data="paginatedPendingRows" :headers="tableHeaders" @toggle-expansion="toggleRowExpansion" />
              <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
            </template>
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

        <!-- Corrective Billing Section -->
        <div v-if="currentDashboardView === 'corrective'">
          <CorrectiveBilling />
        </div>

        <!-- Configuration Section -->
        <div v-if="currentDashboardView === 'configuration'">
          <Configuration />
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
import CorrectiveBilling from './CorrectiveBilling.vue';
import Pagination from './Pagination.vue';
import BaseButton from './BaseButton.vue';
import Configuration from './Configuration.vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  components: {
    SignatureUpload,
    FileUpload,
    DataTable,
    StatusChecker,
    MyInvoices,
    CorrectiveBilling,
    Pagination,
    BaseButton,
    Configuration,
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
      isBilling: false,
      pollingIntervalId: null,
      establecimientos: [],
      puntosEmision: [],
      selectedEstablecimientoId: null,
      selectedPuntoEmisionId: null,
      filterStatus: 'Todos',
      currentPage: 1,
      itemsPerPage: 10,
      isParsingFile: false,
      isStopping: false, // To be replaced by isPaused
      isPaused: false,
      currentIndex: 0,
      rowsToBill: [],
    };
  },
  computed: {
    availablePuntosEmision() {
        if (!this.selectedEstablecimientoId) {
            return [];
        }
        return this.puntosEmision.filter(p => p.establecimiento_id === this.selectedEstablecimientoId);
    },
    pendingRows() {
        return this.tableData.filter(row => row.Estado === 'Pendiente' || row.Estado === 'Procesando');
    },
    paginatedPendingRows() {
      // This computed property will be used for the main table
      const data = this.pendingRows.filter(row => this.filterStatus === 'Todos' || row.Estado === this.filterStatus);
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return data.slice(start, end);
    },
    totalPages() {
      const data = this.pendingRows.filter(row => this.filterStatus === 'Todos' || row.Estado === this.filterStatus);
      return Math.ceil(data.length / this.itemsPerPage);
    },
  },
  watch: {
    tableData: {
        handler() {
            this.saveState();
        },
        deep: true,
    },
    isBilling(newValue) {
        this.saveState();
    },
    selectedEstablecimientoId() {
        // Reset punto de emision when establishment changes
        this.selectedPuntoEmisionId = null;
    }
  },
  mounted() {
    this.loadState();
    if (this.isBilling) {
        // If billing was in progress, resume it.
        this.startBilling();
    }
    this.fetchEstablecimientos();
    this.fetchPuntosEmision();
  },
  methods: {
    async fetchEstablecimientos() {
        try {
            const response = await axios.get('/api/establecimientos', {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            this.establecimientos = response.data.data.data;
        } catch (error) {
            console.error('Error fetching establecimientos:', error);
        }
    },
    async fetchPuntosEmision() {
        try {
            const response = await axios.get('/api/puntos-emision', {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            this.puntosEmision = response.data.data.data;
        } catch (error) {
            console.error('Error fetching puntos de emision:', error);
        }
    },
    saveState() {
        const state = {
            tableData: this.tableData,
            isBilling: this.isBilling,
        };
        localStorage.setItem('bulkBillingState', JSON.stringify(state));
    },
    loadState() {
        const savedState = localStorage.getItem('bulkBillingState');
        if (savedState) {
            const state = JSON.parse(savedState);
            this.tableData = state.tableData || [];
            this.isBilling = state.isBilling || false;
        }
    },
    clearState() {
        localStorage.removeItem('bulkBillingState');
        this.tableData = [];
        this.isBilling = false;
    },
    toggleRowExpansion(rowId) {
      const row = this.tableData.find(r => r.id === rowId);
      if (row) {
        row.isExpanded = !row.isExpanded;
      }
    },
    handleLogout() {
        this.clearState();
        this.$emit('logout');
    },
    handleFileParsed(data) {
      this.clearState(); // Clear any old state before loading new data
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
    addFailedRowToCorrective(row, errorMessage) {
        row.Estado = 'No Facturado';
        row.errorInfo = errorMessage;

        const correctiveData = JSON.parse(localStorage.getItem('correctiveBillingData') || '[]');
        correctiveData.push(row);
        localStorage.setItem('correctiveBillingData', JSON.stringify(correctiveData));

        // Remove from the main table
        this.tableData = this.tableData.filter(item => item.id !== row.id);

        // Notify other components
        window.dispatchEvent(new Event('corrective-billing-update'));
    },
    async processSingleInvoice(row) {
      try {
        this.updateRowStatus(row.id, 'Procesando', null); // Clear previous errors
        const payload = this.createInvoicePayload(row);

        await axios.post(`/api/comprobantes/factura/${this.selectedPuntoEmisionId}`, payload, {
          headers: { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' },
        });

        // On successful POST, the backend job is queued.
        // Remove the row from the pending/failed tables. Its status will be updated
        // via polling in the 'My Invoices' tab.
        this.tableData = this.tableData.filter(item => item.id !== row.id);

      } catch (error) {
        const errorMessage = error.response?.data?.message || error.message;
        if (error.message.includes('columnas requeridas')) {
          this.addFailedRowToCorrective(row, 'Datos incompletos en la fila.');
        } else if (errorMessage.includes('ERROR SECUENCIAL REGISTRADO')) {
          // This error means the invoice was already processed successfully. Remove it.
          this.tableData = this.tableData.filter(item => item.id !== row.id);
        } else {
          this.addFailedRowToCorrective(row, errorMessage);
        }
      }
    },
    pauseBilling() {
        this.isPaused = true;
    },
    resumeBilling() {
        this.isPaused = false;
        this.runBillingProcess();
    },
    cancelBilling() {
        this.isBilling = false;
        this.isPaused = false;
        this.rowsToBill = [];
        this.currentIndex = 0;
        // Revert any 'Procesando' rows back to 'Pendiente'
        this.tableData.forEach(row => {
            if (row.Estado === 'Procesando') {
                this.updateRowStatus(row.id, 'Pendiente');
            }
        });
    },
    startBilling() {
      this.rowsToBill = [...this.tableData.filter(row => row.Estado === 'Pendiente')];
      if (this.rowsToBill.length === 0) return;

      this.isBilling = true;
      this.isPaused = false;
      this.currentIndex = 0;

      this.runBillingProcess();
    },
    async runBillingProcess() {
        if (this.isPaused || !this.isBilling) {
            return;
        }

        if (this.currentIndex >= this.rowsToBill.length) {
            // Finished
            this.isBilling = false;
            if (!this.pollingIntervalId) {
                this.startPolling();
            }
            return;
        }

        const row = this.rowsToBill[this.currentIndex];
        await this.processSingleInvoice(row);

        this.currentIndex++;

        requestAnimationFrame(this.runBillingProcess);
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
    handleSaveRow(updatedRow) {
      const index = this.tableData.findIndex(row => row.id === updatedRow.id);
      if (index !== -1) {
        this.tableData.splice(index, 1, updatedRow);
      }
    },
  },
  beforeUnmount() {
    this.stopPolling();
  },
};
</script>
