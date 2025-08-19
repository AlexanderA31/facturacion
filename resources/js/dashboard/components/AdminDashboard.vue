<template>
  <AppLayout
    :navigation="navigation"
    :current-view="currentView"
    :is-sidebar-open="isSidebarOpen"
    header-title="Panel de Administración"
    sidebar-title="Admin"
    @navigate="currentView = $event"
    @logout="$emit('logout')"
    @toggle-sidebar="isSidebarOpen = !isSidebarOpen"
  >
    <AdminUsers v-if="currentView === 'users'" :token="token" :is-sidebar-open="isSidebarOpen" />
    <AdminClients v-if="currentView === 'clients'" :token="token" :is-sidebar-open="isSidebarOpen" />
  </AppLayout>
</template>

<script>
import { h } from 'vue';
import AppLayout from './AppLayout.vue';
import AdminUsers from './AdminUsers.vue';
import AdminClients from './AdminClients.vue';

const IconUsers = {
  render() { return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg', 'stroke-width': 1.5 }, [ h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.39.44 1.022.12 1.45l-.527.737c-.25.35-.272.806-.108 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.11v1.093c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.142.854.108 1.204l.527.738c.32.427.27 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.204-.108-.397.165-.71.505-.78.93l-.15.894c-.09.542-.56.94-1.11-.94h-1.093c-.55 0-1.02-.398-1.11-.94l-.149-.894c-.07-.424-.384-.764-.78-.93-.398-.164-.855-.142-1.205-.108l-.737.527a1.125 1.125 0 0 1-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272.806.108-1.204-.165-.397-.505-.71-.93-.78l-.893-.15c-.543-.09-.94-.56-.94-1.11v-1.093c0-.55.397-1.02.94-1.11l.893-.149c.425-.07.765-.383.93-.78.165-.398.142-.854-.108-1.204l-.527-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.806.272 1.204.108.397-.165.71-.505.78-.93l.15-.894Z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' }) ]); }
};
const IconClients = {
  render() { return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg', 'stroke-width': 1.5 }, [ h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m-7.289 2.72a9.094 9.094 0 0 1-3.741-.479 3 3 0 0 1-4.682-2.72M12 12.75a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z' }) ]); }
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
      isSidebarOpen: false,
      currentView: 'users',
      navigation: [
        { name: 'Gestionar Usuarios', view: 'users', icon: IconUsers },
        { name: 'Gestionar Clientes', view: 'clients', icon: IconClients },
      ],
    };
  },
};
</script>
