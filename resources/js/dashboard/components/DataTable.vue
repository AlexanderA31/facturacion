<template>
  <div>
    <div class="overflow-x-auto shadow-lg sm:rounded-lg border border-gray-200">
      <table class="min-w-full bg-white">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th v-for="header in headers" :key="header" scope="col" class="px-8 py-4 text-left text-sm font-bold uppercase tracking-wider">
              {{ header }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="data.length === 0">
            <td :colspan="headers.length" class="px-8 py-5 text-center text-gray-500 text-lg">
              No hay datos para mostrar. Sube un archivo para comenzar.
            </td>
          </tr>
          <tr v-for="(row, index) in data" :key="index" class="hover:bg-gray-100 transition-colors duration-200">
            <td v-for="header in headers" :key="header" class="px-8 py-4 whitespace-nowrap text-md text-gray-800">
              <span v-if="header === 'Estado'" :class="getStatusClass(row[header])" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full">
                {{ row[header] }}
              </span>
              <span v-else-if="header === 'Evento'">
                <span v-if="!row.isExpanded" @click="$emit('toggle-expansion', row.id)" class="cursor-pointer">
                  {{ truncateText(row[header], 20) }}
                </span>
                <span v-else @click="$emit('toggle-expansion', row.id)" class="cursor-pointer">
                  {{ row[header] }}
                </span>
              </span>
              <span v-else>
                {{ row[header] }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="totalPages > 1" class="flex justify-between items-center mt-4">
      <button @click="$emit('prev-page')" :disabled="currentPage === 1" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md disabled:opacity-50">
        Anterior
      </button>
      <span>Página {{ currentPage }} de {{ totalPages }}</span>
      <button @click="$emit('next-page')" :disabled="currentPage === totalPages" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md disabled:opacity-50">
        Siguiente
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DataTable',
  props: {
    data: {
      type: Array,
      required: true,
    },
    headers: {
      type: Array,
      required: true,
    },
    currentPage: {
      type: Number,
      required: true,
    },
    totalPages: {
      type: Number,
      required: true,
    },
  },
  methods: {
    truncateText(text, length) {
      if (text && text.length > length) {
        return text.substring(0, length) + '...';
      }
      return text;
    },
    getStatusClass(status) {
      switch (status) {
        case 'Facturado':
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
        case 'No Facturado':
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
        case 'Enviado':
            return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800';
        case 'Procesando':
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800';
        case 'Pendiente':
        default:
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
      }
    },
  },
};
</script>
