# DATABASE_MODEL.md

# Modelo Oficial de Base de Datos
## Proyecto: Dulce Encanto

Este documento constituye la única fuente oficial del modelo de datos del proyecto Dulce Encanto.

Toda implementación del backend, frontend, API, pruebas, seeders y futuras historias de usuario deberá respetar estrictamente este documento.

Bajo ninguna circunstancia se podrán crear tablas, relaciones, columnas o entidades nuevas sin autorización explícita.

---

# Objetivo del Modelo

El modelo fue diseñado para una repostería que vende productos mediante un catálogo web y pedidos realizados desde WhatsApp.

El sistema administra:

- Catálogo
- Presentaciones
- Extras
- Promociones
- Clientes
- Pedidos
- Inventario
- Producción
- Usuarios del sistema

No existe carrito persistente.

No existe historial de movimientos de inventario.

No existe tabla de compras.

No existe tabla de producción.

Todo debe resolverse utilizando únicamente las tablas oficiales.

---

# Módulo Catálogo

## categories

Agrupa los productos.

Relaciones

Category
    1 ---- N Products

---

## products

Representa el producto lógico.

Ejemplos

Cheesecake

Torta de Chocolate

Cupcakes

Brownies

El producto NO tiene precio.

El producto NO tiene stock.

Las imágenes NO pertenecen al producto.

Todo eso pertenece a las Presentaciones.

Campos

id

category_id

name

description

is_active

timestamps

---

## product_variants

Representa una presentación comercial del producto.

Ejemplos

Pequeña

Mediana

Grande

Caja x6

Caja x12

Caja x24

Cada presentación posee su propio:

precio

imagen

extras

promoción

personas recomendadas

Campos

id

product_id

name

price

serves_people

sku (opcional)

is_active

timestamps

Relaciones

Product

1 ---- N ProductVariant

---

## product_images

Las imágenes pertenecen a la Presentación.

Nunca al producto.

Relaciones

ProductVariant

1 ---- N ProductImages

---

## promotions

Una promoción puede aplicarse únicamente sobre una Presentación.

Nunca sobre el producto completo.

Relaciones

Promotion

1 ---- N ProductVariant

---

# Extras

## extras

Representa únicamente el catálogo de extras.

Ejemplos

Nutella

Crema

Maní

Frutilla

Chocolate

El extra NO tiene precio.

El precio depende de la presentación.

Campos

id

name

description

is_active

timestamps

---

## product_variant_extra

Relaciona una Presentación con un Extra.

Aquí se define el precio del extra para esa presentación.

Campos

product_variant_id

extra_id

price

Relaciones

ProductVariant

N ---- N Extras

mediante la tabla pivote product_variant_extra.

---

# Inventario

## suppliers

Proveedor de insumos.

---

## supplies

Materia prima.

Ejemplos

Harina

Azúcar

Leche

Chocolate

Huevos

Aquí se almacena el stock.

---

## supplier_supplies

Relaciona proveedores con insumos.

También almacena el precio actual de compra.

---

## recipes

Relaciona las presentaciones con los insumos necesarios para producirlas.

Cada receta indica:

insumo

cantidad requerida

unidad

observaciones

---

# Clientes

## customers

Representa al cliente que realiza el pedido.

Campos recomendados

nombre

apellido

correo

teléfono

dirección

referencia

is_active

La dirección únicamente será utilizada cuando el pedido sea para delivery.

Si el cliente recoge en tienda puede permanecer vacía.

---

# Pedidos

## orders

Representa el pedido completo.

Estados oficiales

Pendiente

Confirmado

En preparación

Listo

Entregado

Cancelado

El stock solamente puede descontarse cuando el pedido pasa a:

En preparación

Nunca antes.

---

## order_items

Detalle del pedido.

Cada registro representa una Presentación comprada.

Nunca un producto.

---

## order_item_extras

Extras seleccionados por el cliente.

Cada extra almacena el precio aplicado en el momento de la compra para mantener el histórico aunque posteriormente cambien los precios.

---

# Seguridad

## users

Usuarios administrativos.

---

## roles

Roles mediante Spatie Permission.

---

## permissions

Permisos mediante Spatie Permission.

---

# Consideraciones Importantes

El modelo fue diseñado para reutilizar las mismas entidades durante todo el sistema.

No deben existir tablas adicionales para:

Compras

Movimientos

Producción

Kardex

Inventario

Delivery

Pasarela de Pago

Chatbot

WhatsApp

Todos esos procesos deben utilizar las tablas oficiales existentes.

Cualquier modificación futura al modelo deberá actualizar primero este documento antes de implementarse en el código.