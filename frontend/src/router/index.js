import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth.store';
import { useSucursalStore } from '@/stores/sucursal.store';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/views/LoginView.vue'),
        meta: { guest: true }
    },
    {
        path: '/sucursal/seleccion',
        name: 'SeleccionSucursal',
        component: () => import('@/views/configuracion/SucursalSelectionView.vue'),
        meta: { auth: true }
    },
    {
        path: '/',
        component: () => import('@/layouts/MainLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: () => import('@/views/DashboardView.vue')
            },
            {
                path: 'pos',
                name: 'POS',
                component: () => import('@/views/pos/PosView.vue')
            },
            {
                path: 'pos/historial',
                name: 'HistorialVentas',
                component: () => import('@/views/pos/HistoricoVentasView.vue')
            },
            {
                path: 'productos',
                name: 'Productos',
                component: () => import('@/views/inventario/ProductosView.vue')
            },
            {
                path: 'caja',
                name: 'Caja',
                component: () => import('@/views/caja/CajaView.vue')
            },
            {
                path: 'produccion',
                name: 'Produccion',
                component: () => import('@/views/produccion/ProduccionView.vue')
            },
            {
                path: 'proveedores',
                name: 'Proveedores',
                component: () => import('@/views/inventario/ProveedoresView.vue')
            },
            {
                path: 'compras',
                name: 'Compras',
                component: () => import('@/views/inventario/ComprasView.vue')
            },
            {
                path: 'compras/nueva',
                name: 'NuevaCompra',
                component: () => import('@/views/inventario/NuevaCompraView.vue')
            },
            {
                path: 'usuarios',
                name: 'Usuarios',
                component: () => import('@/views/configuracion/UsuariosView.vue'),
                meta: { auth: true, permission: 'ver usuarios' }
            },
            {
                path: 'clientes',
                name: 'Clientes',
                component: () => import('@/views/configuracion/ClientesView.vue'),
                meta: { auth: true, permission: 'ver clientes' }
            },
            {
                path: 'roles',
                name: 'Roles',
                component: () => import('@/views/configuracion/RolesView.vue'),
                meta: { auth: true, permission: 'ver configuracion' }
            },
            {
                path: 'reportes',
                name: 'Reportes',
                component: () => import('@/views/ReportesView.vue'),
                meta: { auth: true }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to) => {
    const authStore = useAuthStore();
    const sucursalStore = useSucursalStore();

    if (to.meta.auth && !authStore.isLoggedIn) {
        return { name: 'Login' };
    }

    // Forzar selección de sucursal
    if (authStore.isLoggedIn && !sucursalStore.hasSucursal && to.name !== 'SeleccionSucursal' && to.name !== 'Login') {
        return { name: 'SeleccionSucursal' };
    }

    if (to.meta.guest && authStore.isLoggedIn) {
        return { name: 'Dashboard' };
    }

    if (to.meta.permission) {
        if (!authStore.hasPermission(to.meta.permission)) {
            return { name: 'Dashboard' };
        }
    }

    return true;
});

export default router;