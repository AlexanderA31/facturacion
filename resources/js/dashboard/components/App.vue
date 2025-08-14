<template>
  <div>
    <Login v-if="!token" @login-success="handleLoginSuccess" />
    <Dashboard v-else :token="token" @logout="handleLogout" />
  </div>
</template>

<script>
import Login from './Login.vue';
import Dashboard from './Dashboard.vue';
import axios from 'axios';

export default {
  name: 'App',
  components: {
    Login,
    Dashboard,
  },
  data() {
    return {
      token: localStorage.getItem('jwt_token') || null,
    };
  },
  methods: {
    handleLoginSuccess(token) {
      localStorage.setItem('jwt_token', token);
      this.token = token;
    },
    async handleLogout() {
      try {
        await axios.post('/api/logout', {}, {
            headers: { 'Authorization': `Bearer ${this.token}` }
        });
      } catch (error) {
        console.error('Error during logout:', error);
      } finally {
        localStorage.removeItem('jwt_token');
        this.token = null;
      }
    },
  },
};
</script>
