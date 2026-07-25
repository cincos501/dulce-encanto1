# Modelo Oficial de Base de Datos
## Proyecto: Dulce Encanto (Actualizado)

Este documento constituye la única fuente oficial del modelo de datos real implementado en el proyecto Dulce Encanto. Refleja fielmente las tablas de MySQL de las migraciones del sistema, así como la persistencia transitoria de sesiones conversacionales en Redis.

---

# Mapeo del Modelo de Datos (MySQL)

## 1. Módulo Catálogo y Administración

### `categories`
*   **Descripción:** Agrupa los productos lógicos de repostería.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `name` (String/Varchar)
    *   `description` (Text, Nullable)
    *   `is_active` (Boolean, Default true)
    *   `deleted_at` (Timestamp, Soft deletes)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Category` (1) ---- (N) `Products`

### `products`
*   **Descripción:** Representa el producto lógico o receta base (ej. Selva Negra, Cheesecake). No posee precio ni stock directo.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `category_id` (Bigint, Foreign Key -> `categories`, Nullable, Delete set null)
    *   `name` (String/Varchar)
    *   `description` (Text, Nullable)
    *   `is_active` (Boolean, Default true)
    *   `was_active` (Boolean, Default true)
    *   `deleted_at` (Timestamp, Soft deletes)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Category` (N) ---- (1) `Product`
    *   `Product` (1) ---- (N) `ProductVariants`

### `product_variants`
*   **Descripción:** Presentación comercial específica de un producto (ej. Mediana, Caja x6, Porción). Aquí se define el precio y empaque.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `product_id` (Bigint, Foreign Key -> `products`, Cascade delete)
    *   `name` (String/Varchar)
    *   `sku` (String/Varchar, Unique index)
    *   `price` (Decimal 10,2)
    *   `serves_people` (Integer, Nullable)
    *   `is_active` (Boolean, Default true)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Product` (N) ---- (1) `ProductVariant`
    *   `ProductVariant` (1) ---- (N) `ProductImages`
    *   `ProductVariant` (N) ---- (N) `Promotions` (a través de `promotion_product_variant`)
    *   `ProductVariant` (N) ---- (N) `Extras` (a través de `product_variant_extras`)

### `product_images`
*   **Descripción:** Almacena las URLs de las imágenes asociadas a una presentación específica.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `product_variant_id` (Bigint, Foreign Key -> `product_variants`, Cascade delete)
    *   `image_url` (String/Varchar)
    *   `is_primary` (Boolean, Default false)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `ProductVariant` (N) ---- (1) `ProductImage`

---

## 2. Módulo de Extras y Promociones

### `extras`
*   **Descripción:** Catálogo de adicionales disponibles (ej. Nutella, Frutillas, Crema). El precio no se define aquí, sino por variante.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `name` (String/Varchar)
    *   `description` (Text, Nullable)
    *   `is_active` (Boolean, Default true)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Extra` (N) ---- (N) `ProductVariants` (a través de `product_variant_extras`)

### `product_variant_extras`
*   **Descripción:** Tabla pivote que relaciona una variante con sus extras y define el precio del extra para esa presentación específica.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `product_variant_id` (Bigint, Foreign Key -> `product_variants`, Cascade delete)
    *   `extra_id` (Bigint, Foreign Key -> `extras`, Cascade delete)
    *   `price` (Decimal 10,2)
    *   `created_at` / `updated_at` (Timestamps)

### `promotions`
*   **Descripción:** Ofertas y descuentos temporales aplicables a variantes de productos.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `name` (String/Varchar)
    *   `description` (Text, Nullable)
    *   `image_url` (String/Varchar, Nullable)
    *   `discount_type` (Enum: `'percentage'`, `'fixed'`, Default `'percentage'`)
    *   `discount` (Decimal 10,2)
    *   `start_date` (DateTime)
    *   `end_date` (DateTime)
    *   `is_active` (Boolean, Default true)
    *   `deleted_at` (Timestamp, Soft deletes)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Promotion` (N) ---- (N) `ProductVariants` (a través de `promotion_product_variant`)

### `promotion_product_variant`
*   **Descripción:** Tabla pivote que relaciona muchas promociones con muchas variantes de productos.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `product_variant_id` (Bigint, Foreign Key -> `product_variants`, Cascade delete)
    *   `promotion_id` (Bigint, Foreign Key -> `promotions`, Cascade delete)
    *   `created_at` / `updated_at` (Timestamps)

---

## 3. Módulo de Inventario e Insumos

### `supplies`
*   **Descripción:** Materias primas (ej. Harina, Huevo, Leche) con control de stock físico y costos promedios.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `name` (String/Varchar)
    *   `unit` (String/Varchar: kg, gr, lt, un, etc.)
    *   `stock` (Decimal 12,4, Default 0.0000)
    *   `minimum_stock` (Decimal 12,4, Default 0.0000)
    *   `average_cost` (Decimal 10,2, Default 0.00)
    *   `is_active` (Boolean, Default true)
    *   `deleted_at` (Timestamp, Soft deletes)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Supply` (N) ---- (N) `Suppliers` (a través de `supplier_supplies`)
    *   `Supply` (1) ---- (N) `Recipes`

### `suppliers`
*   **Descripción:** Proveedores de insumos y materias primas.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `business_name` (String/Varchar)
    *   `phone` (String/Varchar)
    *   `email` (String/Varchar, Nullable)
    *   `address` (Text, Nullable)
    *   `is_active` (Boolean, Default true)
    *   `deleted_at` (Timestamp, Soft deletes)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Supplier` (N) ---- (N) `Supplies` (a través de `supplier_supplies`)

### `supplier_supplies`
*   **Descripción:** Relaciona insumos con sus proveedores e incluye el costo de compra en la última transacción.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `supplier_id` (Bigint, Foreign Key -> `suppliers`, Cascade delete)
    *   `supply_id` (Bigint, Foreign Key -> `supplies`, Cascade delete)
    *   `purchase_price` (Decimal 10,2)
    *   `created_at` / `updated_at` (Timestamps)

### `recipes`
*   **Descripción:** Fórmulas de recetarios de repostería. Mapea la cantidad exacta de insumos necesarios para confeccionar una variante de producto.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `product_variant_id` (Bigint, Foreign Key -> `product_variants`, Cascade delete)
    *   `supply_id` (Bigint, Foreign Key -> `supplies`, Cascade delete)
    *   `quantity` (Decimal 12,4)
    *   `unit` (String/Varchar)
    *   `observation` (String/Varchar, Nullable)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `ProductVariant` (1) ---- (N) `Recipes`
    *   `Supply` (1) ---- (N) `Recipes`

---

## 4. Módulo de Pedidos y Transacciones

### `customers`
*   **Descripción:** Representa al cliente que realiza el pedido (vía Web o WhatsApp).
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `full_name` (String/Varchar)
    *   `phone` (String/Varchar)
    *   `email` (String/Varchar, Nullable)
    *   `chatwoot_conversation_id` (Bigint, Nullable, index)
    *   `created_at` / `updated_at` (Timestamps)
*   **Particularidad de Implementación:** El campo `email` se reutiliza para serializar en formato JSON los detalles de la entrega (`delivery_type`, `address`, `observations`) del cliente, optimizando el esquema de base de datos.
*   **Relaciones:**
    *   `Customer` (1) ---- (N) `Orders`

### `orders`
*   **Descripción:** Encabezado de los pedidos del sistema.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `customer_id` (Bigint, Foreign Key -> `customers`, Nullable, Delete set null)
    *   `status` (Enum: `'Pendiente'`, `'Confirmado'`, `'En preparación'`, `'Listo'`, `'Entregado'`, `'Cancelado'`, Default `'Pendiente'`)
    *   `total` (Decimal 10,2, Default 0.00)
    *   `delivery_date` (DateTime, Nullable)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Customer` (N) ---- (1) `Order`
    *   `Order` (1) ---- (N) `OrderItems`
    *   `Order` (1) ---- (N) `Payments`

### `order_items`
*   **Descripción:** Detalle de productos solicitados por pedido.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `order_id` (Bigint, Foreign Key -> `orders`, Cascade delete)
    *   `product_variant_id` (Bigint, Foreign Key -> `product_variants`, Cascade delete)
    *   `quantity` (Integer, Default 1)
    *   `price` (Decimal 10,2)
    *   `created_at` / `updated_at` (Timestamps)
*   **Relaciones:**
    *   `Order` (N) ---- (1) `OrderItem`
    *   `OrderItem` (1) ---- (N) `OrderItemExtras`

### `order_item_extras`
*   **Descripción:** Adicionales/extras seleccionados y pagados para un ítem del pedido.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `order_item_id` (Bigint, Foreign Key -> `order_items`, Cascade delete)
    *   `extra_id` (Bigint, Foreign Key -> `extras`, Cascade delete)
    *   `quantity` (Integer, Default 1)
    *   `price` (Decimal 10,2)
    *   `created_at` / `updated_at` (Timestamps)

### `payments`
*   **Descripción:** Registro opcional e histórico de pagos vinculados a un pedido.
*   **Campos:**
    *   `id` (Bigint, Primary Key, Auto-increment)
    *   `order_id` (Bigint, Foreign Key -> `orders`, Cascade delete)
    *   `amount` (Decimal 10,2)
    *   `payment_method` (String/Varchar)
    *   `transaction_code` (String/Varchar, Nullable)
    *   `status` (String/Varchar, Default `'Pendiente'`)
    *   `payment_date` (DateTime)
    *   `created_at` / `updated_at` (Timestamps)

---

# Almacenamiento Transitorio (Redis)

## `wa_session:$phone`
*   **Estructura:** Hash JSON
*   **Campos:**
    *   `phone` (String, Teléfono del cliente)
    *   `name` (String, Nombre del remitente en Chatwoot/WhatsApp)
    *   `last_message` (String, Último texto del usuario)
    *   `step` (String, Estado conversacional)
    *   `order_data` (Array/JSON, Borrador temporal del pedido conteniendo ítems y extras)
    *   `history` (Array, Historial de los últimos 30 mensajes para el LLM)
    *   `updated_at` (Timestamp ISO 8601)