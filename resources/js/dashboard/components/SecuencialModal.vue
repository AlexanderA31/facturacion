<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="close">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <div class="p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Editar Próximo Secuencial</h3>
        <form @submit.prevent="save">
          <div class="mb-4">
            <label for="proximo_secuencial" class="block text-sm font-medium text-gray-700">Próximo Secuencial</label>
            <input
              type="text"
              id="proximo_secuencial"
              v-model="editableSecuencial"
              class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              maxlength="9"
              pattern="\d{9}"
              title="Debe ser un número de 9 dígitos."
              required
            />
            <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
          </div>
          <div class="flex justify-end space-x-4">
            <BaseButton type="button" @click="close" variant="secondary">Cancelar</BaseButton>
            <BaseButton type="submit" :is-loading="isLoading" variant="primary">Guardar</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from './BaseButton.vue';

export default {
  name: 'SecuencialModal',
  components: { BaseButton },
  props: {
    show: Boolean,
    puntoEmision: Object,
    isLoading: Boolean,
  },
  data() {
    return {
      editableSecuencial: '',
      error: null,
    };
  },
  watch: {
    puntoEmision(newVal) {
      if (newVal) {
        this.editableSecuencial = newVal.proximo_secuencial || '000000001';
      }
    },
  },
  methods: {
    close() {
      this.$emit('close');
    },
    save() {
      this.error = null;
      if (!/^\d{9}$/.test(this.editableSecuencial)) {
        this.error = 'El secuencial debe ser un número de 9 dígitos.';
        return;
      }
      this.$emit('save', {
        proximo_secuencial: this.editableSecuencial,
      });
    },
  },
};
</script>
