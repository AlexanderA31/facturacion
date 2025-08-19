<template>
  <div>
    <div v-if="!token">
      <Login v-if="currentAuthView === 'login'" @login-success="handleLoginSuccess" @show-register="currentAuthView = 'register'" />
      <Register v-else @register-success="currentAuthView = 'login'" @show-login="currentAuthView = 'login'" />
    </div>
    <div v-else>
      <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
        <p class="text-xl">Loading...</p> <!-- Or a spinner component -->
      </div>
      <div v-else>
        <AdminDashboard v-if="userRole === 'admin'" :token="token" @logout="handleLogout" />
        <Dashboard v-else :token="token" @logout="handleLogout" />
      </div>
    </div>
  </div>
</template>

<script>
import Login from './Login.vue';
import Register from './Register.vue';
import Dashboard from './Dashboard.vue';
import AdminDashboard from './AdminDashboard.vue';
import axios from 'axios';

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
      isLoading: true,
      currentAuthView: 'login',
    };
  },
  created() {
    if (this.token) {
      this.fetchUserProfile();
    } else {
      this.isLoading = false;
    }
  },
  methods: {
    async handleLoginSuccess(token) {
      this.isLoading = true;
      localStorage.setItem('jwt_token', token);
      this.token = token;
      await this.fetchUserProfile();
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api/me', {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        const user = response.data.data;
        if (user.roles && user.roles.some(role => role.name === 'admin')) {
          this.userRole = 'admin';
        } else {
          this.userRole = 'client';
        }
      } catch (error) {
        console.error('Error fetching user profile:', error);
        this.handleLogout();
      } finally {
        this.isLoading = false;
      }
    },
    async handleLogout() {
      try {
        if (this.token) {
            await axios.post('/api/logout', {}, {
                headers: { 'Authorization': `Bearer ${this.token}` }
            });
        }
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
