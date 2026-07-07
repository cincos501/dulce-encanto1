import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'sonner'
import { AuthProvider } from '@/app/providers/AuthContext'
import { ThemeProvider } from '@/app/providers/ThemeContext'
import Home from '@/modules/catalog/pages/Home'
import Menu from '@/modules/catalog/pages/Menu'
import PublicPromotions from '@/modules/catalog/pages/Promotions'
import Contact from '@/modules/catalog/pages/Contact'
import Login from '@/modules/auth/pages/Login'
import Dashboard from '@/modules/dashboard/pages/Dashboard'
import Categories from '@/modules/categories/pages/Categories'
import Extras from '@/modules/extras/pages/Extras'
import Promotions from '@/modules/promotions/pages/Promotions'
import Products from '@/modules/products/pages/Products'
import Users from '@/modules/users/pages/Users'
import ProtectedRoute from '@/shared/components/ProtectedRoute'
import AdminLayout from '@/app/layouts/AdminLayout'
import '@/App.css'

const queryClient = new QueryClient()

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <AuthProvider>
          <BrowserRouter>
          <Routes>
            {/* Public routes */}
            <Route path="/" element={<Home />} />
            <Route path="/menu" element={<Menu />} />
            <Route path="/promociones" element={<PublicPromotions />} />
            <Route path="/contacto" element={<Contact />} />
            <Route path="/login" element={<Login />} />
            
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
            </Route>
          </Routes>
          <Toaster position="top-right" richColors />
        </BrowserRouter>
      </AuthProvider>
      </ThemeProvider>
    </QueryClientProvider>
  )
}

export default App
