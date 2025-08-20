<template>
  <div v-if="totalPages > 1" class="flex items-center justify-between">
    <!-- Previous Button -->
    <button @click="$emit('prev-page')" :disabled="currentPage === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
      Anterior
    </button>

    <!-- Page Info -->
    <div class="hidden sm:block">
      <p class="text-sm text-gray-700">
        Página
        <span class="font-medium">{{ currentPage }}</span>
        de
        <span class="font-medium">{{ totalPages }}</span>
      </p>
    </div>

    <!-- Next Button -->
    <button @click="$emit('next-page')" :disabled="currentPage === totalPages"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
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
