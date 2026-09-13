import React, { useState, useEffect } from 'react'
import { FiDownload, FiWifiOff, FiMessageCircle, FiX } from 'react-icons/fi'

interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export default function PWAInstallBanner() {
  const [deferredPrompt, setDeferredPrompt] = useState<BeforeInstallPromptEvent | null>(null)
  const [showInstallBanner, setShowInstallBanner] = useState<boolean>(false)
  const [isOffline, setIsOffline] = useState<boolean>(!navigator.onLine)
  const [dismissOfflineNotice, setDismissOfflineNotice] = useState<boolean>(false)

  useEffect(() => {
    // Listen for PWA install prompt
    const handleBeforeInstallPrompt = (e: Event) => {
      e.preventDefault()
      setDeferredPrompt(e as BeforeInstallPromptEvent)
      
      // Check if user already dismissed banner this session
      const dismissed = sessionStorage.getItem('pwa_banner_dismissed')
      if (!dismissed) {
        setShowInstallBanner(true)
      }
    }

    // Listen for online/offline status
    const handleOnline = () => setIsOffline(false)
    const handleOffline = () => {
      setIsOffline(true)
      setDismissOfflineNotice(false)
    }

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  const handleInstallClick = async () => {
    if (!deferredPrompt) return

    await deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice

    if (outcome === 'accepted') {
      setShowInstallBanner(false)
    }
    setDeferredPrompt(null)
  }

  const dismissBanner = () => {
    setShowInstallBanner(false)
    sessionStorage.setItem('pwa_banner_dismissed', 'true')
  }

  const whatsappNumber = '59177872032' // Pastelería Dulce Encanto
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(
    '¡Hola Dulce Encanto! Quería realizar una consulta / pedido desde la aplicación web.'
  )}`

  return (
    <>
      {/* OFFLINE RESILIENCE BANNER */}
      {isOffline && !dismissOfflineNotice && (
        <div className="bg-amber-900 text-amber-50 px-4 py-2.5 shadow-md flex items-center justify-between text-xs font-semibold z-50 animate-fade-in border-b border-amber-800">
          <div className="flex items-center gap-2 max-w-4xl mx-auto flex-1">
            <FiWifiOff className="text-sm shrink-0 text-amber-300 animate-pulse" />
            <span>
              <strong>Modo Sin Conexión:</strong> Estás navegando con el catálogo en caché local. Si deseas confirmar un pedido ahora mismo, puedes realizarlo por WhatsApp.
            </span>
          </div>
          <div className="flex items-center gap-3 shrink-0 ml-4">
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-md text-xs font-bold transition-all shadow-sm active:scale-95"
            >
              <FiMessageCircle className="text-sm" />
              <span>Pedir por WhatsApp</span>
            </a>
            <button
              onClick={() => setDismissOfflineNotice(true)}
              className="text-amber-200 hover:text-white p-1 rounded transition-colors"
              title="Cerrar aviso"
            >
              <FiX className="text-sm" />
            </button>
          </div>
        </div>
      )}

      {/* PWA INSTALL PROMPT FLOATING BANNER */}
      {showInstallBanner && deferredPrompt && (
        <div className="fixed bottom-5 right-5 max-w-md bg-surface border border-primary/20 rounded-xl p-4 shadow-xl z-50 animate-bounce-in flex items-center gap-3 bg-stone-900 text-white font-sans border-l-4 border-l-primary">
          <div className="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center shrink-0">
            <FiDownload className="text-primary text-xl" />
          </div>
          <div className="flex-1 text-xs space-y-0.5">
            <h4 className="font-bold text-white text-sm">Instalar App Dulce Encanto</h4>
            <p className="text-stone-300 text-[11px]">
              Accede rápido desde tu pantalla de inicio y navega por el catálogo sin conexión.
            </p>
          </div>
          <div className="flex flex-col gap-1.5 shrink-0">
            <button
              onClick={handleInstallClick}
              className="bg-primary hover:bg-primary/90 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all active:scale-95 shadow-sm"
            >
              Instalar
            </button>
            <button
              onClick={dismissBanner}
              className="text-stone-400 hover:text-white text-[10px] text-center"
            >
              Ahora no
            </button>
          </div>
        </div>
      )}
    </>
  )
}
