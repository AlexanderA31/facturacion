<template>
  <div>
    <div v-if="!token">
      <Login v-if="currentAuthView === 'login'" @login-success="handleLoginSuccess" @show-register="currentAuthView = 'register'" />
      <Register v-else @register-success="currentAuthView = 'login'" @show-login="currentAuthView = 'login'" />
    </div>
    <Dashboard v-else :token="token" @logout="handleLogout" />
  </div>
</template>

<script>
import Login from './Login.vue';
import Register from './Register.vue';
import Dashboard from './Dashboard.vue';
import axios from 'axios';

export default {
  name: 'App',
  components: {
    Login,
    Register,
    Dashboard,
  },
  data() {
    return {
      token: localStorage.getItem('jwt_token') || null,
      currentAuthView: 'login',
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
