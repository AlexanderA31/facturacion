<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-800">Facturación Correctiva</h2>
      <div class="flex space-x-2">
        <RefreshButton :is-loading="isLoading" @click="loadState" />
        <BaseButton @click="exportToExcel" variant="secondary" :disabled="failedRows.length === 0">
            <template #icon>
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.5 4A1.5 1.5 0 014 2.5h12A1.5 1.5 0 0117.5 4v12A1.5 1.5 0 0116 17.5H4A1.5 1.5 0 012.5 16V4zM4 4v12h12V4H4z" />
                    <path d="M7.823 6.643a.5.5 0 01.714 0l1.29 1.434a.5.5 0 01-.357.823H9.5v3.5a.5.5 0 01-1 0v-3.5H7.177a.5.5 0 01-.357-.823l1.003-1.11z" />
                    <path d="M12.177 6.643a.5.5 0 01.714 0l1.29 1.434a.5.5 0 01-.357.823h-1.29v3.5a.5.5 0 01-1 0v-3.5h-1.29a.5.5 0 01-.357-.823l1.003-1.11z" />
                </svg>
            </template>
            Exportar a Excel
        </BaseButton>
      </div>
    </div>
    <p class="text-gray-600 mb-6">Aquí puede ver las facturas que fallaron durante el proceso masivo y necesitan corrección. Edite los datos necesarios y vuelva a procesarlas.</p>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <!-- Billing controls will go here -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
              <BaseSelect
                id="corr-establecimiento-select"
                label="Establecimiento"
                v-model="selectedEstablecimientoId"
                :options="establecimientoOptions"
                placeholder="Seleccione un establecimiento"
              />
          </div>
          <div>
              <BaseSelect
                id="corr-punto-emision-select"
                label="Punto de Emisión"
                v-model="selectedPuntoEmisionId"
                :options="puntoEmisionOptions"
                :disabled="!selectedEstablecimientoId"
                placeholder="Seleccione un punto de emisión"
              />
          </div>
      </div>
      <div class="mb-4 flex justify-end">
        <!-- Start Button -->
        <BaseButton v-if="!isBilling" @click="startBilling" :disabled="failedRows.length === 0 || !selectedPuntoEmisionId" variant="success">
            <template #icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </template>
            Facturar Corregidas
        </BaseButton>
        <!-- Billing In Progress Controls -->
        <div v-if="isBilling" class="flex items-center space-x-4">
            <div class="flex items-center text-lg font-medium text-gray-700">
                <svg class="animate-spin mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Procesando... ({{ currentIndex + 1 }} / {{ rowsToBill.length }})</span>
            </div>
            <BaseButton v-if="!isPaused" @click="pauseBilling" variant="warning">Pausar</BaseButton>
            <BaseButton v-if="isPaused" @click="resumeBilling" variant="success">Reanudar</BaseButton>
            <BaseButton @click="cancelBilling" variant="danger">Cancelar</BaseButton>
        </div>
      </div>

      <!-- Data table for failed rows -->
      <TableSkeleton v-if="isLoading" />
      <div v-else>
        <DataTable
          :data="paginatedRows"
          :headers="tableHeaders"
          :showEditButton="false"
          @open-edit-modal="openEditModal"
          @toggle-expansion="toggleRowExpansion"
        >
            <template #cell(acciones)="{ row }">
                <div class="space-x-2 text-center">
                    <button @click="openEditModal(row)" title="Editar" class="p-1 text-yellow-600 hover:text-yellow-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button @click="deleteRow(row)" title="Eliminar" class="p-1 text-red-600 hover:text-red-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </template>
        </DataTable>
        <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
      </div>
    </div>

    <EditInvoiceModal
        :show="isModalVisible"
        :rowData="selectedRowForEdit"
        :is-sidebar-open="isSidebarOpen"
        @close="closeEditModal"
        @save="saveEditedRow"
    />
  </div>
</template>

<script>
import DataTable from './DataTable.vue';
import Pagination from './Pagination.vue';
import BaseButton from './BaseButton.vue';
import EditInvoiceModal from './EditInvoiceModal.vue';
import RefreshButton from './RefreshButton.vue';
import BaseSelect from './BaseSelect.vue';
import axios from 'axios';
import * as XLSX from 'xlsx';
import { parsePaymentMethods } from '../utils/paymentMethods.js';

export default {
  name: 'CorrectiveBilling',
  props: {
    isSidebarOpen: {
      type: Boolean,
      default: false,
    }
  },
  components: {
    DataTable,
    Pagination,
    BaseButton,
    EditInvoiceModal,
    RefreshButton,
    BaseSelect,
  },
  data() {
    return {
      userProfile: {
        tipo_impuesto: '2',
        codigo_porcentaje_iva: '2',
      },
      failedRows: [],
      currentPage: 1,
      itemsPerPage: 10,
      isModalVisible: false,
      selectedRowForEdit: null,
      tableHeaders: [
        { text: 'Nombres', value: 'Nombres' },
        { text: 'Cédula', value: 'Cédula' },
        { text: 'Evento', value: 'Evento' },
        { text: 'Precio', value: 'Precio' },
        { text: 'Estado', value: 'Estado' },
        { text: 'Error', value: 'errorInfo' },
        { text: 'Acciones', value: 'acciones' },
      ],
      isBilling: false,
      isPaused: false,
      currentIndex: 0,
      rowsToBill: [],
      token: localStorage.getItem('jwt_token'),
      establecimientos: [],
      puntosEmision: [],
      selectedEstablecimientoId: null,
      selectedPuntoEmisionId: null,
      isLoading: false,
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.failedRows.length / this.itemsPerPage);
    },
    paginatedRows() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.failedRows.slice(start, end);
    },
    establecimientoOptions() {
      return this.establecimientos.map(est => ({
        value: est.id,
        text: `${est.numero} - ${est.nombre}`,
      }));
    },
    puntoEmisionOptions() {
      if (!this.selectedEstablecimientoId) {
        return [];
      }
      return this.puntosEmision
        .filter(p => p.establecimiento_id == this.selectedEstablecimientoId)
        .map(pto => ({
          value: pto.id,
          text: `${pto.numero} - ${pto.nombre}`,
        }));
    },
  },
  watch: {
    failedRows: {
        handler() {
            this.saveState();
        },
        deep: true,
    },
    selectedEstablecimientoId() {
        this.selectedPuntoEmisionId = null;
    }
  },
  mounted() {
    this.loadState();
    window.addEventListener('corrective-billing-update', this.loadState);
    this.fetchUserProfile();
    this.fetchEstablecimientos();
    this.fetchPuntosEmision();
  },
  beforeUnmount() {
    window.removeEventListener('corrective-billing-update', this.loadState);
  },
  methods: {
    getTarifaFromCodigoPorcentaje(codigo) {
        const map = {
            '0': 0,
            '2': 12,
            '3': 14,
            '6': 0,
            '7': 0,
            '8': 5,
            '9': 15,
        };
        return map[codigo] || 0;
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api/profile', {
          headers: { 'Authorization': `Bearer ${this.token}` },
        });
        this.userProfile = response.data.data;
      } catch (error) {
        console.error('Error fetching user profile:', error);
        this.userProfile = { tipo_impuesto: '2', codigo_porcentaje_iva: '2' };
      }
    },
    async fetchEstablecimientos() {
        try {
            const response = await axios.get('/api/establecimientos', {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            // The API returns a paginated response, the items are in the `data` property
            this.establecimientos = response.data.data.data;
        } catch (error) {
            console.error('Error fetching establecimientos:', error);
            this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudieron cargar los establecimientos.' });
        }
    },
    async fetchPuntosEmision() {
        try {
            const response = await axios.get('/api/puntos-emision', {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
            // The API returns a paginated response, the items are in the `data` property
            this.puntosEmision = response.data.data.data;
        } catch (error) {
            console.error('Error fetching puntos de emision:', error);
            this.$emitter.emit('show-alert', { type: 'error', message: 'No se pudieron cargar los puntos de emisión.' });
        }
    },
    openEditModal(row) {
        this.selectedRowForEdit = row;
        this.isModalVisible = true;
    },
    closeEditModal() {
        this.isModalVisible = false;
        this.selectedRowForEdit = null;
    },
    loadState() {
      this.isLoading = true;
      // Use a short timeout to ensure the loading state is visible for local operations
      new Promise(resolve => setTimeout(resolve, 200)).then(() => {
          try {
            const savedData = localStorage.getItem('correctiveBillingData');
            if (savedData) {
                this.failedRows = JSON.parse(savedData);
            } else {
                this.failedRows = [];
            }
          } catch (e) {
              console.error("Error loading state from localStorage", e);
              this.failedRows = [];
          } finally {
              this.isLoading = false;
          }
      });
    },
    saveState() {
      localStorage.setItem('correctiveBillingData', JSON.stringify(this.failedRows));
    },
    saveEditedRow(updatedRow) {
      const index = this.failedRows.findIndex(row => row.id === updatedRow.id);
      if (index !== -1) {
        this.failedRows.splice(index, 1, updatedRow);
        this.saveState();
      }
    },
    deleteRow(row) {
        if (window.confirm(`¿Está seguro de que desea eliminar permanentemente esta fila de la lista de corrección?`)) {
            this.failedRows = this.failedRows.filter(item => item.id !== row.id);
            this.saveState();
            this.$emitter.emit('show-alert', { type: 'success', message: 'Fila eliminada exitosamente.' });
        }
    },
    toggleRowExpansion(rowId) {
      const row = this.failedRows.find(r => r.id === rowId);
      if (row) {
        row.isExpanded = !row.isExpanded;
      }
    },
    updateRowStatus(rowId, status, errorInfo = null) {
      const index = this.failedRows.findIndex(r => r.id === rowId);
      if (index !== -1) {
        this.failedRows[index].Estado = status;
        this.failedRows[index].errorInfo = errorInfo;
        this.saveState();
      }
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
      let telefono = findValue('Teléfono');
      const precio = formatToNumber(findValue('Precio'));

      // Validations
      if (!cedula || (String(cedula).length !== 10 && String(cedula).length !== 13)) {
        throw new Error('Cédula no válida');
      }

      telefono = String(telefono || '').replace(/\s+/g, ''); // Clean up spaces
      if (telefono.length === 9) {
        telefono = '0' + telefono;
      }
      if (telefono.length !== 10) {
        throw new Error('Teléfono no válido');
      }

      if (!precio || precio <= 0) {
        throw new Error('Precio no válido');
      }
      if (!nombres || !codigo || !evento) {
        throw new Error('Una o más columnas requeridas (Nombres, Código, Evento) no se encontraron o están vacías en el archivo.');
      }
      const tarifa = this.getTarifaFromCodigoPorcentaje(this.userProfile.codigo_porcentaje_iva);
      const taxRate = 1 + (tarifa / 100);
      const totalSinImpuestos = formatToNumber(precio / taxRate, 6);
      const iva = formatToNumber(precio - totalSinImpuestos, 2);
      const metodoPago = findValue('metodo de pago');
      const pagos = parsePaymentMethods(metodoPago, precio);
      return {
        tipoIdentificacionComprador: String(cedula).length === 13 ? '04' : '05',
        razonSocialComprador: nombres,
        identificacionComprador: String(cedula),
        direccionComprador: direccion,
        totalSinImpuestos: formatToNumber(totalSinImpuestos, 2),
        totalDescuento: 0,
        totalConImpuestos: [{
            codigo: this.userProfile.tipo_impuesto,
            codigoPorcentaje: this.userProfile.codigo_porcentaje_iva,
            baseImponible: formatToNumber(totalSinImpuestos, 2),
            valor: iva
        }],
        importeTotal: precio,
        pagos: pagos,
        detalles: [{
          codigoPrincipal: String(codigo),
          descripcion: evento,
          cantidad: 1,
          precioUnitario: formatToNumber(totalSinImpuestos, 2),
          descuento: 0,
          precioTotalSinImpuesto: formatToNumber(totalSinImpuestos, 2),
          impuestos: [{
                codigo: this.userProfile.tipo_impuesto,
                codigoPorcentaje: this.userProfile.codigo_porcentaje_iva,
                tarifa: tarifa,
                baseImponible: formatToNumber(totalSinImpuestos, 2),
                valor: iva
            }],
        }],
        infoAdicional: { email: email, telefono: telefono },
      };
    },
    async processSingleInvoice(row) {
      try {
        this.updateRowStatus(row.id, 'Procesando', null);
        const payload = this.createInvoicePayload(row);
        await axios.post(`/api/comprobantes/factura/${this.selectedPuntoEmisionId}`, payload, {
          headers: { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' },
        });
        // On success, remove the row from the corrective table
        this.failedRows = this.failedRows.filter(item => item.id !== row.id);
        this.saveState();
      } catch (error) {
        const errorMessage = error.response?.data?.message || error.message;
        let friendlyMessage = errorMessage;
        if (error.message === 'Cédula no válida') {
          friendlyMessage = 'Cédula debe tener 10 o 13 dígitos.';
        } else if (error.message === 'Teléfono no válido') {
          friendlyMessage = 'Teléfono debe tener 10 dígitos (o 9 para corrección).';
        } else if (error.message === 'Precio no válido') {
          friendlyMessage = 'El Precio debe ser un número mayor a 0.';
        } else if (error.message.includes('columnas requeridas')) {
          friendlyMessage = 'Datos incompletos en la fila.';
        }
        // Update the row with the new error but keep it in the table
        this.updateRowStatus(row.id, 'No Facturado', friendlyMessage);
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
        this.failedRows.forEach(row => {
            if (row.Estado === 'Procesando') {
                this.updateRowStatus(row.id, 'No Facturado', 'Proceso cancelado por el usuario.');
            }
        });
    },
    startBilling() {
      this.rowsToBill = [...this.failedRows];
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
            return;
        }

        const row = this.rowsToBill[this.currentIndex];
        await this.processSingleInvoice(row);

        this.currentIndex++;

        requestAnimationFrame(this.runBillingProcess);
    },
    exportToExcel() {
        if (this.failedRows.length === 0) {
            this.$emitter.emit('show-alert', { type: 'error', message: 'No hay datos para exportar.' });
            return;
        }
        const worksheet = XLSX.utils.json_to_sheet(this.failedRows);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Facturas Fallidas");
        XLSX.writeFile(workbook, "facturas_correctivas.xlsx");
    },
  },
};
</script>
