<template>
  <div class="overflow-x-auto shadow-md sm:rounded-lg">
    <table class="min-w-full bg-white">
      <thead class="bg-gray-50">
        <tr>
          <th v-for="header in headers" :key="header" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ header }}
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-if="data.length === 0">
          <td :colspan="headers.length" class="px-6 py-4 text-center text-gray-500">
            No data to display. Upload a file to get started.
          </td>
        </tr>
        <tr v-for="(row, index) in data" :key="index" class="hover:bg-gray-50">
          <td v-for="header in headers" :key="header" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            <span v-if="header === 'Estado'" :class="getStatusClass(row[header])">
              {{ row[header] }}
            </span>
            <span v-else>
              {{ row[header] }}
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
    getStatusClass(status) {
      switch (status) {
        case 'Billed':
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
        case 'Not Billed':
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
        case 'Pending':
        default:
          return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
      }
    },
  },
};
</script>
