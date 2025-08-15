<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-2xl font-semibold mb-4 border-b pb-3">Gestión de Clientes</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-1">
        <h3 class="text-xl font-semibold mb-3 text-gray-700">Registrar Nuevo Cliente</h3>
        <form @submit.prevent="registerClient">
          <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre:</label>
            <input type="text" id="name" v-model="client.name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" id="email" v-model="client.email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
            <input type="password" id="password" v-model="client.password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="ruc" class="block text-gray-700 text-sm font-bold mb-2">RUC:</label>
            <input type="text" id="ruc" v-model="client.ruc" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="razonSocial" class="block text-gray-700 text-sm font-bold mb-2">Razón Social:</label>
            <input type="text" id="razonSocial" v-model="client.razonSocial" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label for="nombreComercial" class="block text-gray-700 text-sm font-bold mb-2">Nombre Comercial:</label>
            <input type="text" id="nombreComercial" v-model="client.nombreComercial" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
          </div>
          <div class="mb-4">
            <label for="dirMatriz" class="block text-gray-700 text-sm font-bold mb-2">Dirección Matriz:</label>
            <input type="text" id="dirMatriz" v-model="client.dirMatriz" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
          </div>
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
              <input type="checkbox" v-model="client.obligadoContabilidad" class="mr-2 leading-tight">
              <span>Obligado a llevar contabilidad</span>
            </label>
          </div>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            Registrar Cliente
          </button>
        </form>
      </div>
      <div class="lg:col-span-2">
        <h3 class="text-xl font-semibold mb-3 text-gray-700">Lista de Clientes</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="py-2 px-4">Nombre</th>
                <th class="py-2 px-4">Email</th>
                <th class="py-2 px-4">RUC</th>
                <th class="py-2 px-4">Razón Social</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in clients" :key="c.id" class="border-b">
                <td class="py-2 px-4">{{ c.name }}</td>
                <td class="py-2 px-4">{{ c.email }}</td>
                <td class="py-2 px-4">{{ c.ruc }}</td>
                <td class="py-2 px-4">{{ c.razonSocial }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ClientManager',
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      clients: [],
      client: {
        name: '',
        email: '',
        password: '',
        ruc: '',
        razonSocial: '',
        nombreComercial: '',
        dirMatriz: '',
        obligadoContabilidad: false,
        tarifa: 'comprobante',
        ambiente: '1'
      }
    };
  },
  mounted() {
    this.getClients();
  },
  methods: {
    async getClients() {
      try {
        const response = await axios.get('/api/admin/clients', {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });
        this.clients = response.data.data.data;
      } catch (error) {
        console.error('Error fetching clients:', error);
      }
    },
    async registerClient() {
      try {
        await axios.post('/api/admin/clients', this.client, {
          headers: {
            'Authorization': `Bearer ${this.token}`,
            'Content-Type': 'application/json'
          }
        });
        alert('Cliente registrado exitosamente');
        this.getClients(); // Refresh the list
        // Clear the form
        this.client = {
          name: '',
          email: '',
          password: '',
          ruc: '',
          razonSocial: '',
          nombreComercial: '',
          dirMatriz: '',
          obligadoContabilidad: false,
          tarifa: 'comprobante',
          ambiente: '1'
        };
      } catch (error) {
        console.error('Error registering client:', error);
        alert('Error al registrar el cliente: ' + (error.response?.data?.message || error.message));
      }
    }
  }
};
</script>
