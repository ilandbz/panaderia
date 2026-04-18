<template>
  <div class="login-container vh-100 d-flex align-items-center justify-content-center bg-brown">
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width: 900px; width: 100%;">
      <div class="row g-0">
        <!-- Brand/Image Section -->
        <div class="col-lg-6 d-none d-lg-block bg-primary p-5 text-white d-flex flex-column justify-content-center text-center">
          <i class="fas fa-wheat-awn fs-1 mb-4"></i>
          <h2 class="display-6 fw-bold">Panadería Jara</h2>
          <p class="lead opacity-75">Sistema Integral de Gestión de Ventas e Inventario</p>
          <div class="mt-4">
             <img src="/img/logo.png" class="img-fluid rounded-circle shadow" style="max-width: 200px;" alt="Logo">
          </div>
        </div>

        <!-- Form Section -->
        <div class="col-lg-6 p-5 bg-white">
          <div class="mb-4">
            <h3 class="fw-bold text-brown">Bienvenido</h3>
            <p class="text-muted small">Ingresa tus credenciales para acceder al sistema</p>
          </div>

          <form @submit.prevent="handleLogin">
            <div class="mb-3">
              <label class="form-label text-brown small fw-bold">Correo Electrónico</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                <input v-model="form.email" type="email" class="form-control border-0 bg-light" placeholder="ejemplo@jara.com" required>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label text-brown small fw-bold">Contraseña</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                <input v-model="form.password" type="password" class="form-control border-0 bg-light" placeholder="••••••••" required>
              </div>
            </div>

            <div v-if="error" class="alert alert-danger p-2 small mb-4 border-0">
               <i class="fas fa-exclamation-circle me-1"></i> {{ error }}
            </div>

            <button :disabled="loading" type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                Ingresar al Sistema
            </button>
          </form>

          <div class="mt-5 text-center">
            <p class="text-muted extrasmall">Jara Bakeware &copy; {{ new Date().getFullYear() }} · Versión 1.0</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  email: '',
  password: ''
});

const loading = ref(false);
const error = ref(null);

const handleLogin = async () => {
  loading.value = true;
  error.value = null;
  try {
    await authStore.login(form.value);
    router.push('/');
  } catch (err) {
    error.value = err.message || 'Error al iniciar sesión';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.bg-brown {
  background-color: #451a03;
}
.text-brown {
  color: #451a03;
}
.extrasmall {
  font-size: 0.7rem;
}
.form-control:focus {
  box-shadow: none;
  background-color: #f3f4f6 !important;
}
</style>
