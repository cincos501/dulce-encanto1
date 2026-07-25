# Plan de Implementación: Integración con Banco Económico (Baneco)

Este plan describe el diseño de la integración del sistema de cobros y pagos con la **API Market del Banco Económico (Baneco)** para la generación de códigos QR de pago simple y la conciliación bancaria. Toda la integración está diseñada a partir de las especificaciones de la versión **v1.3.0** del banco.

---

## 1. Resumen Técnico de la API

*   **Tecnología:** REST con intercambio de datos en formato **JSON**.
*   **Seguridad y Autenticación:** 
    *   Cabecera HTTP: `Authorization: Bearer <token>`.
    *   Autenticación de credenciales: `POST /api/authentication/authenticate`.
*   **Cifrado:** Algoritmo **AES-256 bits (32 bytes)** para contraseñas de autenticación, números de cuentas corrientes/ahorros.
*   **Ambiente de Certificación:** `https://apimktdesa.baneco.com.bo/ApiGateway/`.
*   **Formatos Estándar:**
    *   Importes: Punto decimal (`.`), máximo 2 decimales.
    *   Fechas: `yyyy-MM-dd`.
    *   Horas: `HH:mm:ss` (24 horas).
    *   Propiedades: `camelCase`.

---

## 2. Arquitectura Propuesta

La integración se implementará como un módulo completamente aislado y desacoplado bajo el namespace `App\Baneco` en `backend/app/Baneco/` para cumplir con los principios de Clean Architecture:

```text
backend/app/Baneco/
├── Contracts/         # Interfaces (EncryptionServiceInterface, BanecoRepositoryInterface, etc.)
├── DTO/               # Objetos de Transferencia de Datos que representan el Request/Response del Banco
├── Enums/             # Enums de estados del QR, tipos de planilla (PAYROLL, PROVIDERS), etc.
├── Events/            # Eventos del sistema (QRGenerated, QRPaid, etc.)
├── Exceptions/        # Excepciones específicas del dominio bancario
├── Http/
│   ├── Controllers/   # Controlador del Webhook de pagos (BanecoWebhookController)
│   └── Requests/      # Validadores de peticiones del Webhook
├── Jobs/              # Procesamiento asíncrono (GenerateQRJob, CheckQRStatusJob, etc.)
├── Repositories/      # Persistencia de transacciones de pago QR
├── Services/          # Lógica de negocio (BanecoService, AuthenticationService, EncryptionService)
├── Support/           # Utilidades y cliente HTTP dedicado (BanecoHttpClient)
└── ValueObjects/      # Objetos de valor para control de datos inmutables
```

---

## 3. Endpoints Documentados y Flujos

### 3.1. Flujo de Autenticación y Cache de Token
1. La aplicación verifica si existe un token válido en la Cache (Redis/File).
2. Si no existe o está por expirar, realiza la petición a:
   `POST /api/authentication/authenticate`
   Enviando `userName` y la contraseña cifrada (`password`).
3. El token obtenido se almacena en caché junto con su tiempo de expiración menos un margen de seguridad (5 minutos) para renovación proactiva.

### 3.2. Flujo de Generación de QR de Pago Simple
1. Al confirmarse un borrador de pedido conversacional o al presionar checkout en la web, el estado del pedido cambia a `'Pendiente'`.
2. Se despacha `GenerateQRJob` el cual se comunica con el banco:
   `POST /api/qrsimple/generateQR`
   Enviando la cuenta de crédito cifrada, importe, fecha de vencimiento (`dueDate`) y bandera `singleUse` = `true`.
3. El banco responde con el `qrId` y la imagen del QR en base64 (`qrImage`).
4. Se dispara el evento `QRGenerated`, guardando el registro en la tabla de pagos y asociándolo a la orden en MySQL.

### 3.3. Flujo del Webhook de Notificación de Pago
1. Baneco envía un POST al comercio al endpoint:
   `POST /api/webhooks/baneco/payment`
   Enviando un objeto `PaymentQR` en el body.
2. El webhook valida la firma/seguridad de la petición, loguea en su canal exclusivo `baneco.log` y despacha el Job `ProcessPaymentNotificationJob` de forma asíncrona.
3. El Job marca el pago como exitoso y actualiza de manera transaccional el estado del pedido en MySQL a `'Confirmado'` (o el correspondiente estado de pagado), disparando el evento `QRPaid`.

### 3.4. Consulta de Cuentas y Planilla de Pagos
*   **Movimientos de Cuenta:** `POST /api/accounts/history`
*   **Planillas de Pago (Payroll/Proveedores):** `POST /api/batchPayment/upload`
*   **Webhook de Confirmación de Planilla:** `POST /api/notifyStatus`

---

## 4. Objetos y Mapeos DTO (Exactamente según Especificación)

*   **`PaymentQR`:** `qrId`, `transactionId`, `paymentDate`, `paymentTime`, `currency`, `amount`, `senderBankCode`, `senderName`, `senderDocumentId`, `senderAccount`, `description`, `branchCode`.
*   **`AccountHeader`:** `accountCode`, `accountTypeCode`, `productName`, `status`, `currency`, `balance`, `balanceReserved`, `balanceRetained`, `balanceAvailable`.
*   **`AccountDetail`:** `transactionId`, `date`, `time`, `documentNumber`, `transactionType`, `amount`, `description`, `clienteNote`.
*   **`BatchPayment`:** `batchDetailId`, `amount`, `accountCode`, `accountTypeCode`, `bankCode`, `beneficiaryName`, `beneficaryDocId`, `beneficiaryPhone`, `beneficiaryEmail`, `note`, `AMLData`.

---

## 5. Información que aún debe entregar Baneco (Riesgos y Ambigüedades)

> [!WARNING]
> **Información técnica requerida del Banco:**
> 1. **Detalles de Cifrado AES-256:** La especificación no indica el modo de operación de bloques de AES (CBC, ECB, GCM), el esquema de relleno (PKCS7, etc.), la codificación de salida del texto cifrado (Base64 o Hexadecimal), ni si se utiliza un Vector de Inicialización (IV) dinámico o estático. Se configurará una interfaz para permitir cambiar la estrategia del `EncryptionService` de forma transparente.
> 2. **Parámetros de Anular QR (`DELETE /api/qrsimple/cancelQR`):** El documento oficial no especifica la estructura del body ni parámetros de consulta para la anulación de QR. Se definirá un DTO adaptable.
> 3. **Estructura del Objeto `AMLData` y `AccountWithheld`:** Falta la definición detallada de campos para estos objetos. Se mapearán como estructuras de datos de tipo arreglo flexible.

---

## 6. Integración con Dulce Encanto

*   **Generación:** Al registrar un pedido en estado `'Pendiente'`, se invocará `BanecoService->generateQR()`. El QR generado se guardará y se asociará al pedido.
*   **Confirmación:** Al recibir la notificación del webhook o al consultar activamente el estado de un QR como "pagado" (`v2/statusQR/{id}`), el sistema llamará de forma transaccional a la actualización del pedido, transicionando su estado en MySQL a `'Confirmado'` (o el correspondiente estado de pagado) de forma segura.

---

## 7. Checklist de Implementación

- [ ] Fase 1: Creación del archivo de configuración `config/baneco.php` y variables de entorno.
- [ ] Fase 2: Creación del canal exclusivo de logs `baneco.log`.
- [ ] Fase 3: Implementación del `EncryptionService` y su interfaz.
- [ ] Fase 4: Implementación del cliente HTTP `BanecoHttpClient` con Bearer Token, renovación y reintentos automáticos.
- [ ] Fase 5: Implementación de todos los objetos DTO especificados en el anexo de la API.
- [ ] Fase 6: Creación del `BanecoService` y de los Jobs/Eventos de cola.
- [ ] Fase 7: Creación del controlador del Webhook en `Http/Controllers/BanecoWebhookController.php`.
- [ ] Fase 8: Integración de la generación de QR en el flujo de pedidos existente.
- [ ] Fase 9: Creación de comandos Artisan `baneco:test` y `baneco:health`.
- [ ] Fase 10: Creación de la documentación `backend/README_BANECO.md`.
- [ ] Fase 11: Ejecución de Unit y Feature Tests mockeando la API.
