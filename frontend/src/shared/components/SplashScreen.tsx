import React from 'react'
import logo from '@/assets/splash/splash-logo.svg'

interface SplashScreenProps {
  isLoading: boolean;
}

export default function SplashScreen({ isLoading }: SplashScreenProps) {
  return (
    <div
      className={`fixed inset-0 z-50 flex flex-col items-center justify-center bg-[#FAF6F0] dark:bg-[#181615] transition-opacity duration-500 ${
        isLoading ? 'opacity-100' : 'opacity-0 pointer-events-none'
      }`}
    >
      <div className="flex flex-col items-center space-y-6">
        <img
          src={logo}
          alt="Dulce Encanto Logo"
          className="w-40 h-40"
        />
        <p className="text-stone-600 dark:text-stone-400 font-serif italic text-base tracking-wide animate-pulse">
          Horneando momentos especiales...
        </p>
        {/* 3-dots animation */}
        <div className="flex space-x-2 pt-2 justify-center">
          <div className="w-2.5 h-2.5 bg-[#851c36] rounded-full animate-bounce [animation-delay:-0.3s]"></div>
          <div className="w-2.5 h-2.5 bg-[#851c36] rounded-full animate-bounce [animation-delay:-0.15s]"></div>
          <div className="w-2.5 h-2.5 bg-[#851c36] rounded-full animate-bounce"></div>
        </div>
      </div>
    </div>
  )
}
