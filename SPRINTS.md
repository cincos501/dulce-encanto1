# Historias de Usuario - Proyecto Dulce Encanto

Este documento contiene las Historias de Usuario oficiales del proyecto Dulce Encanto.

Las Historias de Usuario representan los requerimientos funcionales que deben ser considerados como referencia para:

- Auditoría del sistema.
- Análisis de implementación.
- Validación de funcionalidades.
- Actualización de diagramas UML.
- Comparación entre alcance planificado y código existente.

No deben agregarse funcionalidades que no estén descritas en este documento.

---

# Sprint 1

## HU-01 - Administrar categorías del catálogo

**Usuario:** Administrador  
**Alias:** Categorías  
**Requerimiento:** RF01  
**Prioridad:** Alta  
**Puntos:** 1

### Descripción

Como Administrador, quiero gestionar las categorías de repostería en el panel administrativo para mantener clasificados y organizados los productos del catálogo.

### Funcionalidad esperada

- Crear categorías.
- Editar categorías.
- Eliminar categorías.
- Visualizar listado de categorías.
- Asociar categorías con productos.

---

# HU-02 - Administrar productos, variantes e imágenes del catálogo

**Usuarios:** Administrador, Repostero  
**Alias:** Catálogo  
**Requerimientos:** RF02, RF03, RF04  
**Prioridad:** Alta  
**Puntos:** 5

### Descripción

Como Administrador o Repostero, quiero administrar productos de repostería definiendo variantes e imágenes para mantener actualizado el catálogo comercial.

### Funcionalidad esperada

- Crear productos.
- Editar productos.
- Administrar variantes.
- Definir tamaños, presentaciones y precios.
- Gestionar imágenes.
- Asociar imágenes principales y secundarias.

---

# HU-03 - Administrar extras asociados a variantes

**Usuarios:** Administrador, Repostero  
**Alias:** Extras  
**Requerimiento:** RF05  
**Prioridad:** Alta  
**Puntos:** 2

### Descripción

Como Administrador o Repostero, quiero registrar adicionales para personalizar productos y permitir sumar costos adicionales.

### Funcionalidad esperada

- Crear extras.
- Editar extras.
- Eliminar extras.
- Asociar extras con variantes.
- Gestionar precios adicionales.

---

# HU-04 - Administrar promociones

**Usuario:** Administrador  
**Alias:** Promociones  
**Requerimiento:** RF06  
**Prioridad:** Media  
**Puntos:** 1

### Descripción

Como Administrador, quiero configurar promociones para aplicar descuentos en productos durante períodos determinados.

### Funcionalidad esperada

- Crear promociones.
- Definir tipo de descuento.
- Definir fechas de vigencia.
- Asociar promociones con productos.
- Activar o desactivar promociones.

---

# HU-05 - Administrar usuarios y roles

**Usuario:** Administrador  
**Alias:** Usuarios y Roles  
**Requerimiento:** RF20  
**Prioridad:** Alta  
**Puntos:** 2

### Descripción

Como Administrador, quiero gestionar usuarios y asignar roles para controlar los accesos dentro del sistema.

### Funcionalidad esperada

- Crear usuarios.
- Editar usuarios.
- Asignar roles.
- Gestionar permisos.
- Controlar acceso según responsabilidades.

---

# HU-07 - Visualizar catálogo público

**Usuario:** Cliente  
**Alias:** Catálogo Web  
**Requerimiento:** RF10  
**Prioridad:** Alta  
**Puntos:** 3

### Descripción

Como Cliente, quiero explorar el catálogo público para conocer los productos disponibles antes de realizar una compra.

### Funcionalidad esperada

- Visualizar productos.
- Filtrar por categorías.
- Buscar productos.
- Visualizar variantes.
- Visualizar promociones activas.

---

# Sprint 2

---

# HU-06 - Gestionar insumos, proveedores y recetas

**Usuario:** Administrador / Repostero  
**Alias:** Inventario  
**Requerimientos:** RF07, RF08, RF09  
**Prioridad:** Alta  
**Puntos:** 2

### Descripción

Como usuario interno, quiero administrar insumos, proveedores y recetas para controlar la producción.

### Funcionalidad esperada

- Registrar proveedores.
- Registrar insumos.
- Crear recetas.
- Asociar insumos con recetas.
- Calcular costos de producción.
- Controlar disponibilidad.

---

# HU-08 - Gestionar pedidos desde catálogo web

**Usuario:** Cliente / Administrador  
**Alias:** Pedidos Web  
**Requerimiento:** RF13  
**Prioridad:** Alta  
**Puntos:** 5

### Descripción

Como cliente, quiero realizar pedidos desde el catálogo web para calcular automáticamente el costo de mi compra.

### Funcionalidad esperada

- Crear pedidos.
- Agregar productos.
- Seleccionar variantes.
- Agregar extras.
- Aplicar promociones.
- Calcular total.

---

# HU-11 - Gestionar producción y seguimiento de pedidos

**Usuario:** Repostero  
**Alias:** Producción  
**Requerimientos:** RF16, RF17  
**Prioridad:** Alta  
**Puntos:** 3

### Descripción

Como Repostero, quiero controlar el proceso de producción para conocer el estado de preparación de los pedidos.

### Funcionalidad esperada

- Visualizar pedidos pendientes.
- Cambiar estados.
- Controlar preparación.
- Finalizar producción.

---

# HU-12 - Control automático de consumo de insumos

**Usuario:** Sistema  
**Alias:** Control de Inventario  
**Requerimiento:** RF18  
**Prioridad:** Media  
**Puntos:** 2

### Descripción

Como sistema, quiero descontar automáticamente los insumos utilizados para mantener actualizado el inventario.

### Funcionalidad esperada

- Leer recetas asociadas.
- Calcular consumo.
- Descontar inventario.
- Validar disponibilidad.

---

# HU-14 - Generar reportes administrativos

**Usuario:** Administrador  
**Alias:** Reportes  
**Requerimiento:** RF22  
**Prioridad:** Media  
**Puntos:** 2

### Descripción

Como Administrador, quiero visualizar reportes para tomar decisiones sobre el negocio.

### Funcionalidad esperada

- Reportes de ventas.
- Reportes de pedidos.
- Reportes de inventario.
- Indicadores administrativos.

---

# Sprint 3

---

# HU-09 - Automatizar atención mediante WhatsApp

**Usuario:** Cliente  
**Alias:** Chatbot IA  
**Requerimientos:** RF11, RF12, RF21  
**Prioridad:** Alta  
**Puntos:** 8

### Descripción

Como Cliente, quiero recibir atención automática mediante WhatsApp para consultar productos y realizar pedidos.

### Funcionalidad esperada

- Integración WhatsApp.
- Chatbot conversacional.
- Respuestas automáticas.
- Consulta de catálogo.
- Interpretación de pedidos.
- Gestión de contexto.
- Derivación a operador.

---

# HU-13 - Notificar pedido listo

**Usuario:** Cliente  
**Alias:** Notificaciones  
**Requerimiento:** RF19  
**Prioridad:** Alta  
**Puntos:** 2

### Descripción

Como Cliente, quiero recibir una notificación cuando mi pedido esté listo para recoger o entregar.

### Funcionalidad esperada

- Envío automático de mensajes.
- Integración con WhatsApp.
- Aviso de cambio de estado.

---

# Sprint 4

---

# HU-10 - Gestionar pago mediante código QR

**Usuario:** Cliente  
**Alias:** Pagos  
**Requerimientos:** RF14, RF15  
**Prioridad:** Alta  
**Puntos:** 5

### Descripción

Como Cliente, quiero pagar mediante código QR para confirmar mi compra automáticamente.

### Funcionalidad esperada

- Generar código QR.
- Integrar proveedor bancario.
- Validar pagos.
- Recibir confirmación automática.
- Actualizar estado del pedido.

---

# Reglas generales del proyecto

Estas Historias de Usuario representan el alcance funcional oficial del sistema Dulce Encanto.

Toda auditoría debe comparar:

- Historias de Usuario.
- Sprint planificados.
- Código existente.
- Migraciones.
- Modelos.
- Servicios.
- Diagramas UML.

El análisis debe identificar:

- Funcionalidades implementadas.
- Funcionalidades parcialmente implementadas.
- Funcionalidades pendientes.
- Dependencias entre módulos.

No deben crearse funcionalidades fuera del alcance definido.