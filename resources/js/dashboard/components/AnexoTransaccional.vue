<template>
  <div>
    <div class="bg-white rounded-xl shadow-lg p-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Anexo Transaccional</h2>
      <p class="text-gray-600 mb-6">Seleccione el año y el mes para generar el Anexo Transaccional Simplificado (ATS).</p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <label for="year" class="block text-sm font-medium text-gray-700">Año</label>
          <input type="number" id="year" v-model="year" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
          <label for="month" class="block text-sm font-medium text-gray-700">Mes</label>
          <select id="month" v-model="month" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.text }}</option>
          </select>
        </div>
      </div>

      <div class="flex justify-end">
        <button @click="generateAnexo" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
          Generar Anexo
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AnexoTransaccional',
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      year: new Date().getFullYear(),
      month: new Date().getMonth() + 1,
      months: [
        { value: 1, text: 'Enero' },
        { value: 2, text: 'Febrero' },
        { value: 3, text: 'Marzo' },
        { value: 4, text: 'Abril' },
        { value: 5, text: 'Mayo' },
        { value: 6, text: 'Junio' },
        { value: 7, text: 'Julio' },
        { value: 8, text: 'Agosto' },
        { value: 9, text: 'Septiembre' },
        { value: 10, text: 'Octubre' },
        { value: 11, text: 'Noviembre' },
        { value: 12, text: 'Diciembre' },
      ],
    };
  },
  methods: {
    async generateAnexo() {
      try {
        const response = await axios.get(`/api/anexo-transaccional/${this.year}/${this.month}`, {
          headers: { 'Authorization': `Bearer ${this.token}` },
          responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/xml' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `ats-${this.year}-${this.month}.xml`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } catch (error) {
        console.error('Error generating Anexo Transaccional:', error);
        const errorMessage = error.response?.data?.message || 'Error al generar el anexo.';
        this.$emitter.emit('show-alert', { type: 'error', message: errorMessage });
      }
    },
  },
};
</script>
