import { useNavigate } from 'react-router-dom'

export default function AccessDenied() {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-gradient-to-tr from-rose-100 via-amber-50 to-pink-100 flex items-center justify-center p-4 relative overflow-hidden">
      {/* Decorative blobs */}
      <div className="absolute top-10 left-10 w-72 h-72 bg-rose-300 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse"></div>
      <div className="absolute bottom-10 right-10 w-72 h-72 bg-amber-300 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse delay-500"></div>

      <div className="w-full max-w-lg bg-white/80 backdrop-blur-lg rounded-3xl border border-rose-100/50 shadow-2xl p-8 sm:p-12 text-center space-y-6 relative overflow-hidden">
        {/* Warning Icon */}
        <div className="mx-auto w-20 h-20 bg-rose-50 rounded-full flex items-center justify-center border border-rose-100/60 text-4xl shadow-inner animate-bounce">
          🔒
        </div>

        <div className="space-y-2">
          <span className="bg-rose-50 text-rose-600 text-xs font-extrabold px-3 py-1 rounded-full border border-rose-100/50 tracking-wider uppercase">
            Error 403 - Acceso Restringido
          </span>
          <h1 className="text-3xl font-extrabold text-stone-900 tracking-tight">
            Área Reservada
          </h1>
          <p className="text-stone-500 text-sm leading-relaxed max-w-sm mx-auto">
            Tu perfil de usuario no cuenta con los privilegios o permisos necesarios para visualizar esta sección de la pastelería.
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
          <button
            onClick={() => navigate('/dashboard')}
            className="w-full sm:w-auto bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-rose-200/50 hover:shadow-rose-300/80 transition-all duration-200 active:scale-95 text-sm"
          >
            Ir al Dashboard
          </button>
          <button
            onClick={() => navigate(-1)}
            className="w-full sm:w-auto bg-white hover:bg-stone-50 text-stone-755 border border-stone-200 px-6 py-3 rounded-2xl font-bold transition-all duration-200 active:scale-95 text-sm"
          >
            Volver Atrás
          </button>
        </div>

        <div className="border-t border-stone-100/65 pt-6 text-xs text-stone-400">
          Si crees que esto es un error, por favor contacta al administrador del sistema.
        </div>
      </div>
    </div>
  )
}
