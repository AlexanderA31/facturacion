<template>
  <AppLayout
    :navigation="navigation"
    :current-view="currentView"
    header-title="Panel de Administración"
    sidebar-title="Admin"
    @navigate="currentView = $event"
    @logout="$emit('logout')"
  >
    <AdminUsers v-if="currentView === 'users'" :token="token" />
    <AdminClients v-if="currentView === 'clients'" :token="token" />
  </AppLayout>
</template>

<script>
import { h } from 'vue';
import AppLayout from './AppLayout.vue';
import AdminUsers from './AdminUsers.vue';
import AdminClients from './AdminClients.vue';

const IconUsers = {
  render() { return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg' }, [ h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': 2, d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197M15 21a6 6 0 006-6v-1a6 6 0 00-9-5.197' }) ]); }
};
const IconClients = {
    render() { return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg' }, [ h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': 2, d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }) ]); }
};

export default {
  name: 'AdminDashboard',
  components: {
    AppLayout,
    AdminUsers,
    AdminClients,
  },
  props: {
    token: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      currentView: 'users',
      navigation: [
        { name: 'Gestionar Usuarios', view: 'users', icon: IconUsers },
        { name: 'Gestionar Clientes', view: 'clients', icon: IconClients },
      ],
    };
  },
};
</script>
