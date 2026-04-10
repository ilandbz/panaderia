<template>
  <div class="reportes-view">
    <!-- ===== HEADER ===== -->
    <div class="page-header mb-4">
      <div class="d-flex align-items-center gap-3">
        <div class="page-icon">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div>
          <h2 class="page-title mb-0">Reportes & Analítica</h2>
          <p class="text-muted mb-0 small">Información gerencial del negocio</p>
        </div>
      </div>
    </div>

    <!-- ===== FILTROS ===== -->
    <div class="card filter-card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">
              <i class="fas fa-calendar-alt me-1 text-warning"></i> Fecha Inicio
            </label>
            <input type="date" v-model="filtros.desde" class="form-control" @change="cargarTodo" />
          </div>
          <div class="col-md-3">
            <label class="form-label">
              <i class="fas fa-calendar-alt me-1 text-warning"></i> Fecha Fin
            </label>
            <input type="date" v-model="filtros.hasta" class="form-control" @change="cargarTodo" />
          </div>
          <div class="col-md-2">
            <label class="form-label">Agrupar por</label>
            <select v-model="filtros.agrupar" class="form-select" @change="cargarVentas">
              <option value="dia">Día</option>
              <option value="semana">Semana</option>
              <option value="mes">Mes</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label d-block">&nbsp;</label>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-warning btn-sm" @click="cargarTodo" :disabled="cargando">
                <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': cargando }"></i>
                Actualizar
              </button>
              <div class="dropdown">
                <button class="btn btn-outline-success btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-file-excel me-1"></i> Exportar Excel
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <button class="dropdown-item" @click="exportar('ventas')" :disabled="exportando">
                      <i class="fas fa-receipt me-2 text-success"></i> Ventas del período
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" @click="exportar('productos')" :disabled="exportando">
                      <i class="fas fa-bread-slice me-2 text-warning"></i> Productos más vendidos
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" @click="exportar('stock')" :disabled="exportando">
                      <i class="fas fa-exclamation-triangle me-2 text-danger"></i> Stock bajo
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== TABS ===== -->
    <ul class="nav nav-tabs reporte-tabs mb-4" id="reporteTabs">
      <li class="nav-item" v-for="tab in tabs" :key="tab.id">
        <button
          class="nav-link"
          :class="{ active: tabActivo === tab.id }"
          @click="tabActivo = tab.id"
        >
          <i :class="tab.icon + ' me-1'"></i> {{ tab.label }}
        </button>
      </li>
    </ul>

    <!-- ===== SPINNER global ===== -->
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border" style="color: var(--jara-primary);" role="status"></div>
      <p class="mt-2 text-muted">Cargando datos...</p>
    </div>

    <template v-else>
      <!-- ============ TAB: VENTAS ============ -->
      <div v-show="tabActivo === 'ventas'">
        <!-- KPI Cards de Utilidad -->
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="kpi-card kpi-primary">
              <div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(utilidad.total_venta) }}</div>
                <div class="kpi-label">Total Ventas</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="kpi-card kpi-danger">
              <div class="kpi-icon"><i class="fas fa-coins"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(utilidad.total_costo) }}</div>
                <div class="kpi-label">Costo Total</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="kpi-card kpi-success">
              <div class="kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(utilidad.utilidad) }}</div>
                <div class="kpi-label">Utilidad Estimada</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="kpi-card kpi-info">
              <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">{{ utilidad.margen_pct }}%</div>
                <div class="kpi-label">Margen de Ganancia</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Ventas por Período -->
        <div class="row g-4 mb-4">
          <div class="col-lg-8">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-chart-line me-2 text-warning"></i>Evolución de Ventas
                </h6>
                <span class="badge bg-warning-subtle text-warning">{{ ventasData.length }} registros</span>
              </div>
              <div class="card-body chart-container">
                <canvas ref="canvasVentas" id="chartVentas"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card h-100">
              <div class="card-header">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-credit-card me-2 text-info"></i>Forma de Pago
                </h6>
              </div>
              <div class="card-body chart-container d-flex align-items-center justify-content-center">
                <canvas ref="canvasPago" id="chartPago"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla resumen de ventas -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
              <i class="fas fa-table me-2 text-warning"></i>Resumen por Período
            </h6>
            <button class="btn btn-outline-success btn-sm" @click="exportar('ventas')" :disabled="exportando">
              <i class="fas fa-file-excel me-1"></i> Excel
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Período</th>
                    <th class="text-end">Ventas</th>
                    <th class="text-end">Ingresos</th>
                    <th class="text-end">Ticket Promedio</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in ventasData" :key="row.periodo">
                    <td class="fw-semibold">{{ row.periodo }}</td>
                    <td class="text-end">
                      <span class="badge bg-info-subtle text-info">{{ row.cantidad_ventas }}</span>
                    </td>
                    <td class="text-end fw-bold text-success">S/ {{ fmt(row.total_ventas) }}</td>
                    <td class="text-end text-muted">S/ {{ fmt(row.ticket_promedio) }}</td>
                  </tr>
                  <tr v-if="ventasData.length === 0">
                    <td colspan="4" class="text-center text-muted py-4">
                      <i class="fas fa-chart-line fa-2x mb-2 d-block opacity-25"></i>
                      Sin datos en el período seleccionado
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ TAB: PRODUCTOS ============ -->
      <div v-show="tabActivo === 'productos'">
        <div class="row g-4">
          <div class="col-lg-7">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-bread-slice me-2 text-warning"></i>Top 10 Productos Más Vendidos
                </h6>
                <button class="btn btn-outline-success btn-sm" @click="exportar('productos')" :disabled="exportando">
                  <i class="fas fa-file-excel me-1"></i> Excel
                </button>
              </div>
              <div class="card-body chart-container-tall">
                <canvas ref="canvasProductos" id="chartProductos"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-list-ol me-2 text-warning"></i>Ranking de Productos
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th class="text-end">Cant.</th>
                        <th class="text-end">S/</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in productosData" :key="item.id">
                        <td>
                          <span class="rank-badge" :class="rankClass(idx)">{{ idx + 1 }}</span>
                        </td>
                        <td>
                          <div class="fw-semibold">{{ item.nombre }}</div>
                          <small class="text-muted">{{ item.categoria }}</small>
                        </td>
                        <td class="text-end">
                          <span class="badge bg-warning-subtle text-warning">{{ item.cantidad_vendida }}</span>
                        </td>
                        <td class="text-end fw-bold">{{ fmt(item.total_facturado) }}</td>
                      </tr>
                      <tr v-if="productosData.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">Sin datos</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ TAB: VENDEDORES ============ -->
      <div v-show="tabActivo === 'vendedores'">
        <div class="row g-4">
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-users me-2 text-warning"></i>Ventas por Vendedor
                </h6>
              </div>
              <div class="card-body chart-container d-flex align-items-center justify-content-center">
                <canvas ref="canvasVendedores" id="chartVendedores"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="card h-100">
              <div class="card-header">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-table me-2 text-warning"></i>Detalle por Vendedor
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Vendedor</th>
                        <th class="text-center">Transacciones</th>
                        <th class="text-end">Total Vendido</th>
                        <th class="text-end">Ticket Prom.</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(v, idx) in vendedoresData" :key="v.id">
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm" :style="avatarStyle(idx)">
                              {{ v.vendedor.charAt(0) }}
                            </div>
                            <span class="fw-semibold">{{ v.vendedor }}</span>
                          </div>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-info-subtle text-info">{{ v.cantidad_ventas }}</span>
                        </td>
                        <td class="text-end fw-bold text-success">S/ {{ fmt(v.total_vendido) }}</td>
                        <td class="text-end text-muted">S/ {{ fmt(v.ticket_promedio) }}</td>
                      </tr>
                      <tr v-if="vendedoresData.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">Sin datos</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ TAB: CAJA ============ -->
      <div v-show="tabActivo === 'caja'">
        <!-- Resumen caja KPIs -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="kpi-card kpi-success">
              <div class="kpi-icon"><i class="fas fa-arrow-down"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(cajaData.resumen?.total_ingresos) }}</div>
                <div class="kpi-label">Total Ingresos ({{ cajaData.resumen?.cant_ingresos }} ops.)</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi-card kpi-danger">
              <div class="kpi-icon"><i class="fas fa-arrow-up"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(cajaData.resumen?.total_egresos) }}</div>
                <div class="kpi-label">Total Egresos ({{ cajaData.resumen?.cant_egresos }} ops.)</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi-card" :class="saldoNeto >= 0 ? 'kpi-success' : 'kpi-danger'">
              <div class="kpi-icon"><i class="fas fa-balance-scale"></i></div>
              <div class="kpi-body">
                <div class="kpi-value">S/ {{ fmt(saldoNeto) }}</div>
                <div class="kpi-label">Saldo Neto del Período</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla movimientos caja -->
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0 fw-bold">
              <i class="fas fa-list me-2 text-warning"></i>Movimientos de Caja
            </h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
              <table class="table table-hover mb-0">
                <thead class="sticky-top">
                  <tr>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th class="text-end">Monto</th>
                    <th>Fecha</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(m, idx) in cajaData.movimientos" :key="idx">
                    <td>
                      <span
                        class="badge"
                        :class="m.tipo === 'ingreso' ? 'bg-success' : 'bg-danger'"
                      >
                        <i :class="m.tipo === 'ingreso' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'" class="me-1"></i>
                        {{ m.tipo }}
                      </span>
                    </td>
                    <td>{{ m.concepto || '—' }}</td>
                    <td class="text-end fw-bold" :class="m.tipo === 'ingreso' ? 'text-success' : 'text-danger'">
                      S/ {{ fmt(m.monto) }}
                    </td>
                    <td class="text-muted small">{{ formatDate(m.fecha) }}</td>
                  </tr>
                  <tr v-if="!cajaData.movimientos?.length">
                    <td colspan="4" class="text-center text-muted py-4">Sin movimientos en el período</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ TAB: INVENTARIO ============ -->
      <div v-show="tabActivo === 'inventario'">
        <div class="row g-4">
          <!-- Stock Bajo -->
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                  Stock Bajo
                  <span class="badge bg-danger ms-2">{{ stockBajoData.length }}</span>
                </h6>
                <button class="btn btn-outline-success btn-sm" @click="exportar('stock')" :disabled="exportando">
                  <i class="fas fa-file-excel me-1"></i> Excel
                </button>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                  <table class="table table-hover mb-0">
                    <thead class="sticky-top">
                      <tr>
                        <th>Producto</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Mínimo</th>
                        <th class="text-center">Falta</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in stockBajoData" :key="item.id">
                        <td class="fw-semibold">{{ item.nombre }}</td>
                        <td class="text-center">
                          <span class="badge bg-danger">{{ item.stock }} {{ item.unidad }}</span>
                        </td>
                        <td class="text-center text-muted">{{ item.stock_minimo }}</td>
                        <td class="text-center">
                          <span class="badge bg-warning text-dark">{{ item.diferencia }}</span>
                        </td>
                      </tr>
                      <tr v-if="stockBajoData.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">
                          <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                          ¡Todo el stock está en niveles óptimos!
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Por vencer -->
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-calendar-times me-2 text-warning"></i>
                  Por Vencer (próx. 7 días)
                  <span class="badge bg-warning text-dark ms-2">{{ porVencerData.length }}</span>
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                  <table class="table table-hover mb-0">
                    <thead class="sticky-top">
                      <tr>
                        <th>Producto</th>
                        <th class="text-center">Stock</th>
                        <th>Vence</th>
                        <th class="text-center">Días</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in porVencerData" :key="item.id">
                        <td class="fw-semibold">{{ item.nombre }}</td>
                        <td class="text-center">{{ item.stock }}</td>
                        <td>{{ item.fecha_vencimiento }}</td>
                        <td class="text-center">
                          <span
                            class="badge"
                            :class="item.dias_restantes <= 2 ? 'bg-danger' : item.dias_restantes <= 5 ? 'bg-warning text-dark' : 'bg-info'"
                          >
                            {{ item.dias_restantes }}d
                          </span>
                        </td>
                      </tr>
                      <tr v-if="porVencerData.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">
                          <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                          Sin productos próximos a vencer
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Mermas -->
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0 fw-bold">
                  <i class="fas fa-trash-alt me-2 text-secondary"></i>Mermas del Período
                </h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Producto</th>
                        <th>Motivo</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Costo Pérdida</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(m, idx) in mermasData" :key="idx">
                        <td class="fw-semibold">{{ m.nombre }}</td>
                        <td><span class="badge bg-secondary">{{ m.motivo || 'Sin motivo' }}</span></td>
                        <td class="text-end">{{ m.cantidad }}</td>
                        <td class="text-end fw-bold text-danger">S/ {{ fmt(m.costo_perdida) }}</td>
                      </tr>
                      <tr v-if="mermasData.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">Sin mermas registradas</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import reporteService, { downloadBlob } from '@/services/reporte.service.js';

Chart.register(...registerables);

// ===================== STATE =====================
const cargando   = ref(false);
const exportando = ref(false);
const tabActivo  = ref('ventas');

const filtros = ref({
  desde:   hoy(-30),
  hasta:   hoy(0),
  agrupar: 'dia',
});

const ventasData    = ref([]);
const productosData = ref([]);
const vendedoresData= ref([]);
const cajaData      = ref({ movimientos: [], resumen: {} });
const mermasData    = ref([]);
const stockBajoData = ref([]);
const porVencerData = ref([]);
const pagoData      = ref([]);
const utilidad      = ref({ total_venta: 0, total_costo: 0, utilidad: 0, margen_pct: 0 });

// Referencias a canvas
const canvasVentas    = ref(null);
const canvasPago      = ref(null);
const canvasProductos = ref(null);
const canvasVendedores= ref(null);

// Instancias de Chart
let chartVentas     = null;
let chartPago       = null;
let chartProductos  = null;
let chartVendedores = null;

// ===================== TABS =====================
const tabs = [
  { id: 'ventas',     label: 'Ventas',      icon: 'fas fa-chart-line' },
  { id: 'productos',  label: 'Productos',   icon: 'fas fa-bread-slice' },
  { id: 'vendedores', label: 'Vendedores',  icon: 'fas fa-users' },
  { id: 'caja',       label: 'Caja',        icon: 'fas fa-cash-register' },
  { id: 'inventario', label: 'Inventario',  icon: 'fas fa-boxes' },
];

// ===================== COMPUTED =====================
const saldoNeto = computed(() => {
  const r = cajaData.value.resumen ?? {};
  return (r.total_ingresos ?? 0) - (r.total_egresos ?? 0);
});

// ===================== HELPERS =====================
function hoy(offset = 0) {
  const d = new Date();
  d.setDate(d.getDate() + offset);
  return d.toISOString().split('T')[0];
}

function fmt(val) {
  const n = parseFloat(val) || 0;
  return n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleString('es-PE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function rankClass(idx) {
  return idx === 0 ? 'rank-gold' : idx === 1 ? 'rank-silver' : idx === 2 ? 'rank-bronze' : 'rank-normal';
}

const AVATAR_COLORS = ['#C8971A', '#2D8A4E', '#2980B9', '#8E44AD', '#C0392B', '#16A085'];
function avatarStyle(idx) {
  const c = AVATAR_COLORS[idx % AVATAR_COLORS.length];
  return { background: c, color: '#fff' };
}

// ===================== CARGAR DATOS =====================
async function cargarTodo() {
  cargando.value = true;
  try {
    await Promise.all([
      cargarVentas(),
      cargarProductos(),
      cargarVendedores(),
      cargarCaja(),
      cargarMermas(),
      cargarStockBajo(),
      cargarPorVencer(),
      cargarPago(),
      cargarUtilidad(),
    ]);
  } finally {
    cargando.value = false;
    await nextTick();
    renderCharts();
  }
}

async function cargarVentas() {
  try {
    const res = await reporteService.getVentas(filtros.value.desde, filtros.value.hasta, filtros.value.agrupar);
    ventasData.value = res.data ?? [];
  } catch { ventasData.value = []; }
}

async function cargarProductos() {
  try {
    const res = await reporteService.getProductosTop(filtros.value.desde, filtros.value.hasta);
    productosData.value = res.data ?? [];
  } catch { productosData.value = []; }
}

async function cargarVendedores() {
  try {
    const res = await reporteService.getVentasUsuario(filtros.value.desde, filtros.value.hasta);
    vendedoresData.value = res.data ?? [];
  } catch { vendedoresData.value = []; }
}

async function cargarCaja() {
  try {
    const res = await reporteService.getCaja(filtros.value.desde, filtros.value.hasta);
    cajaData.value = res.data ?? { movimientos: [], resumen: {} };
  } catch { cajaData.value = { movimientos: [], resumen: {} }; }
}

async function cargarMermas() {
  try {
    const res = await reporteService.getMermas(filtros.value.desde, filtros.value.hasta);
    mermasData.value = res.data ?? [];
  } catch { mermasData.value = []; }
}

async function cargarStockBajo() {
  try {
    const res = await reporteService.getStockBajo();
    stockBajoData.value = res.data ?? [];
  } catch { stockBajoData.value = []; }
}

async function cargarPorVencer() {
  try {
    const res = await reporteService.getPorVencer(7);
    porVencerData.value = res.data ?? [];
  } catch { porVencerData.value = []; }
}

async function cargarPago() {
  try {
    const res = await reporteService.getFormaPago(filtros.value.desde, filtros.value.hasta);
    pagoData.value = res.data ?? [];
  } catch { pagoData.value = []; }
}

async function cargarUtilidad() {
  try {
    const res = await reporteService.getUtilidad(filtros.value.desde, filtros.value.hasta);
    utilidad.value = res.data ?? {};
  } catch { utilidad.value = {}; }
}

// ===================== GRÁFICOS =====================
function destroyChart(instance) {
  if (instance) { instance.destroy(); }
}

function renderCharts() {
  renderChartVentas();
  renderChartPago();
  renderChartProductos();
  renderChartVendedores();
}

function renderChartVentas() {
  destroyChart(chartVentas);
  if (!canvasVentas.value) return;

  const labels = ventasData.value.map(r => r.periodo);
  const totales = ventasData.value.map(r => r.total_ventas);
  const cantidades = ventasData.value.map(r => r.cantidad_ventas);

  chartVentas = new Chart(canvasVentas.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Ingresos (S/)',
          data: totales,
          backgroundColor: 'rgba(200, 151, 26, 0.7)',
          borderColor: '#C8971A',
          borderWidth: 2,
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          label: 'N° Ventas',
          data: cantidades,
          type: 'line',
          borderColor: '#2980B9',
          backgroundColor: 'rgba(41, 128, 185, 0.1)',
          borderWidth: 2,
          pointRadius: 4,
          tension: 0.4,
          yAxisID: 'y1',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              if (ctx.datasetIndex === 0) return ` S/ ${ctx.parsed.y.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
              return ` ${ctx.parsed.y} ventas`;
            },
          },
        },
      },
      scales: {
        y: {
          type: 'linear',
          position: 'left',
          ticks: { callback: v => `S/ ${v.toLocaleString('es-PE')}` },
          grid: { color: '#f0e8d5' },
        },
        y1: {
          type: 'linear',
          position: 'right',
          grid: { drawOnChartArea: false },
        },
      },
    },
  });
}

function renderChartPago() {
  destroyChart(chartPago);
  if (!canvasPago.value || !pagoData.value.length) return;

  const COLORS = ['#C8971A', '#2D8A4E', '#2980B9', '#8E44AD', '#C0392B', '#16A085', '#E67E22'];

  chartPago = new Chart(canvasPago.value, {
    type: 'doughnut',
    data: {
      labels: pagoData.value.map(r => r.forma_pago),
      datasets: [{
        data: pagoData.value.map(r => r.total),
        backgroundColor: COLORS.slice(0, pagoData.value.length),
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => ` S/ ${ctx.parsed.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`,
          },
        },
      },
    },
  });
}

function renderChartProductos() {
  destroyChart(chartProductos);
  if (!canvasProductos.value) return;

  const top = productosData.value.slice(0, 10);

  chartProductos = new Chart(canvasProductos.value, {
    type: 'bar',
    data: {
      labels: top.map(r => r.nombre),
      datasets: [{
        label: 'Total Facturado (S/)',
        data: top.map(r => r.total_facturado),
        backgroundColor: top.map((_, i) => `hsla(${37 + i * 18}, 75%, ${45 + i * 3}%, 0.85)`),
        borderRadius: 6,
        borderWidth: 1,
        borderColor: '#C8971A',
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` S/ ${ctx.parsed.x.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`,
          },
        },
      },
      scales: {
        x: {
          ticks: { callback: v => `S/ ${v.toLocaleString('es-PE')}` },
          grid: { color: '#f0e8d5' },
        },
        y: { grid: { display: false } },
      },
    },
  });
}

function renderChartVendedores() {
  destroyChart(chartVendedores);
  if (!canvasVendedores.value || !vendedoresData.value.length) return;

  const COLORS = ['#C8971A', '#2D8A4E', '#2980B9', '#8E44AD', '#C0392B'];

  chartVendedores = new Chart(canvasVendedores.value, {
    type: 'pie',
    data: {
      labels: vendedoresData.value.map(r => r.vendedor),
      datasets: [{
        data: vendedoresData.value.map(r => r.total_vendido),
        backgroundColor: COLORS.slice(0, vendedoresData.value.length),
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => ` S/ ${ctx.parsed.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`,
          },
        },
      },
    },
  });
}

// Re-renderizar charts al cambiar de tab
watch(tabActivo, async () => {
  await nextTick();
  renderCharts();
});

// ===================== EXPORTAR =====================
async function exportar(tipo) {
  exportando.value = true;
  try {
    let blob, filename;

    if (tipo === 'ventas') {
      const res = await reporteService.exportVentas(filtros.value.desde, filtros.value.hasta);
      blob = res;
      filename = `ventas_${filtros.value.desde}_${filtros.value.hasta}.xlsx`;
    } else if (tipo === 'productos') {
      const res = await reporteService.exportProductosTop(filtros.value.desde, filtros.value.hasta);
      blob = res;
      filename = `productos_vendidos_${filtros.value.desde}_${filtros.value.hasta}.xlsx`;
    } else if (tipo === 'stock') {
      const res = await reporteService.exportStockBajo();
      blob = res;
      filename = 'stock_bajo.xlsx';
    }

    if (blob) downloadBlob(blob, filename);

  } catch (e) {
    console.error('Error al exportar:', e);
    alert('Error al generar el archivo Excel. Intenta nuevamente.');
  } finally {
    exportando.value = false;
  }
}

// ===================== LIFECYCLE =====================
onMounted(cargarTodo);
</script>

<style scoped>
.reportes-view {
  padding: 1.5rem;
  min-height: 100vh;
  background: var(--jara-cream, #FDF6E3);
}

/* ---- Header ---- */
.page-header { }
.page-icon {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #C8971A, #A67C14);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 1.4rem;
  box-shadow: 0 4px 12px rgba(200, 151, 26, 0.35);
}
.page-title { font-size: 1.4rem; font-weight: 700; color: #2C2C2C; }

/* ---- Filter card ---- */
.filter-card {
  border-radius: 12px;
  border: none;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

/* ---- Tabs ---- */
.reporte-tabs {
  border-bottom: 2px solid #f0e8d5;
}
.reporte-tabs .nav-link {
  color: #7A7A7A;
  border: none;
  border-bottom: 3px solid transparent;
  padding: 0.65rem 1.15rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  border-radius: 0;
  margin-bottom: -2px;
}
.reporte-tabs .nav-link:hover { color: #C8971A; background: rgba(200,151,26,0.06); }
.reporte-tabs .nav-link.active {
  color: #C8971A;
  border-bottom-color: #C8971A;
  background: transparent;
  font-weight: 600;
}

/* ---- KPI Cards ---- */
.kpi-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0,0,0,0.07);
  border-left: 4px solid;
  transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.kpi-primary  { border-color: #C8971A; }
.kpi-success  { border-color: #2D8A4E; }
.kpi-danger   { border-color: #C0392B; }
.kpi-info     { border-color: #2980B9; }
.kpi-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem;
}
.kpi-primary .kpi-icon  { background: rgba(200,151,26,0.12); color: #C8971A; }
.kpi-success .kpi-icon  { background: rgba(45,138,78,0.12);  color: #2D8A4E; }
.kpi-danger  .kpi-icon  { background: rgba(192,57,43,0.12);  color: #C0392B; }
.kpi-info    .kpi-icon  { background: rgba(41,128,185,0.12); color: #2980B9; }
.kpi-value {
  font-size: 1.55rem;
  font-weight: 700;
  color: #2C2C2C;
  line-height: 1.2;
}
.kpi-label {
  font-size: 0.78rem;
  color: #7A7A7A;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 2px;
}

/* ---- Charts ---- */
.chart-container {
  position: relative;
  height: 280px;
}
.chart-container-tall {
  position: relative;
  height: 380px;
  padding: 1rem;
}

/* ---- Rank badges ---- */
.rank-badge {
  display: inline-flex;
  width: 26px; height: 26px;
  border-radius: 50%;
  align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700;
}
.rank-gold   { background: #FFD700; color: #5a4200; }
.rank-silver { background: #C0C0C0; color: #3a3a3a; }
.rank-bronze { background: #CD7F32; color: #fff; }
.rank-normal { background: #e9ecef; color: #495057; }

/* ---- Avatar ---- */
.avatar-sm {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.85rem;
  flex-shrink: 0;
}

/* ---- Misc ---- */
.sticky-top { position: sticky; top: 0; z-index: 1; }
.bg-warning-subtle { background-color: rgba(200,151,26,0.12) !important; }
.bg-info-subtle    { background-color: rgba(41,128,185,0.12) !important; }
</style>
