<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-800">Facturación Correctiva</h2>
      <RefreshButton :is-loading="isLoading" @click="loadState" />
    </div>
    <p class="text-gray-600 mb-6">Aquí puede ver las facturas que fallaron durante el proceso masivo y necesitan corrección. Edite los datos necesarios y vuelva a procesarlas.</p>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <!-- Billing controls will go here -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
              <label for="corr-establecimiento-select" class="block text-sm font-medium text-gray-700">Establecimiento</label>
              <select id="corr-establecimiento-select" v-model="selectedEstablecimientoId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                  <option :value="null" disabled>Seleccione un establecimiento</option>
                  <option v-for="est in establecimientos" :key="est.id" :value="est.id">
                      {{ est.codigo }} - {{ est.nombre }}
                  </option>
              </select>
          </div>
          <div>
              <label for="corr-punto-emision-select" class="block text-sm font-medium text-gray-700">Punto de Emisión</label>
              <select id="corr-punto-emision-select" v-model="selectedPuntoEmisionId" :disabled="!selectedEstablecimientoId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md disabled:bg-gray-200">
                  <option :value="null" disabled>Seleccione un punto de emisión</option>
                  <option v-for="pto in availablePuntosEmision" :key="pto.id" :value="pto.id">
                      {{ pto.codigo }} - {{ pto.nombre }}
                  </option>
              </select>
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
          :showEditButton="true"
          @open-edit-modal="openEditModal"
          @toggle-expansion="toggleRowExpansion"
        />
        <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
      </div>
    </div>

    <EditInvoiceModal
        :show="isModalVisible"
        :rowData="selectedRowForEdit"
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
import axios from 'axios';

export default {
  name: 'CorrectiveBilling',
  components: {
    DataTable,
    Pagination,
    BaseButton,
    EditInvoiceModal,
    RefreshButton,
  },
  data() {
    return {
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
    availablePuntosEmision() {
        if (!this.selectedEstablecimientoId) {
            return [];
        }
        return this.puntosEmision.filter(p => p.establecimiento_id === this.selectedEstablecimientoId);
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
    this.fetchEstablecimientos();
    this.fetchPuntosEmision();
  },
  beforeUnmount() {
    window.removeEventListener('corrective-billing-update', this.loadState);
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
      const telefono = findValue('Teléfono');
      const precio = formatToNumber(findValue('Precio'));
      if (!cedula || !nombres || !precio || !codigo || !evento) {
        throw new Error('Una o más columnas requeridas (Cédula, Nombres, Precio, Código, Evento) no se encontraron o están vacías en el archivo.');
      }
      const totalSinImpuestos = formatToNumber(precio / 1.15, 6);
      const iva = formatToNumber(precio - totalSinImpuestos, 2);
      return {
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
          precioUnitario: formatToNumber(totalSinImpuestos, 2),
          descuento: 0,
          precioTotalSinImpuesto: formatToNumber(totalSinImpuestos, 2),
          impuestos: [{ codigo: 2, codigoPorcentaje: 4, tarifa: 15.00, baseImponible: formatToNumber(totalSinImpuestos, 2), valor: iva }],
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
        this.updateRowStatus(row.id, 'No Facturado', errorMessage);
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
  },
};
</script>
