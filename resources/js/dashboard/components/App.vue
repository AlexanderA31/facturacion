<template>
  <div>
    <div v-if="!token">
      <Login v-if="currentAuthView === 'login'" @login-success="handleLoginSuccess" @show-register="currentAuthView = 'register'" />
      <Register v-else @register-success="currentAuthView = 'login'" @show-login="currentAuthView = 'login'" />
    </div>
    <div v-else>
      <AdminDashboard v-if="userRole === 'admin'" :token="token" @logout="handleLogout" />
      <Dashboard v-else :token="token" @logout="handleLogout" />
    </div>
  </div>
</template>

<script>
import Login from './Login.vue';
import Register from './Register.vue';
import Dashboard from './Dashboard.vue';
import AdminDashboard from './AdminDashboard.vue'; // Will be created
import axios from 'axios';
import { decodeJwt } from '../../utils/jwt';

export default {
  name: 'App',
  components: {
    Login,
    Register,
    Dashboard,
    AdminDashboard,
  },
  data() {
    return {
      token: localStorage.getItem('jwt_token') || null,
      userRole: null,
      currentAuthView: 'login',
    };
  },
  created() {
    if (this.token) {
      const decodedToken = decodeJwt(this.token);
      this.userRole = decodedToken ? decodedToken.role : null;
    }
  },
  methods: {
    handleLoginSuccess({ token, role }) {
      localStorage.setItem('jwt_token', token);
      this.token = token;
      this.userRole = role;
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
        localStorage.removeItem('correctiveBillingData');
        this.token = null;
        this.userRole = null;
      }
    },
  },
};
</script>
