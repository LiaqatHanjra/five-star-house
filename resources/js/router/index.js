import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'
import HomeView from '../views/HomeView.vue'
import ServicesView from '../views/ServicesView.vue'
import ServiceDetailView from '../views/ServiceDetailView.vue'
import BookingView from '../views/BookingView.vue'
import BookWizardView from '../views/BookWizardView.vue'
import PaymentView from '../views/PaymentView.vue'
import SuccessView from '../views/SuccessView.vue'
import LoginView from '../views/admin/LoginView.vue'
import AdminLayout from '../views/admin/AdminLayout.vue'
import DashboardView from '../views/admin/DashboardView.vue'
import SiteImagesView from '../views/admin/SiteImagesView.vue'
import ServicesAdminView from '../views/admin/ServicesAdminView.vue'
import BookingsAdminView from '../views/admin/BookingsAdminView.vue'
import PaymentsAdminView from '../views/admin/PaymentsAdminView.vue'
import CustomersAdminView from '../views/admin/CustomersAdminView.vue'
import SettingsAdminView from '../views/admin/SettingsAdminView.vue'
import BookingPageAdminView from '../views/admin/BookingPageAdminView.vue'
import GoogleCalendarView from '../views/admin/GoogleCalendarView.vue'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        { path: '', name: 'home', component: HomeView, meta: { titleKey: 'meta.home' } },
        { path: 'services', name: 'services', component: ServicesView, meta: { titleKey: 'meta.services' } },
        { path: 'services/:slug', name: 'service', component: ServiceDetailView, meta: { titleKey: 'meta.service' } },
        { path: 'book', name: 'book', component: BookingView, meta: { titleKey: 'meta.book' } },
        { path: 'book/service/:slug', name: 'book-service', component: BookWizardView, meta: { titleKey: 'meta.bookService' } },
        { path: 'book/:kind', name: 'book-kind', component: BookWizardView, meta: { titleKey: 'meta.book' } },
        { path: 'pay/:reference', name: 'pay', component: PaymentView, meta: { titleKey: 'meta.payment' } },
        { path: 'booked/:reference', name: 'booked', component: SuccessView, meta: { titleKey: 'meta.confirmed' } },
      ],
    },
    { path: '/admin/login', name: 'login', component: LoginView, meta: { title: 'Admin Login' } },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { auth: true, title: 'Admin' },
      children: [
        { path: '', name: 'dashboard', component: DashboardView, meta: { auth: true, title: 'Dashboard' } },
        { path: 'home-images', name: 'home-images', component: SiteImagesView, meta: { auth: true, page: 'home', title: 'Home Images' } },
        { path: 'services', name: 'admin-services', component: ServicesAdminView, meta: { auth: true, title: 'Services' } },
        { path: 'booking-page', name: 'booking-page', component: BookingPageAdminView, meta: { auth: true, title: 'Booking page' } },
        { path: 'booking-images', name: 'booking-images', component: SiteImagesView, meta: { auth: true, page: 'booking', title: 'Booking Images' } },
        { path: 'bookings', name: 'admin-bookings', component: BookingsAdminView, meta: { auth: true, title: 'Bookings' } },
        { path: 'calendar', name: 'admin-calendar', component: GoogleCalendarView, meta: { auth: true, title: 'Google Calendar' } },
        { path: 'payments', name: 'admin-payments', component: PaymentsAdminView, meta: { auth: true, title: 'Payments' } },
        { path: 'customers', name: 'admin-customers', component: CustomersAdminView, meta: { auth: true, title: 'Customers' } },
        { path: 'settings', name: 'admin-settings', component: SettingsAdminView, meta: { auth: true, title: 'Site settings' } },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const token = localStorage.getItem('admin_token')
  if (to.meta.auth && !token) {
    return { path: '/admin/login', query: { redirect: to.fullPath } }
  }
  if (to.path === '/admin/login' && token) {
    return { path: '/admin' }
  }
})

export default router
