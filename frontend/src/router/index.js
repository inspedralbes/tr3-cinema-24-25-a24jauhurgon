// Router — Totes les rutes de l'aplicació Última Hora BCN
import { createRouter, createWebHistory } from 'vue-router'
import { useCuaStore } from '../stores/cuaStore.js'
import { useAuthStore } from '../stores/authStore.js'
import LoginView from '../views/LoginView.vue'
import VolsLastMinuteView from '../views/VolsLastMinuteView.vue'
import CuaAccesView from '../views/CuaAccesView.vue'
import CuaEsperaView from '../views/CuaEsperaView.vue'
import SeatmapView from '../views/SeatmapView.vue'
import ResumCompraView from '../views/ResumCompraView.vue'
import CompraCompletadaView from '../views/CompraCompletadaView.vue'
import SessioExpiradaView from '../views/SessioExpiradaView.vue'
import HistorialVolsView from '../views/HistorialVolsView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import AdminUsersView from '../components/AdminUsersView.vue'
import AdminSeatmapView from '../views/AdminSeatmapView.vue'
import AdminScannerView from '../views/AdminScannerView.vue'

var routes = [
    {
        path: '/',
        name: 'login',
        component: LoginView
    },
    {
        path: '/vols',
        name: 'vols',
        component: VolsLastMinuteView
    },
    {
        path: '/vol/:id/cua',
        name: 'cuaAcces',
        component: CuaAccesView
    },
    {
        path: '/vol/:id/esperant',
        name: 'cuaEspera',
        component: CuaEsperaView
    },
    {
        path: '/vol/:id/seients',
        name: 'seatmap',
        component: SeatmapView,
        meta: { calTicket: true }
    },
    {
        path: '/vol/:id/resum',
        name: 'resumCompra',
        component: ResumCompraView,
        meta: { calTicket: true }
    },
    {
        path: '/compra/:id/completada',
        name: 'compraCompletada',
        component: CompraCompletadaView
    },
    {
        path: '/sessio-expirada',
        name: 'sessioExpirada',
        component: SessioExpiradaView
    },
    {
        path: '/historial',
        name: 'historial',
        component: HistorialVolsView
    },
    {
        path: '/admin',
        name: 'admin',
        component: AdminDashboardView
    },
    {
        path: '/admin/usuaris',
        name: 'admin-usuaris',
        component: AdminUsersView,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/scanner',
        name: 'admin-scanner',
        component: AdminScannerView,
        meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/vol/:id/seatmap',
        name: 'adminSeatmap',
        component: AdminSeatmapView
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'noTrobat',
        redirect: '/vols'
    }
]

var router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: routes
})

// Guard de navegació: protegir rutes que requereixen ticket de cua
router.beforeEach(function (to, from, next) {
    // Inicialitzar clientId si no existeix
    var authStore = useAuthStore()
    authStore.inicialitzarClient()

    // Si la ruta requereix un ticket de cua vàlid
    if (to.meta.calTicket) {
        var cuaStore = useCuaStore()
        // Verificar que l'usuari té un ticket autoritzat
        if (cuaStore.estat !== 'autoritzat' || !cuaStore.ticket) {
            // Redirigir a la cua d'accés del vol
            next({ name: 'cuaAcces', params: { id: to.params.id } })
            return
        }
    }

    next()
})

export default router
