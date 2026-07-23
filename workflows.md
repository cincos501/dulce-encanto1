# n8n Workflows — Referencia completa

> **Instancia**: `https://n8n.goblin-sw.com`
> **Generado**: 2026-07-07
> **Flujos activos**: 15 (180 nodos en total, todos activos y conectados — sin remanentes desactivados/desconectados)

---

## Índice

| # | Nombre | ID | Tipo de trigger |
|---|--------|----|-----------------|
| 1 | [Webhook Fan-out](#1-webhook-fan-out) | `icSaYhB4eMSaQKt7` | Webhook externo |
| 2 | [Process input message](#2-process-input-message) | `C7bBjpvRmmXbBaak` | Webhook externo |
| 3 | [Await message grace time](#3-await-message-grace-time) | `brwAtVKGUqZPW5yg` | Llamado por otro workflow |
| 4 | [Get Ollama Response — Tool Calling v3](#4-get-ollama-response--tool-calling-v3) | `JEtvQiYzs4ZtUEQh` | Llamado por otro workflow |
| 5 | [Inscriptions AI agent](#5-inscriptions-ai-agent) | `DO1KOwGNSULsQ20D` | Llamado por otro workflow |
| 6 | [Process attachment files](#6-process-attachment-files) | `lVBz1pxJdOSi1GFR` | Llamado por otro workflow |
| 7 | [Typing Loop](#7-typing-loop) | `4UiUiyRKtl5SwGuP` | Llamado por otro workflow |
| 8 | [Stop process execution](#8-stop-process-execution) | `34PcgWs057ll8MVc` | Llamado por otro workflow |
| 9 | [Stop execution](#9-stop-execution) | `MXixvoKC47ifoAfU` | Llamado por otro workflow (sin callers actuales) |
| 10 | [Save base system prompt](#10-save-base-system-prompt) | `2Woea4PA1MiZIsXt` | Webhook externo |
| 11 | [Save courses data](#11-save-courses-data) | `KiCYYElIF3TXCjVd` | Webhook externo |
| 12 | [Save payment methods data](#12-save-payment-methods-data) | `isLbz4UKIoprkNAH` | Webhook externo |
| 13 | [Refresh system prompt](#13-refresh-system-prompt) | `I5pQmYPtreJGoZha` | Llamado por otro workflow |
| 14 | [KV Store — Write](#14-kv-store--write) | `AB2b8LRVmKVEPoOK` | Llamado por otro workflow |
| 15 | [KV Store — Read](#15-kv-store--read) | `ulyHlUNZzAsLSnl1` | Llamado por otro workflow |

---

## Arquitectura general

El sistema implementa un **bot de atención al cliente vía WhatsApp** integrado con Chatwoot, con dos agentes de IA separados (ventas de cursos vs. gestión de inscripciones) y una capa de persistencia KV compartida. Patrón central:

1. Chatwoot envía eventos webhook → **Webhook Fan-out** los replica a las rutas de producción y test.
2. **Process input message** es el punto de entrada principal: filtra mensajes entrantes, bifurca inscriptor vs. flujo general de ventas, y coordina grace time, adjuntos y cancelación de respuestas previas.
3. El flujo general usa un debounce Redis (GETSET atómico) para matar ejecuciones previas antes de responder.
4. **Get Ollama Response — Tool Calling v3** es el núcleo del agente de ventas: un bucle de tool-calling **construido a mano** (sin nodo LangChain Agent) que llama a Ollama, ejecuta herramientas (enviar afiche, brochure, método de pago, derivar a humano) y **persiste cada llamada/resultado de herramienta como notas privadas en Chatwoot**, reconstruyendo el historial completo desde Postgres en cada iteración — ya no depende de memoria en RAM entre turnos.
5. **Inscriptions AI agent** usa el nodo LangChain Agent (historial desde Postgres) con herramientas HTTP hacia un Google Apps Script (Sheets) para gestionar inscripciones.
6. Flujos auxiliares: adjuntos (audio → Whisper, imagen → Ollama vision), indicador de escritura en WhatsApp, y gestión del KV store (Redis + Postgres como persistencia dual, con lectura **multi-clave** en un solo llamado).
7. Tres flujos "Save ..." (`Save base system prompt`, `Save courses data`, `Save payment methods data`) alimentan el KV store vía webhooks (típicamente disparados desde Google Drive/Apps Script) y disparan **Refresh system prompt** para recombinar el `sysprompt` activo.

**Patrón debounce** (repetido en casi todos los flujos):
```
Getset redisflag (REDIS SET key $execution.id GET EX ttl)
 ├─ Si había valor anterior → Call 'Stop process execution' (mata la ejecución anterior)
 └─ Wait N segundos → Is valid flag? (¿sigue siendo esta ejecución la dueña?) → continuar
```

**Inconsistencia de conexión Redis**: el cliente `ioredis` está **hardcodeado** (`host:'redis', port:'6379', db:'0'`) en `Process input message`, `Save courses data`, `Typing Loop`, y en el nodo `Has response and is valid flag? - delete flag` de `Get Ollama Response — Tool Calling v3` y el nodo `Exist response and is valid flag? - delete flag` de `Inscriptions AI agent`; pero usa **variables de entorno** (`REDIS_HOST`/`REDIS_PORT`/`REDIS_PASSWORD`/`REDIS_DB`) en `Await message grace time`, `Save base system prompt`, `Save payment methods data`, `Refresh system prompt`, y el nodo `Getset redisflag` de ambos `Get Ollama Response — Tool Calling v3` e `Inscriptions AI agent`. La migración a env vars está en progreso pero no es uniforme (incluso dentro del mismo workflow).

---

## 1. Webhook Fan-out

**ID**: `icSaYhB4eMSaQKt7` | **Creado**: 2026-05-28 | **Actualizado**: 2026-05-29 | **3 nodos**

### Propósito
Recibe el webhook de Chatwoot en un único endpoint y lo replica (fan-out) a cuatro URLs: las rutas de test y producción de `wp-goblin-media` y `process-messages`. Sin cambios desde la versión anterior de este documento.

### Trigger
`POST /webhook/wp-goblin` (también responde en `/webhook-test/wp-goblin`)

### Flujo activo
```
Webhook (/wp-goblin)
  → Preparar Destinos (Code)
      Genera 4 items, uno por URL destino:
      - /webhook-test/wp-goblin-media
      - /webhook/wp-goblin-media
      - /webhook-test/process-messages
      - /webhook/process-messages
  → Replicar POST (HTTP Request, continueOnFail)
      POST a cada targetUrl con $json.payload.body
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook | webhook | Punto de entrada desde Chatwoot |
| Preparar Destinos | code | Construye los 4 destinos |
| Replicar POST | httpRequest | Envía el POST a cada URL |

Sin nodos desactivados ni desconectados.

---

## 2. Process input message

**ID**: `C7bBjpvRmmXbBaak` | **Creado**: 2026-06-09 | **Actualizado**: 2026-07-07 | **11 nodos**

### Propósito
Punto de entrada principal para mensajes de Chatwoot. Filtra mensajes no válidos, bifurca según si el usuario tiene permisos de inscriptor, y coordina grace time, adjuntos y cancelación de respuestas previas en curso.

> **Cambio relevante**: toda la cadena de procesamiento de multimedia legacy que documentaba la versión anterior (`Configold`, `Set/Unset Redis flag`, `Register Record`, `File type switch`, `Download audio/audio1`, `Transcript`, `Analyze image`, `Armar nota`, `Add reply private note`, `Cleanup Records`, dos `Call 'Get Ollama Response'`) **fue eliminada por completo**. El workflow quedó con exactamente los 11 nodos activos que ya ejecutaban el flujo real.

### Trigger
`POST /webhook/process-messages` (también recibe de Webhook Fan-out vía `/webhook-test/process-messages`)

### Flujo activo
```
Webhook (/process-messages)
  → is a courses editor? (IF)
      Condición: custom_attribute 'can-register-inscriptions' = true AND event = 'message_created'

      TRUE  → Isn't the bot? (Code — filtra sender_name='bot-ia' y sender_type='user')
                → Call 'Inscriptions AI agent' (fire-and-forget)

      FALSE → Is incoming message? (Filter — event=message_created, message_type=incoming)
                → Config (Set — define processing_response_flag key)
                  ├─ Call 'Await message grace time' (fire-and-forget, con conversation_id)
                  ├─ have attachments? (Filter — attachments[0] existe?)
                  │     → Call 'Process atach' (fire-and-forget → Process attachment files)
                  └─ Getset redisflag (Code — REDIS GETDEL processing:modelresponse:conversation:<id>, cliente hardcodeado)
                        → Call 'Stop process execution' (mata respuesta IA anterior en curso)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook | webhook | Entrada principal |
| is a courses editor? | if | Bifurca inscriptor vs general |
| Isn't the bot? | code | Filtra mensajes del propio bot |
| Call 'Inscriptions AI agent' | executeWorkflow | Delega al agente de inscripciones |
| Is incoming message? | filter | Filtra solo mensajes entrantes |
| Config | set | Define clave `processing:modelresponse:conversation:<id>` |
| Call 'Await message grace time' | executeWorkflow | Inicia el período de espera |
| have attachments? | filter | Detecta si hay adjuntos |
| Call 'Process atach' | executeWorkflow | Delega al procesador de adjuntos |
| Getset redisflag | code | GETDEL de la clave de respuesta anterior |
| Call 'Stop process execution' | executeWorkflow | Mata la ejecución anterior |

Sin nodos desactivados ni desconectados — flujo completamente limpio.

---

## 3. Await message grace time

**ID**: `brwAtVKGUqZPW5yg` | **Creado**: 2026-05-29 | **Actualizado**: 2026-07-07 | **9 nodos**

### Propósito
Implementa el período de gracia antes de responder al usuario. Si en esos segundos llega otro mensaje (lo que resetea el flag Redis), este flujo se cancela a sí mismo antes de llamar al núcleo del agente. Garantiza que el bot solo responde cuando el usuario terminó de escribir.

> **Cambio relevante**: los remanentes de tracking en Postgres que documentaba la versión anterior (`Register wait proccess`, `Get previous wait processe`, `Unset Redis flag1`) fueron eliminados. Quedan exactamente los 9 nodos del flujo activo. El cliente Redis aquí usa variables de entorno (`REDIS_HOST`/`PORT`/`PASSWORD`/`DB`), a diferencia de otros flujos.

### Trigger
Llamado por **Process input message** con `conversation_id`.

### Flujo activo
```
When Executed by Another Workflow (conversation_id)
  → Config (Set)
      flag_key = processing:conversation:<id>:wait
      flag_ttl = 50s, grace_time = 5s
  → Getset redisflag (Code — REDIS SET key $execution.id GET EX 50, vía env vars)
      ├─ Exist old flags? (Code — filtra si había valor anterior)
      │     → Call 'Stop process execution'1 (mata ejecución anterior del grace time)
      └─ Wait 5s
            → Exist response and is valid flag? (Code — REDIS GET, verifica ownership)
                  → Unset Redis flag (Redis DELETE flag_key)
                        → Call 'Get Ollama Response — Tool Calling v3' (fire-and-forget)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Config | set | Define flag key y tiempos |
| Getset redisflag | code | Debounce Redis atómico (env vars) |
| Exist old flags? | code | Filtra ejecución anterior si existía |
| Call 'Stop process execution'1 | executeWorkflow | Cancela grace time anterior |
| Wait | wait | Grace time de 5 segundos |
| Exist response and is valid flag? | code | Verifica ownership del flag |
| Unset Redis flag | redis | Elimina el flag |
| Call 'Get Ollama Response — Tool Calling v3' | executeWorkflow | Dispara el agente de ventas |

Sin nodos desactivados ni desconectados.

---

## 4. Get Ollama Response — Tool Calling v3

**ID**: `JEtvQiYzs4ZtUEQh` | **Creado**: 2026-06-24 | **Actualizado**: 2026-07-07 | **56 nodos**

> **El ID y el nombre de este workflow cambian cada vez que se reconstruye** (se borra y recrea, no se edita in-place): `LvmjI6L78lpzaOYX` → `tQZtGRlH6i30aKCL` ("Get Ollama Response — Tool Calling") → `JEtvQiYzs4ZtUEQh` ("Get Ollama Response — Tool Calling v3", actual). Antes de referenciar este flujo desde otro lugar, confirmar cuál es el vigente.

### Propósito
Núcleo del agente de ventas de cursos. Es un **bucle de tool-calling construido a mano** (código + HTTP Request + IF + Switch, sin el nodo LangChain Agent) que llama a Ollama con `tools` definidas dinámicamente desde el catálogo de cursos/pagos, ejecuta las herramientas que el modelo invoque, y **persiste cada llamada y resultado de herramienta como notas privadas en Chatwoot** para reconstruir el historial completo desde Postgres en cada iteración del loop — en vez de acumular estado en memoria durante la ejecución. Esto resuelve el bug de raíz documentado anteriormente (el historial reconstruido perdía los `tool_calls`, lo que enseñaba al modelo a dejar de usar herramientas).

### Trigger
Llamado por **Await message grace time** y **Process attachment files**, ambos con `conversation_id`.

### Fase 1 — Guardas y contexto
```
When Executed by Another Workflow "Init" (conversation_id)
  → Config (Set)
      chatwoot_bot_access_token = {{ $env.CHATWOOT_BOT_ACCESS_TOKEN }}
      ai_service_url = http://ollama:11434/api/chat, model = qwen3.5:4b
      flag_key = processing:modelresponse:conversation:<id>, flag_ttl = 600
      others_flag_key = processing:conversation:<id>:*
      chatwoot_base_url = http://chatwoot-web:3000/api/v1/accounts/1
      assignee_id = 7 (agente humano de destino en handoff)
      caption_poster / caption_brochure / caption_payment (textos de los mensajes con adjunto)
      maxIterations = 6
  → Get conversation info (HTTP GET Chatwoot /conversations/<id>)
  → Isn´t ai-bot desactivated? (Code — aborta si custom_attributes['bot-off'] es true)
  → Getset redisflag (SET flag_key $execution.id GET EX 600, vía env vars)
      ├─ Exist old flags? → Call 'Stop process execution' (mata respuesta anterior)
      └─ Get previous others process (Redis KEYS processing:conversation:<id>:*)
            → Exist previous other process? (IF: ¿0 claves?)
                TRUE (sin otras claves activas — grace time/multimedia ya terminaron)
                  → Call 'KV-Read' (batch: ["sysprompt","courses:all","payments:all"])
                  → Call 'Typing Loop' (fire-and-forget)
                  → Build enums and tools descriptions (Code — resuelve enums poster/brochure/payment
                        desde el KV, con fallback a "todos los códigos" si el enum viene vacío;
                        arma tool_instructions en prosa)
                        → Tool: send_course_poster / Tool: send_course_brochure /
                           Tool: assign_conversation_to_human_agent / Tool: send_payment_method
                           (Set — JSON crudo de function-calling, enum inyectado por expresión)
                              → Merge Tools (combineByPosition, 4 inputs)
                                    → entra a la Fase 2
                FALSE (hay grace_time o multimedia en curso)
                  → Unset Redis flag (aborta esta ejecución)
```

### Fase 2 — Loop de tool-calling (notas privadas como historial persistente)
```
Get messages (Postgres — SELECT directo a la tabla messages de Chatwoot, id/content/
              content_attributes/message_type/private/created_at, excluye message_type=2 'activity')
  → Normalize Messages (Code — clasifica cada fila: mensaje público (user/assistant) | nota
        privada [TOOL-CALLS] | nota privada [TOOL-RESULT] | nota privada [ATTACH]/[TRANSCRIPTION];
        descarta mensajes "Este mensaje se ha eliminado"; strippea bloques <think>)
  → Build Request (Code, antes "Build Messages" — reconstruye el timeline completo: mensajes
        públicos intercalados con turnos assistant(tool_calls) + N×role:tool reensamblados
        POR-CALL; descarta llamadas sin resultado (huérfanas); excluye del timeline los mensajes
        públicos que fueron preface o output de una tool ya contabilizada; calcula "iteration"
        contando notas [TOOL-CALLS] posteriores al último mensaje entrante)
  → Message a local model (POST a ai_service_url, think:true, num_ctx:32768, stream:false)
  → Parse Tool Calls (Code — sanea <think>, marcadores "(Nota Privada...)" y tags <tool_call>
        del contenido; si no hay tool_calls o iteration >= maxIterations, continue:false)
  → Has tool_calls?
      FALSE → Has response and is valid flag? - delete flag (Code — GETDEL flag_key,
                  cliente Redis hardcodeado; verifica ownership antes de enviar)
                → Send response (HTTP POST Chatwoot — mensaje final público)
      TRUE  → Has content? (¿el modelo escribió texto junto con la(s) tool_call(s)?)
                SI → Send preface to Chatwoot (publica ese texto como mensaje saliente ANTES
                        de ejecutar las tools — ya no se pierde el texto que acompaña a la tool_call)
                     → Note [TOOL-CALLS] (reply) (nota privada: {preface_msg_id, calls[]},
                          content_attributes.in_reply_to = preface_msg_id)
                NO → Note [TOOL-CALLS] (standalone) (misma nota, preface_msg_id: null)
              → Fan out tool calls (Code — un item por tool_call, con tool_call_id/toolName/args)
                    → Route by tool (Switch por toolName; fallback "extra")
                        send_payment_method
                          → Resolve Payment (busca en payments:all por code o payment_method)
                             → Has payment image?
                                 SI → Download Payment → Send Payment (multipart, imagen + caption_payment)
                                 NO → (sin envío)
                             → Normalize Payment1 (status: sent | not_found | not_available | send_failed)
                        send_course_poster
                          → Resolve Poster (busca en courses:all por code o name)
                             → Has poster? → [Download Poster → Send Poster (imagen) | —]
                             → Normalize Poster
                        send_course_brochure
                          → Resolve Brochure
                             → Has brochure? → [Send Brochure (texto con el link, SIN adjunto) | —]
                             → Normalize Brochure
                        assign_conversation_to_human_agent
                          → Prep Handoff (extrae "reason" opcional)
                             → Assign Conversation (POST .../assignments, assignee_id=7)
                                → Set bot-off (PATCH custom_attributes: {"bot-off": true})
                                   → have reason? → [Send reason note (nota privada "Motivo: ...") | —]
                                   → Normalize Handoff (status: assigned)
                        (nombre desconocido) → Unknown tool (status: unknown_tool)
                    → Merge3 (combineByPosition, 5 inputs)
                          → Note [TOOL-RESULT]1 (nota privada por resultado: {tool_call_id, status,
                                reason?, data?, public_msg_id?}, in_reply_to = public_msg_id si existe)
                                → Loop back (Code) → vuelve a "Get messages" (siguiente iteración)
```

### Nodos (56 — todos activos y conectados, sin remanentes desactivados)
| Grupo | Nodos |
|-------|-------|
| Entrada / guardas | Init, Config, Get conversation info, Isn´t ai-bot desactivated?, Getset redisflag, Exist old flags?, Call 'Stop process execution', Get previous others process, Exist previous other process?, Unset Redis flag |
| Contexto / definición de tools | Call 'KV-Read', Call 'Typing Loop', Build enums and tools descriptions, Tool: send_course_poster, Tool: send_course_brochure, Tool: assign_conversation_to_human_agent, Tool: send_payment_method, Merge Tools |
| Loop principal | Get messages, Normalize Messages, Build Request, Message a local model, Parse Tool Calls, Has tool_calls?, Has content?, Send preface to Chatwoot, Note [TOOL-CALLS] (reply), Note [TOOL-CALLS] (standalone), Has response and is valid flag? - delete flag, Send response, Fan out tool calls, Route by tool, Merge3, Note [TOOL-RESULT]1, Loop back |
| Handler: pago | Resolve Payment, Has payment image?, Download Payment, Send Payment, Normalize Payment1 |
| Handler: afiche | Resolve Poster, Has poster?, Download Poster, Send Poster, Normalize Poster |
| Handler: brochure | Resolve Brochure, Has brochure?, Send Brochure, Normalize Brochure |
| Handler: handoff | Prep Handoff, Assign Conversation, Set bot-off, have reason?, Send reason note, Normalize Handoff |
| Handler: desconocido | Unknown tool |

### Notas
- Implementa el diseño de "notas basado en historial" que se había prototipado por separado (`workflow-tool-calling-notes-based.json`, no subido a la instancia en su momento) — ahora es la versión productiva.
- El texto que el modelo escribe junto a una tool_call **ahora sí llega al cliente** (`Send preface to Chatwoot`), a diferencia de la versión anterior donde solo el turno final sin tools llegaba a `Send response`.
- El afiche y el método de pago se envían como **imagen adjunta** (descarga + `multipart-form-data`); el brochure se envía como **texto con el link** (sin adjunto) — sigue siendo una asimetría de diseño entre recursos "imagen" vs "enlace".
- `assignee_id` (7) y `chatwoot_base_url` (cuenta 1) están hardcodeados en `Config`, no vía variable de entorno.
- El modelo sigue siendo `qwen3.5:4b`; `maxIterations` = 6.
- Inconsistencia interna de Redis: `Getset redisflag` usa variables de entorno, pero `Has response and is valid flag? - delete flag` usa el cliente hardcodeado (`host:'redis'`).

---

## 5. Inscriptions AI agent

**ID**: `DO1KOwGNSULsQ20D` | **Creado**: 2026-06-08 | **Actualizado**: 2026-07-07 | **18 nodos**

### Propósito
Agente LangChain especializado exclusivamente en gestión de inscripciones a cursos. Lee el historial de la conversación desde Postgres, lo formatea como texto, y lo pasa al agente Ollama con herramientas HTTP que conectan a un Google Apps Script (Google Sheets). Responde en Chatwoot. Tiene lógica de debounce propia.

> **Cambio relevante**: toda la deuda técnica que documentaba la versión anterior (nodos desactivados/desconectados: `Get conversation messages`, `Message a local model`/`Send response` viejos, `Filter`/`Filter1`/`Filter2`/`Filter4`, `Get previous processing responce process`, `Register processing responce proccess`, `Cleanup Records`, `Is valid flag?`/`Get previous process`/`Set Redis flag` del enfoque viejo, `Call 'Stop execution'`, el código con error de sintaxis `Code in JavaScript2`, `Code in JavaScript3`, `Markdown to Wp text`) **fue eliminada por completo**. Los 18 nodos restantes son exactamente los que ya ejecutaban el flujo activo — coinciden 1:1 con la tabla de "nodos activos conectados" de la versión anterior de este documento.

### Trigger
Llamado por **Process input message** con `conversation_id`, cuando el usuario tiene el custom attribute `can-register-inscriptions = true`.

### Flujo activo
```
When Executed by Another Workflow (conversation_id)
  → Config (Set)
      chatwoot_bot_access_token, ai_service_url, model = qwen3.5:4b
      flag_key = processing:conversation:agentresponse:<id>
  → Getset redisflag (Code — SET flag_key $execution.id GET EX 600)
      ├─ Exist old flags? → Call 'Stop process execution'1 (mata respuesta anterior del agente)
      ├─ Get messages (Postgres — historial de mensajes con sender_type y display_name)
      │     → Code in JavaScript1 (formatea como "User: ..." / "Assistant: ..." con merge de notas ATTACH/TRANSCRIPTION)
      │           → Agente Inscripciones (LangChain Agent con Ollama qwen3.5:4b)
      │                 Herramientas: listar_hojas, crear_hoja, insertar_inscrito,
      │                               listar_inscritos, obtener_inscrito, eliminar_inscrito
      │                 → Exist response and is valid flag? - delete flag (GETDEL flag)
      │                       → Send response1 (HTTP POST Chatwoot)
      └─ Call 'Typing Loop' (fire-and-forget)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Config | set | Tokens, modelo, flag key |
| Getset redisflag | code | Debounce Redis |
| Exist old flags? | code | Filtra ejecución anterior |
| Call 'Stop process execution'1 | executeWorkflow | Cancela respuesta anterior |
| Get messages | postgres | Historial de mensajes de la conversación |
| Code in JavaScript1 | code | Formatea historial; mergea notas privadas ATTACH/TRANSCRIPTION |
| Agente Inscripciones | langchain.agent | Agente con tools HTTP al Apps Script (URL vía `$env.INSCRIPTIONS_SHEET_SCRIPT_URL`) |
| Ollama Chat Model | langchain.lmChatOllama | LLM: qwen3.5:4b (credencial `ollamaApi`) |
| listar_hojas | langchain.toolHttpRequest | GET al Apps Script — lista cursos |
| crear_hoja | langchain.toolHttpRequest | POST — crea nuevo curso |
| insertar_inscrito | langchain.toolHttpRequest | POST — inscribe alumno |
| listar_inscritos | langchain.toolHttpRequest | GET — lista inscritos de un curso |
| obtener_inscrito | langchain.toolHttpRequest | GET — obtiene inscrito por N |
| eliminar_inscrito | langchain.toolHttpRequest | POST — elimina inscrito |
| Exist response and is valid flag? - delete flag | code | GETDEL del flag, verifica ownership |
| Send response1 | httpRequest | Publica respuesta en Chatwoot |
| Call 'Typing Loop' | executeWorkflow | Indicador de escritura (fire-and-forget) |

Sin nodos desactivados ni desconectados.

### Notas
- Inconsistencia interna de Redis (mismo patrón que `Get Ollama Response — Tool Calling v3`): `Getset redisflag` usa variables de entorno, pero `Exist response and is valid flag? - delete flag` usa el cliente hardcodeado (`host:'redis'`).

---

## 6. Process attachment files

**ID**: `lVBz1pxJdOSi1GFR` | **Creado**: 2026-05-27 | **Actualizado**: 2026-07-07 | **12 nodos**

### Propósito
Procesa archivos adjuntos de Chatwoot (audio e imagen). Audio → transcripción con Faster Whisper. Imagen → descripción estructurada con Ollama vision (`qwen3.5:4b`, prompt orientado a que otro LLM consuma la descripción, no un humano). El resultado se publica como nota privada con prefijo `[TRANSCRIPTION]` o `[ATTACH]`. Al finalizar, dispara el núcleo del agente de ventas.

### Trigger
Llamado por **Process input message** (vía `Call 'Process atach'`) con: `account_id`, `conversation_id`, `message_id`, `file_type`, `attachment_data_url`.

### Flujo activo
```
Webhook (executeWorkflowTrigger — 5 params)
  → Config (Set)
      flag_key = processing:conversation:<id>:multimedia:<execution_id>, flag_ttl = 600, grace_time = 20
  → Set Redis flag (Redis SET flag_key $execution.id EX 600)
  → File type switch (Switch — por file_type, fallback "none")

      audio → Download audio (HTTP GET attachment_data_url, responseFormat: file)
                → Transcript (POST http://faster-whisper:8000/v1/audio/transcriptions
                              model: Systran/faster-whisper-medium, language: es)
                      → Armar nota (Code — '[TRANSCRIPTION]<texto>')
                            → Add reply private note (Chatwoot, token vía $env.CHATWOOT_BOT_ACCESS_TOKEN)
                                  → Unset Redis flag → Call 'Get Ollama Response'1 (fire-and-forget)

      image → Download audio1 (HTTP GET attachment_data_url, responseFormat: file)
                → Analyze image (Ollama qwen3.5:4b, resource: image — prompt estructurado
                      TIPO/TEXTO_LITERAL/ELEMENTOS_VISUALES/DATOS/DESCRIPCION)
                      → Armar nota (Code — '[ATTACH]<attached_image>...')
                            → Add reply private note → Unset Redis flag → Call 'Get Ollama Response'1
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook | executeWorkflowTrigger | Entrada (5 params) |
| Config | set | Flag key por ejecución |
| Set Redis flag | redis | Marca ejecución en curso |
| File type switch | switch | Bifurca audio vs imagen |
| Download audio | httpRequest | Descarga audio |
| Transcript | httpRequest | Faster Whisper transcripción |
| Download audio1 | httpRequest | Descarga imagen |
| Analyze image | langchain.ollama | Descripción de imagen con qwen3.5:4b |
| Armar nota | code | Construye el texto prefijado |
| Add reply private note | httpRequest | Nota privada en Chatwoot |
| Unset Redis flag | redis | Libera el flag de multimedia |
| Call 'Get Ollama Response'1 | executeWorkflow | Dispara **Get Ollama Response — Tool Calling v3** |

Sin nodos desactivados ni desconectados (el viejo trigger webhook directo y su tracking Postgres, documentados en la versión anterior, ya no existen).

---

## 7. Typing Loop

**ID**: `4UiUiyRKtl5SwGuP` | **Creado**: 2026-06-01 | **Actualizado**: 2026-06-16 | **6 nodos**

### Propósito
Mantiene el indicador de escritura ("typing") activo en WhatsApp mientras el bot procesa una respuesta. Consulta periódicamente la clave Redis de procesamiento; si sigue activa, reenvía el typing indicator y espera 25 segundos más. Se detiene cuando la clave desaparece.

### Trigger
Llamado por **Get Ollama Response — Tool Calling v3** e **Inscriptions AI agent** con `conversation_id` y `processig_flag_key` [sic].

### Flujo activo
```
When Executed by Another Workflow (conversation_id, processig_flag_key)
  → Merge an forward first data (Code — reenvía el primer item)
  → Get message source id (Postgres)
      SELECT m.source_id FROM conversations cv JOIN messages m ...
      WHERE cv.id = <conversation_id> AND m.sender_type = 'Contact'
      ORDER BY m.created_at DESC LIMIT 1
  → Send message read status (HTTP POST Meta Graph API v23.0, credencial httpBearerAuth "Bearer WP Business")
      POST graph.facebook.com/v23.0/1109445028926192/messages/
      { messaging_product: 'whatsapp', status: 'read', message_id: <source_id>,
        typing_indicator: { type: 'text' } }
  → Wait 25s
  → Get processing flag (Code — REDIS GET processig_flag_key, cliente hardcodeado)
      Si el flag existe → vuelve a Merge an forward first data (loop)
      Si no existe → fin
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Merge an forward first data | code | Reenvía datos al inicio del loop |
| Get message source id | postgres | Obtiene source_id del último mensaje del contacto |
| Send message read status | httpRequest | Typing indicator vía Meta API (credencial Bearer) |
| Wait | wait | Espera 25 segundos |
| Get processing flag | code | Redis GET — verifica si el proceso sigue activo |

Sin nodos desactivados ni desconectados.

### Notas
- La phone number ID de WhatsApp cambió de `1125551453976021` (documentado antes) a **`1109445028926192`**; sigue hardcodeada en la URL en vez de variable de entorno.
- El token de la Meta Graph API ya no está en texto plano: usa la credencial n8n `httpBearerAuth` ("Bearer WP Business").
- El nombre del parámetro `processig_flag_key` conserva el typo (falta la "n").

---

## 8. Stop process execution

**ID**: `34PcgWs057ll8MVc` | **Creado**: 2026-05-29 | **Actualizado**: 2026-07-07 | **4 nodos**

### Propósito
Detiene una ejecución n8n en curso vía la API REST interna, y opcionalmente elimina una clave Redis. Es el mecanismo de cancelación usado por prácticamente todos los flujos con debounce (8 workflows lo invocan).

### Trigger
Llamado por múltiples workflows con `execution_id` y `redis_key` (puede ser `null`).

### Flujo activo
```
When Executed by Another Workflow (execution_id, redis_key)
  → Edit Fields (Set)
      n8n_api_key = {{ $env.N8N_API_KEY }}
  → HTTP Request
      POST http://localhost:5678/api/v1/executions/<execution_id>/stop
      Header: X-N8N-API-KEY: <api_key>
  → Redis
      DELETE <redis_key>  (no-op si redis_key es null)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Edit Fields | set | Inyecta el API key desde variable de entorno |
| HTTP Request | httpRequest | Llama a `/executions/<id>/stop` |
| Redis | redis | DELETE de la clave Redis (si aplica) |

Sin nodos desactivados ni desconectados.

### Notas
- **La API key ya no está hardcodeada**: usa `$env.N8N_API_KEY` (resuelto desde la auditoría de credenciales anterior).
- Callers actuales: `Await message grace time`, `Get Ollama Response — Tool Calling v3`, `Inscriptions AI agent`, `Process input message`, `Refresh system prompt`, `Save base system prompt`, `Save courses data`, `Save payment methods data`.

---

## 9. Stop execution

**ID**: `MXixvoKC47ifoAfU` | **Creado**: 2026-06-02 | **Actualizado**: 2026-07-07 | **3 nodos**

### Propósito
Versión anterior de "Stop process execution". Solo detiene la ejecución vía API, sin limpiar Redis.

### Trigger
Llamado por otro workflow con `execution_id` — **actualmente ningún workflow lo invoca**, ni siquiera desde nodos desactivados.

### Flujo activo
```
When Executed by Another Workflow (execution_id)
  → Edit Fields (Set — n8n_api_key = {{ $env.N8N_API_KEY }})
  → HTTP Request (POST http://localhost:5678/api/v1/executions/<execution_id>/stop)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Edit Fields | set | API key desde variable de entorno |
| HTTP Request | httpRequest | Stop vía API |

### Notas
- **Candidato a archivar**: la limpieza de nodos desactivados de los demás workflows (ejecutada entre la versión anterior de este documento y ahora) eliminó también las últimas referencias muertas a este workflow. Ya no aparece en ningún archivo salvo el suyo propio — es un flujo completamente huérfano.
- Ya no tiene la API key hardcodeada (mismo fix que "Stop process execution").

---

## 10. Save base system prompt

**ID**: `2Woea4PA1MiZIsXt` | **Creado**: 2026-06-03 | **Actualizado**: 2026-07-07 | **9 nodos**

### Propósito
Recibe un nuevo system prompt base vía webhook (típicamente enviado desde Google Drive), aplica un debounce de 5 segundos, lo guarda en el KV store y dispara "Refresh system prompt" para regenerar el prompt activo combinando base + cursos + métodos de pago.

> **Cambio relevante**: los 7 nodos desactivados que documentaba la versión anterior (`Get concatenated`, `Get previous process`, `Exist previous process?`, `Set Redis flag`, `Call 'Stop execution'`, `Filter2`, `Get previous flag`) fueron eliminados. Quedan los 9 nodos del flujo activo.

### Trigger
`POST /webhook/drive-system-prompt` con body `{ "content": "<texto del prompt>" }`

### Flujo activo
```
Webhook System Prompt (/drive-system-prompt)
  → Config (Set)
      flag_key = sheets:sysprompt:pending, flag_ttl = 30s, grace_time = 5s
      sys_prompt_base_key = sysprompt:base, sys_prompt_key = sysprompt
      courses_details_concatenated_key = courses:concatenated
      fallback_prompt = "No tienes conocimento, delega la conversación a un agente humano"
  → Getset redisflag (Code — REDIS SET key $execution.id GET EX 30, vía env vars)
      ├─ Exist old flags? → Call 'Stop process execution' (mata envío anterior)
      └─ Wait 5s
            → Is valid flag? (Code — REDIS GET, verifica ownership)
                  → Call 'KV Store — Write' (key: sysprompt:base, value: body.content)
                        → Call 'Refrest system prompt' (dispara la regeneración del prompt activo)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook System Prompt | webhook | Entrada |
| Config | set | Configuración |
| Getset redisflag | code | Debounce Redis (env vars) |
| Exist old flags? | code | Filtra ejecución anterior |
| Call 'Stop process execution' | executeWorkflow | Cancela envío anterior |
| Wait | wait | Grace time 5s |
| Is valid flag? | code | Verifica ownership |
| Call 'KV Store — Write' | executeWorkflow | Guarda `sysprompt:base` |
| Call 'Refrest system prompt' | executeWorkflow | Dispara **Refresh system prompt** (el nodo conserva la etiqueta cacheada con el typo viejo) |

Sin nodos desactivados ni desconectados.

---

## 11. Save courses data

**ID**: `KiCYYElIF3TXCjVd` | **Creado**: 2026-06-02 | **Actualizado**: 2026-07-07 | **14 nodos**

### Propósito
Recibe el catálogo de cursos activos (desde Google Drive), lo parsea, y guarda en el KV store: el JSON de cursos (`courses:all`), el texto concatenado (`courses:concatenated`) y **dos enums nuevos** (`courses:posters:enum`, `courses:brochures:enum` — solo los códigos de los cursos que sí tienen ese recurso). Dispara "Refresh system prompt" al terminar.

> **Cambios relevantes** frente a la versión anterior de este documento:
> - **El formato del body cambió de CSV a JSON**: antes se documentaba `name;details;poster_url;brochure_url` como CSV; el código actual hace `JSON.parse(courses)` directamente sobre `body.content` — el body ahora es un array JSON de objetos `{name, details, poster_url, brochure_url}`.
> - Se agregaron los nodos **Set posters enum** y **Set brochures enum** (más las 2 ramas de `Merge`, que pasó de 2 a 4 inputs).
> - Los 8 nodos desactivados que documentaba la versión anterior (`Filter1`, `Call 'Stop execution'`, `Set courses`, `Set concatenated`, `Get previous process`, `Set Redis flag`, `Filter`, `Get previous flag`) fueron eliminados por completo.
> - `Is valid flag?` pasó de tener el chequeo de ownership comentado a tenerlo activo (cambio de comportamiento real, no solo cleanup).

### Trigger
`POST /webhook/drive-active-courses` con body `{ "content": "<JSON array de cursos>" }`

### Flujo activo
```
Webhook Courses Content (/drive-active-courses)
  → Config (Set)
      flag_key = sheets:courses:pending, flag_ttl = 30s, grace_time = 5s
      courses_key = courses:all, courses_details_concatenated_key = courses:concatenated
      courses_posters_enum_key = courses:posters:enum, courses_brochures_enum_key = courses:brochures:enum
  → Getset redisflag (Code — REDIS SET key $execution.id GET EX 30, cliente hardcodeado)
      ├─ Exist old flags? → Call 'Stop process execution'
      └─ Wait 5s
            → Is valid flag? (Code — verifica ownership, cliente hardcodeado)
                  → Code in JavaScript (JSON.parse(body.content) → genera concatenated +
                        posters_enum + brochures_enum por índice: C1, C2, ...)
                        ├─ Set courses1 → KV Write (courses:all)
                        ├─ Set concatenated1 → KV Write (courses:concatenated)
                        ├─ Set posters enum → KV Write (courses:posters:enum)
                        └─ Set brochures enum → KV Write (courses:brochures:enum)
                              → Merge (combineByPosition, 4 inputs)
                                    → Call 'Refrest system prompt' (fire-and-forget)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook Courses Content | webhook | Entrada |
| Config | set | Configuración |
| Getset redisflag | code | Debounce Redis |
| Exist old flags? | code | Filtra ejecución anterior |
| Call 'Stop process execution' | executeWorkflow | Cancela envío anterior |
| Wait | wait | Grace time 5s |
| Is valid flag? | code | Verifica ownership (chequeo activo) |
| Code in JavaScript | code | Parseo de JSON + generación de enums |
| Set courses1 | executeWorkflow (KV Write) | Guarda `courses:all` |
| Set concatenated1 | executeWorkflow (KV Write) | Guarda `courses:concatenated` |
| Set posters enum | executeWorkflow (KV Write) | Guarda `courses:posters:enum` |
| Set brochures enum | executeWorkflow (KV Write) | Guarda `courses:brochures:enum` |
| Merge | merge | Sincroniza las 4 escrituras |
| Call 'Refrest system prompt' | executeWorkflow | Regenera system prompt |

Sin nodos desactivados ni desconectados.

---

## 12. Save payment methods data

**ID**: `isLbz4UKIoprkNAH` | **Creado**: 2026-06-17 | **Actualizado**: 2026-07-07 | **13 nodos**

### Propósito
Workflow **nuevo desde la versión anterior de este documento**. Recibe el catálogo de métodos de pago (banco/QR/cuenta) desde Google Drive, lo parsea, guarda en el KV store el JSON crudo (`payments:all`), el texto concatenado (`payments:concatenated`) y el enum de códigos (`payments:enum`), y dispara "Refresh system prompt". Sigue el mismo patrón que "Save courses data".

### Trigger
`POST /webhook/drive-payment-methods` con body `{ "content": "<JSON array de métodos de pago>" }`

### Flujo activo
```
Webhook Courses Content (/drive-payment-methods)   ← nombre de nodo heredado por copy-paste, sin renombrar
  → Config (Set)
      flag_key = sheets:payment:pending, flag_ttl = 30s, grace_time = 5s
      payments_methods_key = payments:all
      payments_methods_concatenated_key = payments:concatenated
      payments_methods_enum_key = payments:enum
  → Getset redisflag (Code — REDIS SET key $execution.id GET EX 30, vía env vars)
      ├─ Exist old flags? → Call 'Stop process execution'
      └─ Wait 5s
            → Is valid flag? (Code — GETDEL para verificar ownership, vía env vars)
                  → Code in JavaScript (JSON.parse(body.content) → concatenated + payments_enum
                        por índice: M1, M2, ...)
                        ├─ Call 'KV Store — Write' → KV Write (payments:all = contenido crudo)
                        ├─ Set concatenated1 → KV Write (payments:concatenated)
                        └─ Set posters enum → KV Write (payments:enum)   ← nombre heredado, escribe el enum de pagos
                              → Merge (combineByPosition, 3 inputs)
                                    → Call 'Refrest system prompt' (fire-and-forget)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| Webhook Courses Content | webhook | Entrada (`/drive-payment-methods`) |
| Config | set | Configuración |
| Getset redisflag | code | Debounce Redis (env vars) |
| Exist old flags? | code | Filtra ejecución anterior |
| Call 'Stop process execution' | executeWorkflow | Cancela envío anterior |
| Wait | wait | Grace time 5s |
| Is valid flag? | code | Verifica ownership vía GETDEL (env vars) |
| Code in JavaScript | code | Parseo de JSON + generación de enum |
| Call 'KV Store — Write' | executeWorkflow | Guarda `payments:all` (contenido crudo, sin re-serializar) |
| Set concatenated1 | executeWorkflow (KV Write) | Guarda `payments:concatenated` |
| Set posters enum | executeWorkflow (KV Write) | Guarda `payments:enum` |
| Merge | merge | Sincroniza las 3 escrituras |
| Call 'Refrest system prompt' | executeWorkflow | Regenera system prompt |

Sin nodos desactivados ni desconectados.

### Notas
- **Nombres heredados por copy-paste, sin renombrar**: el nodo webhook se sigue llamando "Webhook Courses Content" (path real: `drive-payment-methods`), y el nodo que escribe `payments:enum` se llama "Set posters enum" (viene de duplicar el workflow de cursos). No afecta funcionamiento, pero puede confundir al leer el flujo.
- El código de parseo usa la variable `poyment` (typo de "payment"), cosmético.
- Igual que `Save courses data` (que tampoco reserializa `courses`), aquí `payments:all` se guarda con el `body.content` crudo tal como llegó del webhook — no hay asimetría real entre ambos flujos en este punto.

---

## 13. Refresh system prompt

**ID**: `I5pQmYPtreJGoZha` | **Creado**: 2026-06-03 | **Actualizado**: 2026-07-07 | **10 nodos**

> El typo de nombre ("Refrest") **fue corregido** — el workflow se llama ahora "Refresh system prompt". Sin embargo, los nodos `executeWorkflow` que lo invocan desde `Save base system prompt`, `Save courses data` y `Save payment methods data` conservan el `cachedResultName` viejo "Refrest system prompt" (cosmético: es solo la etiqueta cacheada en el nodo llamador, no afecta la ejecución).

### Propósito
Regenera el system prompt activo (`sysprompt`) combinando el base prompt (`sysprompt:base`) con el texto concatenado de cursos (`courses:concatenated`) **y ahora también de métodos de pago** (`payments:concatenated`). Sustituye los placeholders `<courses_content/>` y `<payment_methods/>` en el base prompt. Si falta cualquiera de los tres insumos, usa el fallback prompt.

> **Cambio relevante**: además de agregar el placeholder de pagos, ahora usa `Call 'KV Store — Read'` con lectura **multi-clave en un solo llamado** (`[courses:concatenated, payments:concatenated, sysprompt:base]`), reemplazando las dos llamadas separadas que documentaba la versión anterior. Los 6 nodos desactivados que documentaba esa versión (`Get previous process`, `Set Redis flag`, `Exist previous process?`, `Call 'Stop execution'`, `Filter2`, `Get previous flag`) fueron eliminados.

### Trigger
Llamado por **Save base system prompt**, **Save courses data** y **Save payment methods data** (los tres vía `passthrough` — no pasan parámetros).

### Flujo activo
```
When Executed by Another Workflow (passthrough)
  → Config (Set)
      flag_key = n8n:sysprompt:pending, flag_ttl = 20s, grace_time = 5s
      sys_prompt_base_key = sysprompt:base, sys_prompt_key = sysprompt
      courses_details_concatenated_key = courses:concatenated
      payments_methods_concatenated_key = payments:concatenated
      fallback_prompt = "No tienes conocimento, delega la conversación a un agente humano"
  → Getset redisflag (Code — REDIS SET key $execution.id GET EX 20, vía env vars)
      ├─ Exist old flags? → Call 'Stop process execution'
      └─ Wait 5s
            → Is valid flag? (Code — verifica ownership, vía env vars)
                  → Call 'KV Store — Read' (batch: [courses:concatenated, payments:concatenated, sysprompt:base])
                        → Code in JavaScript
                            Si hay courses_concatenated Y payments_concatenated Y base_system_prompt:
                              system_prompt = base.replaceAll('<courses_content/>', courses)
                                                  .replaceAll('<payment_methods/>', payments)
                            Si no:
                              system_prompt = fallback_prompt
                                  → Call 'KV Store — Write' (sysprompt = system_prompt)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Config | set | Configuración |
| Getset redisflag | code | Debounce Redis (env vars) |
| Exist old flags? | code | Filtra ejecución anterior |
| Call 'Stop process execution' | executeWorkflow | Cancela refresh anterior |
| Wait | wait | Grace time 5s |
| Is valid flag? | code | Verifica ownership |
| Call 'KV Store — Read' | executeWorkflow | Lee `courses:concatenated` + `payments:concatenated` + `sysprompt:base` en un llamado |
| Code in JavaScript | code | Combina base + cursos + pagos |
| Call 'KV Store — Write' | executeWorkflow | Guarda `sysprompt` |

Sin nodos desactivados ni desconectados.

---

## 14. KV Store — Write

**ID**: `AB2b8LRVmKVEPoOK` | **Creado**: 2026-06-03 | **Actualizado**: 2026-06-03 | **4 nodos**

### Propósito
Persistencia dual de clave-valor: escribe en Redis (para velocidad) y en Postgres (tabla `kv_store`, para durabilidad). Librería compartida usada por múltiples workflows. Sin cambios desde la versión anterior de este documento.

### Trigger
Llamado por otros workflows con `key` y `value`.

### Flujo activo
```
When Executed by Another Workflow (key, value)
  → Config (Set — normaliza key y value)
  → Redis SET (key = $json.key, value = $json.value)
  → Postgres UPSERT
      INSERT INTO kv_store(key, value, updated_at) VALUES (...)
      ON CONFLICT(key) DO UPDATE SET value = ..., updated_at = NOW()
      RETURNING key, value, updated_at
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada |
| Config | set | Normalización |
| Redis SET | redis | Escritura en caché |
| Postgres UPSERT | postgres | Persistencia duradera |

Sin nodos desactivados ni desconectados.

### Callers actuales
`Refresh system prompt`, `Save base system prompt`, `Save courses data` (×4: `courses:all`, `courses:concatenated`, `courses:posters:enum`, `courses:brochures:enum`), `Save payment methods data` (×3: `payments:all`, `payments:concatenated`, `payments:enum`).

---

## 15. KV Store — Read

**ID**: `ulyHlUNZzAsLSnl1` | **Creado**: 2026-06-03 | **Actualizado**: 2026-07-07 | **8 nodos**

> **Reescrito por completo desde la versión anterior de este documento.** Antes leía **una sola clave** con cascada Redis→Postgres vía dos nodos IF. Ahora es una lectura **multi-clave (batch)**: recibe un array `keys`, resuelve todas contra Redis en una pasada, cae a Postgres solo para las que faltaron ("misses"), recachea en Redis lo encontrado en Postgres, y devuelve **un solo item** `{ [clave]: valor|null, ... }` con todas las claves pedidas.

### Propósito
Lectura con cache-aside multi-clave: intenta primero en Redis para cada clave del array recibido; para las que no están, consulta Postgres en un solo `SELECT ... WHERE key IN (...)` y las recachea en Redis. Retorna un único objeto con todas las claves solicitadas.

### Trigger
Llamado por otros workflows con `keys` (array).

### Flujo activo
```
When Executed by Another Workflow (keys: array)
  → Normalize Keys1 (Code — castea a string, descarta vacíos; si la lista queda vacía usa '__none__')
  → Redis GET2 (Redis GET, un item por key)
      → Aggregate Redis1 (Code — arma redisMap con los hits, "misses" con las que no
            resolvieron en Redis, y "missesSql" ya escapado para el IN())
            → Postgres SELECT2 (SELECT key, value FROM kv_store WHERE key IN (missesSql))
                  ├─ Only real rows1 (Filter — descarta filas sin key)
                  │     → Redis SET (cache)2 (recachea lo encontrado en Postgres)
                  │           → Build Result1
                  └─ Build Result1 (Code — combina redisMap (preferente) + filas de Postgres
                        (fallback) → { [key]: value|null } para cada key pedida)
```

### Nodos
| Nodo | Tipo | Rol |
|------|------|-----|
| When Executed by Another Workflow | executeWorkflowTrigger | Entrada (`keys: array`) |
| Normalize Keys1 | code | Normaliza el array de claves |
| Redis GET2 | redis | Lectura de caché, una por clave |
| Aggregate Redis1 | code | Separa hits de Redis vs. misses a consultar en Postgres |
| Postgres SELECT2 | postgres | Fallback batch a DB (`WHERE key IN (...)`) |
| Only real rows1 | filter | Descarta filas sin `key` |
| Redis SET (cache)2 | redis | Repoblación del caché con lo hallado en Postgres |
| Build Result1 | code | Combina Redis + Postgres → `{ [key]: value\|null, ... }` |

Sin nodos desactivados ni desconectados.

### Callers actuales
- `Get Ollama Response — Tool Calling v3` (nodo `Call 'KV-Read'` — batch: `sysprompt`, `courses:all`, `payments:all`)
- `Refresh system prompt` (nodo `Call 'KV Store — Read'` — batch: `courses:concatenated`, `payments:concatenated`, `sysprompt:base`)

---

## Mapa de dependencias entre workflows

```
Chatwoot
  └── Webhook Fan-out  (wp-goblin)
        └──→ Process input message  (process-messages)
               ├──→ Inscriptions AI agent  (si can-register-inscriptions=true)
               │      ├──→ Typing Loop
               │      └──→ Stop process execution
               │
               ├──→ Await message grace time
               │      ├──→ Stop process execution
               │      └──→ Get Ollama Response — Tool Calling v3
               │             ├──→ KV Store — Read (batch: sysprompt, courses:all, payments:all)
               │             ├──→ Typing Loop
               │             └──→ Stop process execution
               │
               ├──→ Process attachment files
               │      └──→ Get Ollama Response — Tool Calling v3
               │
               └──→ Stop process execution (cancela respuesta anterior)

Google Drive / Apps Script (fuentes externas)
  ├──→ Save base system prompt  (drive-system-prompt)
  │      ├──→ KV Store — Write (sysprompt:base)
  │      ├──→ Stop process execution
  │      └──→ Refresh system prompt
  │
  ├──→ Save courses data  (drive-active-courses)
  │      ├──→ KV Store — Write (×4: courses:all, courses:concatenated, courses:posters:enum, courses:brochures:enum)
  │      ├──→ Stop process execution
  │      └──→ Refresh system prompt
  │
  └──→ Save payment methods data  (drive-payment-methods)
         ├──→ KV Store — Write (×3: payments:all, payments:concatenated, payments:enum)
         ├──→ Stop process execution
         └──→ Refresh system prompt
                ├──→ KV Store — Read (batch: courses:concatenated, payments:concatenated, sysprompt:base)
                ├──→ Stop process execution
                └──→ KV Store — Write (sysprompt)

Huérfano: Stop execution (MXixvoKC47ifoAfU) — sin callers, activos ni desactivados.
```

---

## Estado de limpieza (resumen)

La versión anterior de este documento (2026-06-12) listaba una cantidad considerable de nodos desactivados y desconectados por workflow (multimedia legacy en `Process input message`, tracking Postgres muerto en varios flujos, herramientas viejas en `Inscriptions AI agent`, credenciales en texto plano, etc.). **Toda esa limpieza ya se ejecutó**: los 15 workflows activos suman 180 nodos y los 180 están activos y conectados — no queda ningún nodo `disabled: true` ni ningún nodo activo sin conexión de entrada desde el flujo principal.

Lo que sigue pendiente o queda como nota para el futuro:

### Workflow huérfano
| Workflow | Razón |
|----------|-------|
| **Stop execution** (`MXixvoKC47ifoAfU`) | Cero referencias en cualquier otro workflow (ni siquiera desde nodos desactivados). Candidato a archivar. |

### Inconsistencias de implementación (no bugs, pero vale unificar)
| Tema | Detalle |
|------|---------|
| Cliente Redis | Hardcodeado (`host:'redis'`) en `Process input message`, `Save courses data`, `Typing Loop`, y un nodo cada uno de `Get Ollama Response — Tool Calling v3` e `Inscriptions AI agent`; vía env vars en `Await message grace time`, `Save base system prompt`, `Save payment methods data`, `Refresh system prompt`, y el otro nodo de esos mismos dos workflows. |
| Nombres heredados por copy-paste | En `Save payment methods data`: el webhook se llama "Webhook Courses Content" (path real `drive-payment-methods`) y el nodo que escribe `payments:enum` se llama "Set posters enum". |
| Etiqueta cacheada desactualizada | Los nodos `executeWorkflow` que llaman a `Refresh system prompt` desde `Save base system prompt`, `Save courses data` y `Save payment methods data` conservan el `cachedResultName` "Refrest system prompt" (typo viejo) aunque el workflow ya fue renombrado. Cosmético. |
| Valores hardcodeados no-secretos | `assignee_id` (7) y `chatwoot_base_url` (cuenta 1) en `Get Ollama Response — Tool Calling v3`; phone number ID de WhatsApp (`1109445028926192`) en `Typing Loop`. Ninguno es un secreto, pero acoplan el workflow a un entorno específico. |

### Credenciales
Las 3 credenciales en texto plano de la auditoría previa (JWT de n8n, token de bot de Chatwoot, URL del Apps Script) **ya están migradas a variables de entorno** en todos los workflows donde aparecían. El token de la Meta Graph API en `Typing Loop` usa una credencial n8n (`httpBearerAuth`).
