<template>
  <div v-if="totalPages > 1" class="flex justify-between items-center mt-4">
    <button @click="$emit('prev-page')" :disabled="currentPage === 1" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md disabled:opacity-50 hover:bg-gray-400">
      Anterior
    </button>
    <span>Página {{ currentPage }} de {{ totalPages }}</span>
    <button @click="$emit('next-page')" :disabled="currentPage === totalPages" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md disabled:opacity-50 hover:bg-gray-400">
      Siguiente
    </button>
  </div>
</template>

<script>
export default {
  name: 'Pagination',
  props: {
    currentPage: {
      type: Number,
      required: true,
    },
    totalPages: {
      type: Number,
      required: true,
    },
  },
};
</script>
