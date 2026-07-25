# Integración con la API Market del Banco Económico (Baneco)

Este módulo implementa la integración desacoplada y robusta con los servicios del **Banco Económico S.A. (API Market)** bajo especificaciones de la versión **v1.3.0**.

---

## 1. Arquitectura del Módulo

Toda la lógica de la integración se encuentra desacoplada bajo el namespace `App\Baneco` en la ruta `backend/app/Baneco/` para cumplir con los principios de Clean Architecture:

*   **`Contracts/`**: Contrato `EncryptionServiceInterface` que permite cambiar la estrategia de encriptación AES sin acoplamiento.
*   **`Services/`**: 
    *   `Aes256EncryptionService`: Cifrado local utilizando `aes-256-cbc`.
    *   `BanecoAuthenticationService`: Solicita el Bearer Token, lo almacena en caché y lo refresca automáticamente.
    *   `BanecoService`: Fachada principal que expone los métodos de negocio (`generateQR`, `cancelQR`, `statusQR`, `paidQR`, `accountHistory`, `uploadBatch`).
*   **`Support/`**: `BanecoHttpClient` dedicado que gestiona timeouts, reintentos automáticos, enmascaramiento de información confidencial en logs y excepciones de conexión.
*   **`DTO/`**: Objetos de transferencia de datos con tipado estricto que mapean las peticiones y respuestas especificadas por el banco.
*   **`Events/` y `Jobs/`**: Acciones asíncronas asiladas para generación y conciliación de cobros y pagos.
*   **`Http/`**: Controlador para la recepción del webhook con validación de datos y protección de idempotencia por caché.

---

## 2. Variables de Entorno y Configuración

El archivo de configuración principal es `backend/config/baneco.php`. Se alimenta de las siguientes variables en el archivo `.env`:

```env
BANECO_BASE_URL=https://apimktdesa.baneco.com.bo/ApiGateway/
BANECO_USERNAME=tu_usuario_asignado
BANECO_PASSWORD=tu_contraseña_asignada
BANECO_AES_KEY=tu_llave_aes_256_bits_32_bytes
BANECO_ACCOUNT=tu_numero_de_cuenta_credito
BANECO_TIMEOUT=30
BANECO_VERIFY_SSL=true
BANECO_NOTIFICATION_ENABLED=true
BANECO_QR_EXPIRATION_DAYS=1
```

---

## 3. Flujo de Autenticación y QR Simple

1. **Autenticación (Bearer Token):** El token se solicita mediante `POST /api/authentication/authenticate` enviando la contraseña cifrada. Se almacena en la caché de la aplicación durante 55 minutos para evitar peticiones repetitivas innecesarias.
2. **Generación de QR:** Cuando un pedido se crea o cambia a estado `'Pendiente'`, se despacha `GenerateQRJob`, que se comunica con el endpoint del banco `POST /api/qrsimple/generateQR` enviando la cuenta cifrada. Tras recibir el QR, se guarda en el sistema y se asocia al pedido.

---

## 4. Webhook de Confirmación de Pago

El endpoint de recepción es público:
`POST /api/webhooks/baneco/payment`

*   **Idempotencia:** Utiliza un mecanismo de caché con la llave `baneco_payment_processed_{qrId}` para ignorar notificaciones duplicadas en un margen de 24 horas.
*   **Procesamiento:** Delega la transacción asíncronamente a `ProcessPaymentNotificationJob` para actualizar el pedido en la base de datos a `'Confirmado'` (Pagado) sin provocar bloqueos de tablas concurrentes.

---

## 5. Pruebas y Comandos de Consola

### Comandos Artisan:
*   `php artisan baneco:test`: Verifica la lectura de configuración, la encriptación AES local y la conectividad (si las credenciales están presentes).
*   `php artisan baneco:health`: Ejecuta un chequeo básico del estado del canal.

### Ejecución de Pruebas:
```bash
php artisan test --filter=BanecoIntegrationTest
```

---

## 6. Checklist de Certificación y Producción

### 📋 Para Certificación:
- [ ] Completar el archivo `.env` con las credenciales de pruebas entregadas por el Banco.
- [ ] Ejecutar `php artisan baneco:test` y verificar que el resultado sea `OK`.
- [ ] Exponer la url local usando una herramienta de túnel (ej. ngrok) para recibir notificaciones en `/api/webhooks/baneco/payment`.
- [ ] Validar con el banco que la generación de QR y recepción de notificaciones funcione en su ambiente de pruebas.

### 📋 Para Producción:
- [ ] Cambiar `BANECO_BASE_URL` a la URL de producción oficial.
- [ ] Actualizar credenciales y la llave real AES-256 bits de producción.
- [ ] Asegurar que `BANECO_VERIFY_SSL` esté configurado en `true`.
- [ ] Habilitar y configurar logs del canal `baneco.log` para monitoreo de incidentes.
