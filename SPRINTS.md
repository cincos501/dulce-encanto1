# Planificación de Sprints - Proyecto Dulce Encanto

Este documento define la planificación oficial de los Sprint del proyecto.

Toda auditoría, documentación, diagramas, reportes y análisis del sistema deben tomar este documento como referencia.

---

# Sprint 1

## Objetivo

Desarrollar la infraestructura base del sistema y el catálogo administrativo.

## Alcance

- Configuración inicial del proyecto Laravel + React.
- Autenticación mediante Laravel Sanctum.
- Autorización mediante Spatie Permission.
- Administración de usuarios.
- Administración de roles y permisos.
- Gestión de categorías.
- Gestión de productos.
- Gestión de variantes.
- Gestión de imágenes.
- Gestión de extras.
- Gestión de promociones.
- Desarrollo del catálogo web público.

---

# Sprint 2

## Objetivo

Implementar el módulo operativo interno de la repostería.

## Alcance

- Gestión de insumos.
- Gestión de proveedores.
- Gestión de recetas.
- Relación entre recetas e insumos.
- Cálculo automático del costo de producción.
- Control automático del inventario.
- Descuento automático de insumos al confirmar pedidos.
- Reportes administrativos.

---

# Sprint 3

## Objetivo

Automatizar la atención al cliente mediante WhatsApp e Inteligencia Artificial.

## Alcance

- Integración con WhatsApp.
- Integración con Chatwoot.
- Integración con Inteligencia Artificial.
- Chatbot conversacional.
- Interpretación automática de pedidos.
- Respuesta a preguntas frecuentes.
- Gestión del contexto conversacional.
- Derivación de conversaciones a un operador humano.
- Gestión del flujo de producción.
- Cambio automático de estados del pedido.
- Notificaciones automáticas al cliente.

---

# Sprint 4

## Objetivo

Implementar el proceso completo de ventas y pagos.

## Alcance

- Generación de pedidos desde el catálogo web.
- Integración con la API Market del Banco Económico (Baneco).
- Generación de códigos QR.
- Validación automática de pagos.
- Recepción de Webhooks.
- Confirmación automática de transacciones.
- Actualización automática del estado del pedido.
- Inicio del proceso de preparación después del pago.

---

# Consideraciones

Este documento representa la planificación oficial del proyecto.

Cualquier auditoría del sistema debe comparar la implementación actual con estos Sprint para determinar:

- funcionalidades implementadas;
- funcionalidades parcialmente implementadas;
- funcionalidades pendientes;
- porcentaje de avance de cada Sprint;
- dependencias entre Sprint;
- estado general del proyecto.

No deben utilizarse otros criterios para calcular el avance del sistema.