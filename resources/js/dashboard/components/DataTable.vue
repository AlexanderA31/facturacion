<template>
  <div class="overflow-x-auto shadow-lg sm:rounded-lg border border-gray-200">
    <table class="min-w-full bg-white">
      <thead class="bg-gray-800 text-white">
        <tr>
          <th v-for="header in headers" :key="header.value" scope="col" class="px-8 py-4 text-left text-sm font-bold uppercase tracking-wider">
            {{ header.text }}
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-if="data.length === 0">
          <td :colspan="headers.length" class="px-8 py-5 text-center text-gray-500 text-lg">
            No hay datos para mostrar.
          </td>
        </tr>
        <tr v-for="(row, index) in data" :key="index" class="hover:bg-gray-100 transition-colors duration-200">
          <td v-for="header in headers" :key="header.value" class="px-8 py-4 whitespace-nowrap text-md text-gray-800">
            <span v-if="header.value === 'estado'" :class="getStatusClass(row.estado)" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full">
              {{ row.estado }}
            </span>
            <span v-else-if="header.value === 'evento'">
              <span v-if="!row.isExpanded" @click="$emit('toggle-expansion', row.id)" class="cursor-pointer" :title="row.evento">
                {{ truncateText(row.evento, 20) }}
              </span>
              <span v-else @click="$emit('toggle-expansion', row.id)" class="cursor-pointer">
                {{ row.evento }}
              </span>
            </span>
            <span v-else-if="header.value === 'error_message'">
              <span v-if="row.error_message">
                <span v-if="!row.isErrorExpanded" @click="$emit('toggle-error-expansion', row.id)" class="cursor-pointer" :title="row.error_message">
                  {{ truncateText(row.error_message, 30) }}
                </span>
                <span v-else @click="$emit('toggle-error-expansion', row.id)" class="cursor-pointer">
                  {{ row.error_message }}
                </span>
              </span>
            </span>
            <span v-else-if="header.value === 'acciones'" class="space-x-2">
              <button v-if="row.estado === 'autorizado'" @click="$emit('download-xml', row.clave_acceso)" class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">
                XML
              </button>
              <button v-if="row.estado === 'autorizado'" @click="$emit('download-pdf', row.clave_acceso)" class="px-3 py-1 bg-green-500 text-white rounded-md text-sm hover:bg-green-600">
                PDF
              </button>
            </span>
            <span v-else>
              {{ row[header.value] }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
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
