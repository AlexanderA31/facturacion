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
        <tr v-if="!data || data.length === 0">
          <td :colspan="headers.length" class="px-8 py-5 text-center text-gray-500 text-lg">
            No hay datos para mostrar.
          </td>
        </tr>
        <tr v-for="row in data" :key="row.id" class="hover:bg-gray-100 transition-colors duration-200">
          <td v-for="header in headers" :key="header.value" class="px-8 py-4 whitespace-nowrap text-md text-gray-800">
            <slot :name="`cell(${header.value})`" :row="row">
              <!-- Default Fallback Content -->
              <span v-if="header.value.toLowerCase().includes('estado')" :class="getStatusClass(row[header.value])" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full">
                {{ row[header.value] }}
              </span>
              <span v-else-if="header.value === 'Evento' && row.Evento">
                <span v-if="!row.isExpanded" @click="$emit('toggle-expansion', row.id)" class="cursor-pointer" :title="row.Evento">
                  {{ truncateText(row.Evento, 20) }}
                </span>
                <span v-else @click="$emit('toggle-expansion', row.id)" class="cursor-pointer">
                  {{ row.Evento }}
                </span>
              </span>
              <span v-else-if="header.value === 'errorInfo' && row.errorInfo">
                  <span v-if="!row.isExpanded" @click="$emit('toggle-expansion', row.id)" class="cursor-pointer text-red-600" :title="row.errorInfo">
                      {{ truncateText(row.errorInfo, 30) }}
                  </span>
                  <span v-else @click="$emit('toggle-expansion', row.id)" class="cursor-pointer text-red-600">
                      {{ row.errorInfo }}
                  </span>
              </span>
              <span v-else-if="header.value === 'error_message' && row.error_message">
                <span v-if="!row.isErrorExpanded" @click="$emit('toggle-error-expansion', row.id)" class="cursor-pointer" :title="row.error_message">
                  {{ truncateText(row.error_message, 30) }}
                </span>
                <span v-else @click="$emit('toggle-error-expansion', row.id)" class="cursor-pointer">
                  {{ row.error_message }}
                </span>
              </span>
              <span v-else-if="header.value === 'acciones'" class="space-x-2 text-center">
                  <!-- Download Buttons -->
                  <button v-if="(row.estado === 'autorizado' && row.fecha_autorizacion) || row.error_message === 'ERROR SECUENCIAL REGISTRADO'" @click="$emit('download-xml', row.clave_acceso)" class="px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600">
                      XML
                  </button>
                  <button v-if="(row.estado === 'autorizado' && row.fecha_autorizacion) || row.error_message === 'ERROR SECUENCIAL REGISTRADO'" @click="$emit('download-pdf', row.clave_acceso)" class="px-3 py-1 bg-green-500 text-white rounded-md text-sm hover:bg-green-600">
                      PDF
                  </button>
                  <!-- Edit Button -->
                  <button v-if="showEditButton" @click="$emit('open-edit-modal', row)" class="px-3 py-1 bg-yellow-500 text-white rounded-md text-sm hover:bg-yellow-600">
                      Editar
                  </button>
              </span>
              <span v-else>
                {{ row[header.value] }}
              </span>
            </slot>
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
    showEditButton: {
      type: Boolean,
      default: false,
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
      const lowerStatus = status ? String(status).toLowerCase() : '';
      switch (lowerStatus) {
        case 'facturado':
        case 'autorizado':
          return 'bg-green-100 text-green-800';
        case 'no facturado':
        case 'rechazado':
        case 'fallido':
          return 'bg-red-100 text-red-800';
        case 'enviado':
        case 'firmado':
            return 'bg-indigo-100 text-indigo-800';
        case 'procesando':
          return 'bg-blue-100 text-blue-800';
        case 'pendiente':
        default:
          return 'bg-yellow-100 text-yellow-800';
      }
    },
  },
};
</script>
