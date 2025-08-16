<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Facturación Correctiva</h2>
    <p class="text-gray-600 mb-6">Aquí puede ver las facturas que fallaron durante el proceso masivo y necesitan corrección. Edite los datos necesarios y vuelva a procesarlas.</p>

    <div class="bg-white rounded-xl shadow-lg p-6">
      <!-- Billing controls will go here -->
      <div class="mb-4 flex justify-end">
        <!-- Start Button -->
        <button v-if="!isBilling" @click="startBilling" :disabled="failedRows.length === 0"
                class="w-full sm:w-auto px-6 py-3 bg-green-600 text-white font-medium text-lg leading-tight uppercase rounded-lg shadow-md hover:bg-green-700 disabled:bg-gray-400 flex items-center justify-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Facturar Corregidas
        </button>
        <!-- Billing In Progress Controls -->
        <div v-if="isBilling" class="flex items-center space-x-4">
            <div class="flex items-center text-lg font-medium text-gray-700">
                <svg class="animate-spin mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Procesando... ({{ currentIndex + 1 }} / {{ rowsToBill.length }})</span>
            </div>
            <button v-if="!isPaused" @click="pauseBilling" class="px-4 py-2 bg-yellow-500 text-white rounded-md shadow-sm hover:bg-yellow-600">
                Pausar
            </button>
            <button v-if="isPaused" @click="resumeBilling" class="px-4 py-2 bg-green-500 text-white rounded-md shadow-sm hover:bg-green-600">
                Reanudar
            </button>
            <button @click="cancelBilling" class="px-4 py-2 bg-red-600 text-white rounded-md shadow-sm hover:bg-red-700">
                Cancelar
            </button>
        </div>
      </div>

      <!-- Data table for failed rows -->
      <DataTable
        :data="paginatedRows"
        :headers="tableHeaders"
        :editable="true"
        :reprocessable="false"
        @save-row="handleSaveRow"
      />
      <Pagination :currentPage="currentPage" :totalPages="totalPages" @prev-page="currentPage--" @next-page="currentPage++" />
    </div>
  </div>
</template>

<script>
import DataTable from './DataTable.vue';
import Pagination from './Pagination.vue';
import axios from 'axios';

export default {
  name: 'CorrectiveBilling',
  components: {
    DataTable,
    Pagination,
  },
  data() {
    return {
      failedRows: [],
      currentPage: 1,
      itemsPerPage: 10,
      tableHeaders: [
        { text: 'Nombres', value: 'Nombres' },
        { text: 'Cédula', value: 'Cédula' },
        { text: 'Evento', value: 'Evento' },
        { text: 'Precio', value: 'Precio' },
        { text: 'Estado', value: 'Estado' },
        { text: 'Error', value: 'errorInfo' },
      ],
      isBilling: false,
      isPaused: false,
      currentIndex: 0,
      rowsToBill: [],
      puntoEmisionId: 1, // Assuming a default, might need to be dynamic
      token: localStorage.getItem('jwt_token'),
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
  },
  watch: {
    failedRows: {
        handler() {
            this.saveState();
        },
        deep: true,
    }
  },
  mounted() {
    this.loadState();
    window.addEventListener('corrective-billing-update', this.loadState);
  },
  beforeUnmount() {
    window.removeEventListener('corrective-billing-update', this.loadState);
  },
  methods: {
    loadState() {
      const savedData = localStorage.getItem('correctiveBillingData');
      if (savedData) {
        this.failedRows = JSON.parse(savedData);
      }
    },
    saveState() {
      localStorage.setItem('correctiveBillingData', JSON.stringify(this.failedRows));
    },
    handleSaveRow(updatedRow) {
      const index = this.failedRows.findIndex(row => row.id === updatedRow.id);
      if (index !== -1) {
        this.failedRows.splice(index, 1, updatedRow);
        this.saveState();
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
          const normalizedObjKey = key.toLowerCase().normalize("NFD").replace(/[\u0000-\u036f]/g, "");
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
        await axios.post(`/api/comprobantes/factura/${this.puntoEmisionId}`, payload, {
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
