<template>
    <div class="bg-white rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-4 px-6 pt-6">
            <h3 class="text-xl font-bold text-gray-800">Gestionar Compras</h3>
            <div class="flex space-x-4">
                <RefreshButton :is-loading="isLoading" @click="fetchPurchases" />
                <BaseButton @click="openCreateModal" variant="primary">
                    <template #icon>
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    Nueva Compra
                </BaseButton>
            </div>
        </div>

        <div class="flex justify-end my-4">
            <div class="relative w-full max-w-xs">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" v-model="searchQuery" placeholder="Buscar..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>

        <TableSkeleton v-if="isLoading" />
        <DataTable v-else :data="processedPurchases" :headers="headers" :sort-key="sortKey" :sort-order="sortOrder" @sort="sortBy">
            <template #cell(actions)="{ row }">
                <div class="space-x-2 text-center">
                    <button @click="openEditModal(row)" title="Editar" class="p-1 text-yellow-600 hover:text-yellow-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button @click="confirmDelete(row)" title="Eliminar" class="p-1 text-red-600 hover:text-red-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <PurchaseInvoiceModal
            :show="isModalOpen"
            :purchase="selectedPurchase"
            :is-loading="isSubmitting"
            @close="closeModal"
            @save="handleSave"
        />
    </div>
</template>

<script>
import axios from 'axios';
import DataTable from './DataTable.vue';
import BaseButton from './BaseButton.vue';
import PurchaseInvoiceModal from './PurchaseInvoiceModal.vue';
import RefreshButton from './RefreshButton.vue';
import TableSkeleton from './TableSkeleton.vue';

export default {
    name: 'PurchasesManager',
    components: {
        DataTable,
        BaseButton,
        PurchaseInvoiceModal,
        RefreshButton,
        TableSkeleton,
    },
    data() {
        return {
            purchases: [],
            headers: [
                { text: 'Proveedor', value: 'supplier.razon_social' },
                { text: 'Comprobante', value: 'secuencial' },
                { text: 'Fecha Emisión', value: 'fecha_emision' },
                { text: 'Autorización', value: 'autorizacion' },
                { text: 'Acciones', value: 'actions' },
            ],
            isLoading: false,
            isSubmitting: false,
            isModalOpen: false,
            selectedPurchase: null,
            token: localStorage.getItem('jwt_token'),
            searchQuery: '',
            sortKey: 'fecha_emision',
            sortOrder: 'desc',
        };
    },
    computed: {
        processedPurchases() {
            let filtered = [...this.purchases];

            if (this.searchQuery) {
                const lowerCaseQuery = this.searchQuery.toLowerCase();
                filtered = filtered.filter(item => {
                    return (item.supplier.razon_social || '').toLowerCase().includes(lowerCaseQuery) ||
                           (item.secuencial || '').toLowerCase().includes(lowerCaseQuery) ||
                           (item.autorizacion || '').toLowerCase().includes(lowerCaseQuery);
                });
            }

            if (this.sortKey) {
                filtered.sort((a, b) => {
                    let valA = this.getSortValue(a, this.sortKey);
                    let valB = this.getSortValue(b, this.sortKey);
                    if (valA < valB) return this.sortOrder === 'asc' ? -1 : 1;
                    if (valA > valB) return this.sortOrder === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            return filtered;
        }
    },
    async created() {
        await this.fetchPurchases();
    },
    methods: {
        getSortValue(obj, key) {
            return key.split('.').reduce((o, i) => o[i], obj);
        },
        sortBy(key) {
            if (this.sortKey === key) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortOrder = 'asc';
            }
        },
        async fetchPurchases() {
            this.isLoading = true;
            try {
                const response = await axios.get('/api/purchase-invoices', {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.purchases = response.data.data.data;
            } catch (error) {
                console.error('Error fetching purchases:', error);
            } finally {
                this.isLoading = false;
            }
        },
        openCreateModal() {
            this.selectedPurchase = null;
            this.isModalOpen = true;
        },
        openEditModal(purchase) {
            this.selectedPurchase = purchase;
            this.isModalOpen = true;
        },
        closeModal() {
            this.isModalOpen = false;
            this.selectedPurchase = null;
        },
        async handleSave(purchaseData) {
            if (this.selectedPurchase) {
                await this.handleUpdate(purchaseData);
            } else {
                await this.handleCreate(purchaseData);
            }
        },
        async handleCreate(data) {
            this.isSubmitting = true;
            try {
                await axios.post('/api/purchase-invoices', data, {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.closeModal();
                await this.fetchPurchases();
            } catch (error) {
                console.error('Error creating purchase:', error);
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.errors;
                    let errorMessage = 'Error de validación:<br>';
                    for (const key in errors) {
                        errorMessage += `- ${errors[key][0]}<br>`;
                    }
                    this.$emitter.emit('show-alert', { type: 'error', message: errorMessage });
                } else {
                    const errorMessage = error.response?.data?.message || 'Error al crear la factura de compra.';
                    this.$emitter.emit('show-alert', { type: 'error', message: errorMessage });
                }
            } finally {
                this.isSubmitting = false;
            }
        },
        async handleUpdate(data) {
            this.isSubmitting = true;
            try {
                await axios.put(`/api/purchase-invoices/${data.id}`, data, {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.closeModal();
                await this.fetchPurchases();
            } catch (error) {
                console.error('Error updating purchase:', error);
            } finally {
                this.isSubmitting = false;
            }
        },
        async confirmDelete(purchase) {
            if (window.confirm(`¿Está seguro de que desea eliminar la factura de compra "${purchase.secuencial}"?`)) {
                this.isLoading = true;
                try {
                    await axios.delete(`/api/purchase-invoices/${purchase.id}`, {
                        headers: { 'Authorization': `Bearer ${this.token}` }
                    });
                    await this.fetchPurchases();
                } catch (error) {
                    console.error('Error deleting purchase:', error);
                } finally {
                    this.isLoading = false;
                }
            }
        },
    }
}
</script>
