import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'sonner'
import { AuthProvider } from '@/app/providers/AuthContext'
import { ThemeProvider } from '@/app/providers/ThemeContext'
import { CartProvider } from '@/app/providers/CartContext'
import Home from '@/modules/catalog/pages/Home'
import Menu from '@/modules/catalog/pages/Menu'
import PublicPromotions from '@/modules/catalog/pages/Promotions'
import Contact from '@/modules/catalog/pages/Contact'
import Nosotros from '@/modules/catalog/pages/Nosotros'
import Servicios from '@/modules/catalog/pages/Servicios'
import Login from '@/modules/auth/pages/Login'
import ForgotPassword from '@/modules/auth/pages/ForgotPassword'
import ResetPassword from '@/modules/auth/pages/ResetPassword'
import Dashboard from '@/modules/dashboard/pages/Dashboard'
import Categories from '@/modules/categories/pages/Categories'
import Extras from '@/modules/extras/pages/Extras'
import Promotions from '@/modules/promotions/pages/Promotions'
import Products from '@/modules/products/pages/Products'
import Users from '@/modules/users/pages/Users'
import Suppliers from '@/modules/suppliers/pages/Suppliers'
import Supplies from '@/modules/supplies/pages/Supplies'
import Recipes from '@/modules/recipes/pages/Recipes'
import Orders from '@/modules/orders/pages/Orders'
import Production from '@/modules/production/pages/Production'
import ReportsDashboard from '@/modules/reports/pages/ReportsDashboard'
import ProtectedRoute from '@/shared/components/ProtectedRoute'
import AdminLayout from '@/app/layouts/AdminLayout'
import SplashScreen from '@/shared/components/SplashScreen'
import '@/App.css'
import React, { useState, useEffect } from 'react'

const queryClient = new QueryClient()

function App() {
  const [showSplash, setShowSplash] = useState<boolean>(true)

  useEffect(() => {
    const timer = setTimeout(() => {
      setShowSplash(false)
    }, 2000)
    return () => clearTimeout(timer)
  }, [])

  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <AuthProvider>
          <CartProvider>
            <SplashScreen isLoading={showSplash} />
            <BrowserRouter>
              <Routes>
                {/* Public routes */}
            <Route path="/" element={<Home />} />
            <Route path="/nosotros" element={<Nosotros />} />
            <Route path="/servicios" element={<Servicios />} />
            <Route path="/menu" element={<Menu />} />
            <Route path="/promociones" element={<PublicPromotions />} />
            <Route path="/contacto" element={<Contact />} />
            <Route path="/login" element={<Login />} />
            <Route path="/forgot-password" element={<ForgotPassword />} />
            <Route path="/reset-password" element={<ResetPassword />} />
            
            {/* Protected Administrative Routes using AdminLayout layout wrapper */}
            <Route 
              element={
                <ProtectedRoute>
                  <AdminLayout />
                </ProtectedRoute>
              }
            >
              <Route path="/dashboard" element={<Dashboard />} />
              
              <Route 
                path="/reports" 
                element={
                  <ProtectedRoute permission="reports.view">
                    <ReportsDashboard />
                  </ProtectedRoute>
                } 
              />
              
              <Route 
                path="/categories" 
                element={
                  <ProtectedRoute permission="categories.view">
                    <Categories />
                  </ProtectedRoute>
                } 
              />
              
              <Route 
                path="/extras" 
                element={
                  <ProtectedRoute permission="extras.view">
                    <Extras />
                  </ProtectedRoute>
                } 
              />
              
              <Route 
                path="/promotions" 
                element={
                  <ProtectedRoute permission="promotions.view">
                    <Promotions />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/products" 
                element={
                  <ProtectedRoute permission="products.view">
                    <Products />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/users" 
                element={
                  <ProtectedRoute permission="users.manage">
                    <Users />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/suppliers" 
                element={
                  <ProtectedRoute permission="suppliers.view">
                    <Suppliers />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/supplies" 
                element={
                  <ProtectedRoute permission="supplies.view">
                    <Supplies />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/recipes" 
                element={
                  <ProtectedRoute permission="recipes.view">
                    <Recipes />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/orders" 
                element={
                  <ProtectedRoute permission="orders.view">
                    <Orders />
                  </ProtectedRoute>
                } 
              />

              <Route 
                path="/production" 
                element={
                  <ProtectedRoute permission="orders.view">
                    <Production />
                  </ProtectedRoute>
                } 
              />
            </Route>
          </Routes>
          <Toaster position="top-right" richColors />
            </BrowserRouter>
          </CartProvider>
        </AuthProvider>
      </ThemeProvider>
    </QueryClientProvider>
  )
}

export default App
