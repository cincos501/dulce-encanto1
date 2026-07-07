import React, { useState } from 'react'
import { cn } from '@/shared/utils/cn'
import { FiCamera } from 'react-icons/fi'

export interface ImageUploaderProps {
  onFileSelect: (file: File) => void;
  isLoading?: boolean;
  maxSizeMB?: number;
  acceptedTypes?: string[];
}

export const ImageUploader: React.FC<ImageUploaderProps> = ({
  onFileSelect,
  isLoading = false,
  maxSizeMB = 2,
  acceptedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
}) => {
  const [dragActive, setDragActive] = useState<boolean>(false)

  const handleDrag = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true)
    } else if (e.type === 'dragleave') {
      setDragActive(false)
    }
  }

  const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    setDragActive(false)

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      validateAndProcess(e.dataTransfer.files[0])
    }
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      validateAndProcess(e.target.files[0])
    }
  }

  const validateAndProcess = (file: File) => {
    if (!acceptedTypes.includes(file.type)) {
      alert(`Formato no permitido. Solo se aceptan: ${acceptedTypes.join(', ')}`)
      return
    }

    const maxSize = maxSizeMB * 1024 * 1024
    if (file.size > maxSize) {
      alert(`El tamaño de la imagen no puede superar los ${maxSizeMB}MB.`)
      return
    }

    onFileSelect(file)
  }

  return (
    <div
      onDragEnter={handleDrag}
      onDragOver={handleDrag}
      onDragLeave={handleDrag}
      onDrop={handleDrop}
      onClick={() => {
        const input = document.getElementById('ds-image-file-input') as HTMLInputElement | null
        if (input) input.click()
      }}
      className={cn(
        'border-2 border-dashed rounded-2xl p-8 text-center flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer min-h-[220px] bg-surface',
        dragActive 
          ? 'border-primary bg-secondary/10' 
          : 'border-border hover:border-primary'
      )}
    >
      <input
        id="ds-image-file-input"
        type="file"
        className="hidden"
        accept="image/*"
        onChange={handleFileChange}
      />
      
      {isLoading ? (
        <div className="space-y-2 animate-pulse">
          <svg className="animate-spin h-10 w-10 text-primary mx-auto" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <p className="text-xs text-primary font-bold">Subiendo imagen...</p>
        </div>
      ) : (
        <>
          <FiCamera className="text-4xl text-text-sub/50 shrink-0" />
          <div className="space-y-1">
            <p className="text-sm font-bold text-primary">Arrastra una imagen aquí</p>
            <p className="text-xs text-text-sub">o haz clic para explorar tus archivos</p>
          </div>
          <div className="bg-background border border-border px-3 py-1.5 rounded-lg text-[10px] text-text-sub font-semibold mt-2 select-none">
            JPG, PNG, WEBP • Max {maxSizeMB}MB
          </div>
        </>
      )}
    </div>
  )
}
