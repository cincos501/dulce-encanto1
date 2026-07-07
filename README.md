# Dulce Encanto — Plataforma Integral de Gestión y Catálogo

¡Bienvenido al repositorio de **Dulce Encanto**! Este proyecto es una solución digital de nivel empresarial diseñada para una pastelería artesanal premium. Cuenta con un panel administrativo robusto y un catálogo interactivo público responsivo.

El sistema se compone de una arquitectura desacoplada:
1. **Backend:** Desarrollado con **Laravel 11**, exponiendo una API REST robusta y segura.
2. **Frontend:** Desarrollado con **React 19**, **TypeScript**, **Vite** y **Tailwind CSS v4**, estructurado bajo una arquitectura modular limpia por dominios.

---

## 📸 Capturas de la Interfaz

*(Próximamente: Añadir capturas de pantalla de la interfaz de usuario aquí)*

* **Catálogo Público (Light Mode):** `<!-- Colocar imagen aquí -->`
* **Catálogo Público (Dark Mode):** `<!-- Colocar imagen aquí -->`
* **Panel de Administración (Dashboard):** `<!-- Colocar imagen aquí -->`
* **Gestor de Productos y Variantes:** `<!-- Colocar imagen aquí -->`

---

## 🛠️ Arquitectura y Estructura del Proyecto

El proyecto está organizado en una estructura monorepo que contiene carpetas independientes para el backend y el frontend:

```text
dulce-encanto/
├── backend/            # API REST en Laravel 11
└── frontend/           # Single Page Application (SPA) en React 19
```

### 1. Estructura del Backend (Laravel)
Sigue el estándar de Laravel con controladores desacoplados para la API y la administración:
* `app/Http/Controllers/Api/`: Controladores públicos para el catálogo de postres.
* `app/Http/Controllers/Api/Admin/`: Controladores protegidos para la administración de Categorías, Productos, Variantes, Extras, Promociones y Personal.
* `app/Models/`: Modelos de Eloquent con relaciones complejas de variantes, imágenes y extras.
* `database/migrations/`: Estructura de base de datos relacional.
* `database/seeders/`: Semillas de pruebas con roles (Spatie) y cuentas de ejemplo.

### 2. Estructura del Frontend (React + TypeScript)
El frontend aplica **Clean Architecture** combinada con **Modular Domain-Driven Design**:
* `src/app/`: Proveedores globales (`AuthContext`, `ThemeContext`), layouts administrativos y ruteo centralizado.
* `src/design-system/`: Componentes UI atómicos, reutilizables y autocontenidos (`Button`, `SearchInput`, `ImageUploader`, `ImagePreview`, `Card`, etc.).
* `src/modules/`: Módulos encapsulados por dominio de negocio (cada uno contiene sus propias páginas, componentes y estados):
  * `auth/`: Control de sesión y acceso seguro.
  * `catalog/`: Páginas públicas (Inicio, Menú, Promociones, Contacto).
  * `categories/`, `products/`, `extras/`, `promotions/`, `users/`: Módulos de administración.
* `src/shared/`: Componentes transversales (como el `CrudFramework` genérico), configuraciones de cliente Axios y tipados TypeScript estrictos.

---

## 🚀 Requisitos Previos

Antes de clonar e instalar el proyecto, asegúrate de tener instalado:
* **PHP:** >= 8.2
* **Composer** (Gestor de dependencias de PHP)
* **Node.js:** >= 20.x
* **NPM** o **Yarn**
* **Base de Datos:** MySQL / PostgreSQL o SQLite.

---

## 🔧 Configuración e Instalación del Backend (Laravel)

1. **Clonar el repositorio e ingresar a la carpeta del backend:**
   ```bash
   git clone <url-del-repositorio>
   cd dulce-encanto/backend
   ```

2. **Instalar dependencias de Composer:**
   ```bash
   composer install
   ```

3. **Configurar el archivo de entorno:**
   Copia el archivo de ejemplo y configura tu conexión a la base de datos y llaves de servicios:
   ```bash
   cp .env.example .env
   ```
   *Nota: Configura `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. Si usas Supabase Storage para las imágenes, completa las variables de entorno de Supabase.*

4. **Generar la clave de la aplicación:**
   ```bash
   php artisan key:generate
   ```

5. **Ejecutar migraciones y poblar la base de datos (Seeders):**
   Este comando creará las tablas, roles Spatie y los usuarios de prueba:
   ```bash
   php artisan migrate --seed
   ```

6. **Crear el enlace simbólico de almacenamiento:**
   ```bash
   php artisan storage:link
   ```

7. **Iniciar el servidor local del Backend:**
   ```bash
   php artisan serve
   ```
   El backend estará disponible en `http://127.0.0.1:8000`.

---

## 💻 Configuración e Instalación del Frontend (React)

1. **Ingresar a la carpeta del frontend:**
   ```bash
   cd ../frontend
   ```

2. **Instalar dependencias de Node:**
   ```bash
   npm install
   ```

3. **Configurar el archivo de entorno del Frontend:**
   Crea un archivo `.env` en la raíz de la carpeta `frontend/` y añade la URL base de tu backend local:
   ```env
   VITE_API_URL=http://127.0.0.1:8000
   ```

4. **Iniciar el servidor de desarrollo de Vite:**
   ```bash
   npm run dev
   ```
   El frontend estará corriendo en `http://localhost:5173/` (o port alternativo indicado en la consola).

5. **Compilar para Producción:**
   ```bash
   npm run build
   ```

---

## 🔑 Credenciales de Prueba

Puedes iniciar sesión en el panel administrativo (`http://localhost:5173/login`) utilizando los siguientes usuarios de semilla predeterminados:

| Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `admin@dulceencanto.com` | `password` |
| **Repostero** | `repostero@dulceencanto.com` | `password` |
| **Operaciones** | `operaciones@dulceencanto.com` | `password` |

---

## 📦 Dependencias Principales Utilizadas

### Backend (PHP/Laravel)
* `spatie/laravel-permission`: Control de accesos y roles a nivel de API.
* `laravel/sanctum`: Autenticación robusta basada en tokens y cookies de sesión.

### Frontend (React/TypeScript)
* `react`: v19.x (Nuevas APIs de rendimiento y renderizado).
* `@tanstack/react-query`: v5.x (Gestión y caché de estado asíncrono/servicios).
* `react-router-dom`: v7.x (Enrutamiento del catálogo y panel de administración).
* `react-hook-form` + `zod`: Manejo de formularios y validación estricta de esquemas de datos.
* `tailwindcss`: v4.0 (Motor CSS de alta velocidad y personalización).
* `react-icons`: Colección de iconos vectorizados de la familia Feather Icons.
* `sonner`: Componente premium de notificaciones tipo Toast.
