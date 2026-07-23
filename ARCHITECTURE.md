# ARCHITECTURE.md

# Arquitectura Oficial
## Proyecto Dulce Encanto

Toda implementación deberá respetar estrictamente la siguiente arquitectura.

Controller

↓

Form Request

↓

DTO

↓

Service

↓

Repository Interface

↓

Repository

↓

Model (Eloquent)

↓

Resource

---

# Responsabilidad de cada capa

## Controller

Recibe solicitudes HTTP.

Nunca contiene lógica de negocio.

Nunca realiza consultas Eloquent.

Nunca abre transacciones.

---

## Form Request

Valida toda la información recibida desde el cliente.

Toda validación HTTP debe realizarse aquí.

---

## DTO

Transporta la información validada.

Debe ser inmutable.

---

## Service

Contiene toda la lógica de negocio.

Controla las transacciones.

Coordina múltiples repositorios cuando sea necesario.

Nunca devuelve respuestas HTTP.

---

## Repository Interface

Define el contrato de acceso a datos.

Los Services siempre dependen de interfaces.

---

## Repository

Contiene todas las consultas Eloquent.

Ninguna consulta debe realizarse fuera de esta capa.

---

## Model

Representa únicamente las entidades de la base de datos y sus relaciones.

No contiene lógica de negocio compleja.

---

## Resource

Formatea la respuesta JSON enviada al frontend.

Nunca realiza consultas adicionales.

---

# Frontend

React 19

Vite

TypeScript

TailwindCSS

Shadcn UI

TanStack Query

React Hook Form

Axios

Toda comunicación con el backend deberá realizarse mediante Services.

Los componentes nunca consumirán Axios directamente.

---

# Principios de Desarrollo

- Arquitectura por capas.
- Repository Pattern.
- DTO Pattern.
- SOLID.
- Validaciones mediante Form Request.
- Recursos mediante API Resource.
- Dependencia de interfaces mediante el contenedor de Laravel.
- Todas las operaciones críticas deberán ejecutarse mediante transacciones.