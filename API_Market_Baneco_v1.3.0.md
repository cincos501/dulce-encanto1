# Banco Económico S.A. | API Market - Especificaciones Técnicas v1.3.0

## 1. Introducción
El presente documento describe las especificaciones técnicas de las API que el Banco Económico S.A. pone a disposición de sus clientes.
Estas API están diseñadas sobre tecnología REST y notación JSON como formato de intercambio de información. Para poder hacer uso de cualquier API se debe enviar en la cabecera un Bearer Token generado previamente con otra API para validación de credenciales de usuarios que serán proporcionados por el Banco.

## 2. Formatos y convenciones
*   **Importe con decimales:** se usará el carácter punto (`.`) como separador de la parte entera con la parte decimal y como máximo dos dígitos para la parte decimal.
*   **Fechas:** los tipos de datos fecha deberán usar el formato `yyyy-MM-dd`.
*   **Horas:** la hora debe usar el formato `HH:mm:ss` (formato 24 horas).
*   **Nombres de propiedades:** los nombres de propiedades usarán la notación `camelCase`.

## 3. Encriptación de datos
Se usará el algoritmo estándar **AES-256 bits (32 bytes)** como método para el cifrado de datos que se consideren necesarios y se enviarán o se recibirán a través de las diferentes API. La llave será proporcionada por el banco.

## 4. URL de ambiente de certificación
`https://apimktdesa.baneco.com.bo/ApiGateway/`

---

## 5. API de Encriptación

### 5.1. Encriptar datos
*   **Descripción:** Encriptación de datos
*   **Método:** `GET`
*   **URI:** `http://[dominio]:[puerto]/api/authentication/encrypt`

| Parámetro | Tipo de Dato | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `text` | Texto | Sí | Texto a encriptar |
| `aeskey` | Texto | Sí | Llave de encriptación |

### 5.2. Desencriptar datos
*   **Descripción:** Desencriptación de datos
*   **Método:** `GET`
*   **URI:** `http://[dominio]:[puerto]/api/authentication/decrypt`

| Parámetro | Tipo de Dato | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `text` | Texto | Sí | Texto a desencriptar |
| `aeskey` | Texto | Sí | Llave de encriptación |

---

## 6. API de Autenticación

### 6.1 Validación de credenciales de acceso
*   **Descripción:** Validación de credenciales y solicitud de token (usado para el consumo de otros servicios)
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/authentication/authenticate`

**Request body:**
| Elemento | Tipo de Dato | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `userName` | Texto | Sí | Nombre de usuario asignado por el Banco |
| `password` | Texto | Sí | Contraseña (cifrado) |

**Response:**
| Elemento | Tipo de Dato | Descripción |
| :--- | :--- | :--- |
| `responseCode` | Entero | Código de respuesta, diferente de cero indica un error |
| `message` | Texto | Cuando responseCode es diferente de cero, indica el mensaje del error |
| `token` | Texto | Token de autorización a enviar en la llamada de otros servicios |

---

## 7. API de Pagos Simple a través de códigos QR

### 7.2 Generación de QR
*   **Descripción:** Solicitud de generación de código QR para cobros a través de la plataforma Pago Simple
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/qrsimple/generateQR`

**Request body:**
| Elemento | Tipo de Dato | Requerido | Descripción |
| :--- | :--- | :--- | :--- |
| `transactionId` | Texto | Sí | Identificador de la transacción en el sistema del comercio |
| `accountCredit` | Texto | Sí | Número de la cuenta corriente o caja de ahorro (cifrado) |
| `currency` | Texto | Sí | Moneda (`BOB` o `USD`) |
| `amount` | Decimal | Sí | Importe con el que se genera el QR |
| `description` | Texto | No | Nota del cobro |
| `dueDate` | Fecha | Sí | Fecha de vencimiento del QR |
| `singleUse` | Lógico | Sí | `true`: un solo pago, `false`: varios pagos |
| `modifyAmount` | Lógico | Sí | `true`: permite modificar importe, `false`: no permite |
| `branchCode` | Texto | No | Código de sucursal que solicita el QR |

**Response:** Retorna `responseCode`, `message`, `qrId` (ID único) y `qrImage` (Base64).

### 7.3 Anular QR
*   **Descripción:** Anula un QR para futuros pagos.
*   **Método:** `DELETE`
*   **URI:** `http://[dominio]:[puerto]/api/qrsimple/cancelQR`

### 7.4 Verificar estado de QR
*   **Descripción:** Consulta el estado de un código QR
*   **Método:** `GET`
*   **URI:** `http://[dominio]:[puerto]/api/qrsimple/v2/statusQR/{id}`
*   **Response:** Retorna `statusQRCode` (0: activo, 1: pagado, 9: anulado) y el objeto `PaymentQR`.

### 7.5 Notificación de pago de QR (Webhook Opcional)
*   **Descripción:** Servicio publicado por el comercio para recibir notificación de pago.
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/qrsimple/notifyPaymentQR`
*   **Request Body:** Recibe un objeto `PaymentQR`.

### 7.6 Lista de QR pagados
*   **Descripción:** Retorna listado de QR pagados en una fecha para conciliación.
*   **Método:** `GET`
*   **URI:** `http://[dominio]:[puerto]/api/qrsimple/v2/paidQR/{fecha}` (formato yyyyMMdd)

---

## 8. Consultas de cuentas (8.1 Consulta de movimientos)
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/accounts/history`
*   **Request body:** `accountCode` (cifrado), `startDate`, `endDate`.
*   **Response:** Retorna `accountHeader`, `accountDetailList`, `accountWithheldList`.

---

## 9. Planillas de pagos

### 9.1 Carga de planilla de pagos
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/batchPayment/upload`
*   **Request body:** `batchId`, `type` (`PAYROLL`, `PROVIDERS`), `descripction`, `detailedDebit`, `accountCode`, `batchCurrency`, `batchAmount`, `AMLData`, `paymentCount`, `paymentList`.

### 9.2 Confirmación de estado de detalles de planilla (Webhook)
*   **Método:** `POST`
*   **URI:** `http://[dominio]:[puerto]/api/notifyStatus`
*   **Request body:** Retorna al comercio `bankBatchId`, `batchId`, `batchDetailId`, `status` (`ACEP`, `RECH`), `transactionIdDebit`, `transactionIdCredit`.

---

## Anexo 1 - Definiciones de Objetos

### Objeto PaymentQR
Contiene: `qrId`, `transactionId`, `paymentDate`, `paymentTime`, `currency`, `amount`, `senderBankCode`, `senderName`, `senderDocumentId`, `senderAccount`, `description`, `branchCode`.

### Objeto AccountHeader
Contiene: `accountCode`, `accountTypeCode`, `productName`, `status`, `currency`, `balance` (contable), `balanceReserved`, `balanceRetained`, `balanceAvailable`.

### Objeto AccountDetail
Contiene: `transactionId`, `date`, `time`, `documentNumber`, `transactionType` (D/C), `amount`, `description`, `clienteNote`.

### Objeto BatchPayment
Contiene: `batchDetailId`, `amount`, `accountCode`, `accountTypeCode`, `bankCode`, `beneficiaryName`, `beneficaryDocId`, `beneficiaryPhone`, `beneficiaryEmail`, `note`, `AMLData`.