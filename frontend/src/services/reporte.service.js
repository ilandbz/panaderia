import api from './api.service.js';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth.store.js';

const BASE = '/reportes';
const API_BASE = import.meta.env.VITE_API_URL || 'http://panaderia_pasteleria_harold.test/api';

const reporteService = {
    // ---- Datos para gráficos ----
    getVentas(desde, hasta, agrupar = 'dia') {
        return api.get(`${BASE}/ventas`, { params: { desde, hasta, agrupar } });
    },

    getProductosTop(desde, hasta, limit = 10) {
        return api.get(`${BASE}/productos-top`, { params: { desde, hasta, limit } });
    },

    getUtilidad(desde, hasta) {
        return api.get(`${BASE}/utilidad`, { params: { desde, hasta } });
    },

    getVentasUsuario(desde, hasta) {
        return api.get(`${BASE}/ventas-usuario`, { params: { desde, hasta } });
    },

    getCaja(desde, hasta) {
        return api.get(`${BASE}/caja`, { params: { desde, hasta } });
    },

    getMermas(desde, hasta) {
        return api.get(`${BASE}/mermas`, { params: { desde, hasta } });
    },

    getStockBajo() {
        return api.get(`${BASE}/stock-bajo`);
    },

    getPorVencer(dias = 7) {
        return api.get(`${BASE}/por-vencer`, { params: { dias } });
    },

    getFormaPago(desde, hasta) {
        return api.get(`${BASE}/forma-pago`, { params: { desde, hasta } });
    },

    getInventarioActual(buscar = '', categoria = '') {
        return api.get(`${BASE}/inventario-actual`, { params: { buscar, categoria } });
    },

    getVentasDetalle(desde, hasta) {
        return api.get(`${BASE}/ventas-detalle`, { params: { desde, hasta } });
    },

    // ---- Exportar Excel (blob directo, bypassing response.data interceptor) ----
    exportVentas(desde, hasta) {
        return _downloadRequest(`${BASE}/export/ventas`, { desde, hasta });
    },

    exportProductosTop(desde, hasta) {
        return _downloadRequest(`${BASE}/export/productos-top`, { desde, hasta });
    },

    exportStockBajo() {
        return _downloadRequest(`${BASE}/export/stock-bajo`, {});
    },

    exportInventarioActual(buscar = '', categoria = '') {
        return _downloadRequest(`${BASE}/export/inventario-actual`, { buscar, categoria });
    },
};

/**
 * Hace una petición GET que devuelve un Blob (para descarga de archivos).
 * Usa axios directamente para evitar el interceptor que hace response.data
 */
async function _downloadRequest(path, params) {
    const authStore = useAuthStore();
    const response = await axios.get(`${API_BASE}${path}`, {
        params,
        responseType: 'blob',
        headers: {
            Authorization: `Bearer ${authStore.token}`,
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
    });
    return response.data; // Aquí sí es el blob real
}

/**
 * Descarga un Blob como archivo en el navegador
 */
export function downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a   = document.createElement('a');
    a.href     = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

export default reporteService;
