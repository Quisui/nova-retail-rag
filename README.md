# Asistente Inteligente de Conocimiento Corporativo — NovaRetail

Sistema RAG empresarial para consultas internas en lenguaje natural sobre documentos corporativos.

## Stack

- Backend: Laravel (compatible con 11/12), PHP 8.2+
- Frontend: Blade + Tailwind CSS + Alpine.js
- Base de datos: PostgreSQL + pgvector
- IA: Gemini API
  - Chat: `gemini-1.5-flash`
  - Embeddings: `text-embedding-004`

## Arquitectura

```text
app/
 ├── Http/Controllers/
 │   ├── DashboardController.php
 │   ├── DocumentController.php
 │   └── ChatController.php
 ├── Services/
 │   ├── GeminiService.php
 │   ├── RagService.php
 │   └── DocumentProcessorService.php
 ├── Repositories/
 │   ├── DocumentRepository.php
 │   ├── DocumentChunkRepository.php
 │   └── ChatMessageRepository.php
 ├── Jobs/
 │   ├── ProcessDocumentJob.php
 │   └── GenerateEmbeddingsJob.php
 ├── DTOs/
 │   ├── ChatAnswerData.php
 │   └── ChunkData.php
 └── Models/
     ├── Document.php
     ├── DocumentChunk.php
     └── ChatMessage.php
```

## Funcionalidades

- Subida y gestión de documentos (`PDF`, `TXT`, `MD`)
- Extracción y limpieza de texto
- Chunking con overlap
- Generación de embeddings con Gemini
- Búsqueda semántica vectorial con pgvector
- Chat RAG con contexto y fuentes
- Historial persistido de preguntas/respuestas/contexto
- Procesamiento asíncrono con colas (`database queue`)
- Dashboard operacional

## Modelo de datos

- `documents`
- `document_chunks` (`embedding vector(768)` en PostgreSQL)
- `chat_messages`
- `jobs`, `job_batches`, `failed_jobs`

## Flujo RAG

1. Usuario envía pregunta.
2. Se genera embedding de pregunta.
3. Se buscan chunks por similitud vectorial (`ORDER BY embedding <-> query LIMIT n`).
4. Se construye contexto recuperado.
5. Gemini genera respuesta restringida al contexto.
6. Se guarda historial con fuentes.

Prompt aplicado:

> Eres un asistente corporativo de NovaRetail. Debes responder únicamente usando el contexto proporcionado. No inventes información. Si no existe suficiente contexto responde: 'No encontré información suficiente en la base de conocimiento para responder con certeza.'

## Requisitos

- PHP 8.2+
- Composer
- Node 18+
- PostgreSQL 15+ con extensión `vector`

## Variables de entorno

```env
APP_NAME=NovaRetail
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=pgvector
DB_PORT=5432
DB_DATABASE=novaretail_rag
DB_USERNAME=novaretail_user
DB_PASSWORD=secret

QUEUE_CONNECTION=database

GEMINI_API_KEY=
GEMINI_CHAT_MODEL=gemini-1.5-flash
GEMINI_EMBEDDING_MODEL=text-embedding-004
```

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run dev
php artisan serve
```

En otra terminal, para colas:

```bash
php artisan queue:work
```

## Docker

El proyecto incluye:

- `Dockerfile`
- `docker-compose.yml`
- servicio `app`
- servicio `postgres`
- servicio `pgvector`

Levantar entorno:

```bash
docker compose up -d --build
```

Ejecutar migraciones/seed manualmente (si aplica):

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

## Despliegue en Railway

Archivos incluidos para Railway:

- `railway.json`
- `railway/start-app.sh`
- `railway/start-worker.sh`

### Servicio web

1. Conecta este repo en Railway.
2. Railway detectará `railway.json` y construirá con `Dockerfile`.
3. Start command web: `sh railway/start-app.sh` (ya definido en `railway.json`).

### Servicio worker (colas)

1. Crea un segundo servicio en el mismo proyecto Railway apuntando al mismo repo.
2. Configura el start command de ese servicio a:

```bash
sh railway/start-worker.sh
```

3. Copia las mismas variables de entorno del servicio web.

### Variables mínimas en Railway

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.railway.app
APP_KEY=base64:...

DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database

GEMINI_API_KEY=...
GEMINI_CHAT_MODEL=gemini-2.5-flash
GEMINI_EMBEDDING_MODEL=gemini-embedding-001
GEMINI_EMBEDDING_DIMENSIONS=768
```

Notas:

- `start-app.sh` ejecuta `php artisan migrate --force` en cada deploy.
- El worker procesa cola con `queue:work database`.
- Mantén `APP_KEY` fija en Railway para no invalidar sesiones/cifrado entre despliegues.

## Uso funcional

1. Ir a `http://localhost:8000`
2. Cargar documentos desde **Documentos > Subir documento**
3. Esperar procesamiento (`queue:work`)
4. Consultar en **Chat IA**
5. Revisar trazabilidad en **Historial**

## Seeders incluidos

- Política devoluciones
- Política garantías
- Escalamiento reclamos
- Procedimiento logístico
- Atención al cliente

Si `GEMINI_API_KEY` existe, el seeder intenta generar embeddings reales automáticamente.

## Testing

```bash
php artisan test
```

Cobertura de pruebas incluida:

- subida de documentos
- despacho de jobs
- generación/almacenamiento de chat
- búsqueda semántica (fallback tests)
- procesamiento de chunks

## Screenshots (placeholders)

- `docs/screenshots/dashboard.png`
- `docs/screenshots/documents.png`
- `docs/screenshots/chat.png`
- `docs/screenshots/history.png`

## Notas de operación

- Si Gemini falla por timeout/API key ausente, el sistema devuelve respuesta controlada y registra logs.
- No se hardcodean llaves API.
- En ambientes sin PostgreSQL/pgvector (tests), se usa fallback de búsqueda semántica para pruebas.
