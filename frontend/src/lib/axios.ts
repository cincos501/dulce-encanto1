import axios, { InternalAxiosRequestConfig, AxiosResponse } from 'axios'

const api = axios.create({
  baseURL: (import.meta.env.VITE_API_URL as string) || 'http://localhost:8000',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
})

// Request Interceptor: Inject X-XSRF-TOKEN header from cookie
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const xsrfCookie = document.cookie
    .split('; ')
    .find((row) => row.startsWith('XSRF-TOKEN='))
  
  if (xsrfCookie && config.headers) {
    const token = decodeURIComponent(xsrfCookie.split('=')[1])
    config.headers['X-XSRF-TOKEN'] = token
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

interface FailedQueueItem {
  resolve: () => void;
  reject: (err: any) => void;
}

let isRefreshingCsrf = false
let failedQueue: FailedQueueItem[] = []

const processQueue = (error: any) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error)
    } else {
      prom.resolve()
    }
  })
  failedQueue = []
}

interface CustomInternalAxiosRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

// Response Interceptor: Handle 419 (CSRF retry) and 401 (Unauthorized)
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error) => {
    const originalRequest = error.config as CustomInternalAxiosRequestConfig
    if (!originalRequest) {
      return Promise.reject(error)
    }

    // 1. Handle 419 Session Expired / CSRF failure
    if (error.response?.status === 419 && !originalRequest._retry) {
      originalRequest._retry = true

      if (isRefreshingCsrf) {
        return new Promise<void>((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        })
          .then(() => api(originalRequest))
          .catch((err) => Promise.reject(err))
      }

      isRefreshingCsrf = true

      try {
        // Fetch new CSRF cookie
        await api.get('/sanctum/csrf-cookie')
        processQueue(null)
        return api(originalRequest)
      } catch (csrfError) {
        processQueue(csrfError)
        return Promise.reject(csrfError)
      } finally {
        isRefreshingCsrf = false
      }
    }

    // 2. Handle 401 Unauthorized / Expelled
    if (error.response?.status === 401) {
      if (window.__logoutHandler) {
        window.__logoutHandler()
      }
      
      // Redirect if in dashboard to avoid infinite loading or stuck views
      if (window.location.pathname.startsWith('/dashboard')) {
        window.location.href = '/login'
      }
    }

    return Promise.reject(error)
  }
)

export default api
