<template>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Gestionar Puntos de Emisión</h3>
            <div class="flex space-x-4">
                <RefreshButton :is-loading="isLoading" @click="fetchPuntosEmision" />
                <BaseButton @click="openCreateModal" variant="primary" :disabled="establecimientos.length === 0">
                    <template #icon>
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    Nuevo Punto de Emisión
                </BaseButton>
            </div>
        </div>
        <p v-if="establecimientos.length === 0" class="text-sm text-yellow-600 bg-yellow-50 p-3 rounded-md">
            Debe crear al menos un establecimiento antes de poder agregar puntos de emisión.
        </p>

        <DataTable :data="puntosEmision" :headers="headers" :is-loading="isLoading">
            <template #cell(actions)="{ row }">
                <div class="space-x-2 text-center">
                    <button @click="openEditModal(row)" title="Editar" class="p-1 text-yellow-600 hover:text-yellow-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    <button @click="confirmReset(row)" title="Reiniciar Secuencial" class="p-1 text-blue-600 hover:text-blue-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 4l16 16"></path></svg>
                    </button>
                    <button @click="confirmDelete(row)" title="Eliminar" class="p-1 text-red-600 hover:text-red-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <PuntoEmisionModal
            :show="isModalOpen"
            :punto-emision="selectedPuntoEmision"
            :establecimientos="establecimientos"
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
import PuntoEmisionModal from './PuntoEmisionModal.vue';
import RefreshButton from './RefreshButton.vue';

export default {
    name: 'PuntosEmisionManager',
    components: {
        DataTable,
        BaseButton,
        PuntoEmisionModal,
        RefreshButton,
    },
    data() {
        return {
            puntosEmision: [],
            establecimientos: [],
            headers: [
                { text: 'Establecimiento', value: 'establecimiento_codigo' },
                { text: 'Número', value: 'numero' },
                { text: 'Nombre', value: 'nombre' },
                { text: 'Acciones', value: 'actions' },
            ],
            isLoading: false,
            isSubmitting: false,
            isModalOpen: false,
            selectedPuntoEmision: null,
            token: localStorage.getItem('jwt_token'),
        };
    },
    async created() {
        this.isLoading = true;
        await this.fetchEstablecimientos();
        await this.fetchPuntosEmision();
        this.isLoading = false;
    },
    methods: {
        async fetchEstablecimientos() {
            try {
                const response = await axios.get('/api/establecimientos', {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.establecimientos = response.data.data.data;
            } catch (error) {
                console.error('Error fetching establecimientos:', error);
            }
        },
        async fetchPuntosEmision() {
            try {
                const response = await axios.get('/api/puntos-emision', {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.puntosEmision = response.data.data.data.map(p => {
                    p.establecimiento_codigo = p.establecimiento?.numero || 'N/A';
                    return p;
                });
            } catch (error) {
                console.error('Error fetching puntos de emision:', error);
            }
        },
        openCreateModal() {
            this.selectedPuntoEmision = null;
            this.isModalOpen = true;
        },
        openEditModal(puntoEmision) {
            this.selectedPuntoEmision = puntoEmision;
            this.isModalOpen = true;
        },
        closeModal() {
            this.isModalOpen = false;
            this.selectedPuntoEmision = null;
        },
        async handleSave(data) {
            if (this.selectedPuntoEmision) {
                await this.handleUpdate(data);
            } else {
                await this.handleCreate(data);
            }
        },
        async handleCreate(data) {
            this.isSubmitting = true;
            try {
                await axios.post('/api/puntos-emision', data, {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.closeModal();
                await this.fetchPuntosEmision();
            } catch (error) {
                console.error('Error creating punto de emision:', error);
            } finally {
                this.isSubmitting = false;
            }
        },
        async handleUpdate(data) {
            this.isSubmitting = true;
            try {
                await axios.put(`/api/puntos-emision/${this.selectedPuntoEmision.id}`, data, {
                    headers: { 'Authorization': `Bearer ${this.token}` }
                });
                this.closeModal();
                await this.fetchPuntosEmision();
            } catch (error) {
                console.error('Error updating punto de emision:', error);
            } finally {
                this.isSubmitting = false;
            }
        },
        async confirmDelete(puntoEmision) {
            if (window.confirm(`¿Está seguro de que desea eliminar el punto de emisión "${puntoEmision.nombre}"?`)) {
                try {
                    await axios.delete(`/api/puntos-emision/${puntoEmision.id}`, {
                        headers: { 'Authorization': `Bearer ${this.token}` }
                    });
                    await this.fetchPuntosEmision();
                } catch (error) {
                    console.error('Error deleting punto de emision:', error);
                }
            }
        },
        async confirmReset(puntoEmision) {
            if (window.confirm(`¿Está seguro de que desea reiniciar el secuencial del punto de emisión "${puntoEmision.nombre}"? Esta acción no se puede deshacer.`)) {
                try {
                    await axios.post(`/api/puntos-emision/reset/${puntoEmision.id}`, {}, {
                        headers: { 'Authorization': `Bearer ${this.token}` }
                    });
                    alert('El secuencial ha sido reiniciado.');
                } catch (error) {
                    console.error('Error resetting punto de emision:', error);
                    alert('Error al reiniciar el secuencial.');
                }
            }
        },
    }
}
</script>
