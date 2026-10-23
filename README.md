# Portal Docente · Universidad de Cartagena

Portal web de apoyo a la docencia para los programas de **Ingeniería de Sistemas** y **Administración Industrial**.
Permite publicar material por asignatura, controlar el acceso con listas de estudiantes (correo `@unicartagena.edu.co`)
y aplicar **evaluaciones en línea tipo Saber Pro**.

## Funcionalidades

| Módulo | Docente | Estudiante |
|---|---|---|
| Asignaturas | Crear por código, periodo (AAAA-1/2), grupo y programa; activar/desactivar | Ver solo las asignaturas donde está habilitado |
| Listas / acceso | Cargar CSV o pegar lista (código; nombre; correo), habilitar/deshabilitar, retirar, aprobar solicitudes | Ingresar con Google institucional; si no está en la lista, puede solicitar acceso |
| Material | Archivos (PDF, Office, ZIP, notebooks…), enlaces y notas, agrupados por unidad/semana, visibles u ocultos | Ver/descargar (los archivos se sirven protegidos, sin enlaces públicos) |
| Evaluaciones | Ventana de fechas, tiempo límite, intentos, barajado de preguntas y opciones, nota sobre 5 (configurable), vista previa, duplicar | Temporizador, **autoguardado**, navegación por preguntas, envío automático al agotarse el tiempo |
| Tipos de pregunta | **Selección múltiple** con única respuesta · **Apareamiento** (con distractores) · **Completar frases** (respuestas alternativas, opcional banco de palabras) · **Respuesta libre** con límite de caracteres | — |
| Calificación | Automática en preguntas cerradas (parcial en apareamiento y completar); manual en respuesta libre con comentarios; análisis de % de acierto por pregunta; exportar notas a CSV (Excel) | Ver nota y, si el docente lo permite, la clave y retroalimentación **después del cierre** |

## Arquitectura

- **Monolito** Laravel 12 (PHP 8.2+) con vistas Blade, sin paso de compilación de frontend (CSS/JS planos en `public/`) → corre en hosting compartido.
- **Base de datos:** PostgreSQL en **Supabase** (plan gratuito). Sesiones y caché en archivos para reducir consultas remotas.
- **Autenticación:** Google OAuth 2.0 / OpenID Connect (sin librerías externas), restringido al dominio institucional y a la lista cargada por el docente. El docente tiene además ingreso con contraseña de respaldo.

```
app/
  Http/Controllers/Teacher/   asignaturas, listas, material, evaluaciones, preguntas, calificación
  Http/Controllers/Student/   asignaturas, presentación de evaluaciones, solicitudes de acceso
  Services/                   GoogleOAuth, RosterImporter, QuestionBuilder, QuizGrader
  Models/                     User, Subject, Enrollment, AccessRequest, Material, Quiz, Question, Attempt, Answer
resources/views/              vistas Blade (docente / estudiante)
tests/Feature/PortalTest.php  pruebas de extremo a extremo
```

## Instalación local

```bash
composer install
cp .env.example .env && php artisan key:generate
# Para desarrollo local puede usar SQLite: DB_CONNECTION=sqlite (y comentar DB_HOST…)
php artisan migrate --seed        # datos demo: docente@unicartagena.edu.co / docente-demo-123
php artisan serve
php artisan test                  # pruebas
```

## Puesta en producción

### 1. Base de datos en Supabase (gratis)
1. Cree un proyecto en <https://supabase.com> (región `us-east-1` o la más cercana).
2. *Project Settings → Database → Connection string* → elija **Session pooler** (compatible con IPv4, necesario en Hostinger).
3. Copie host, usuario (`postgres.<ref>`) y contraseña en el `.env` (`DB_*`, con `DB_SSLMODE=require`).
4. Nota: en el plan gratuito el proyecto se **pausa tras 7 días sin actividad**; se reactiva desde el panel. Con uso semanal normal no ocurre.

### 2. Google OAuth
1. <https://console.cloud.google.com> → nuevo proyecto → *APIs y servicios → Pantalla de consentimiento OAuth* (tipo **Externo**, en producción).
2. *Credenciales → Crear ID de cliente OAuth → Aplicación web*.
   - Orígenes autorizados: `https://su-dominio.com`
   - URI de redirección: `https://su-dominio.com/auth/google/callback`
3. Copie `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` al `.env`.
4. En `TEACHER_EMAILS` ponga su correo docente. Si es Gmail (no institucional), active `ALLOW_EXTERNAL_TEACHER_LOGIN=true`.

### 3. Hostinger (hosting compartido / Business)
1. hPanel → *Avanzado → Configuración PHP*: **PHP 8.3** con extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo`, `openssl`.
2. Active **SSH** (*Avanzado → Acceso SSH*).
3. Opción A — despliegue automático con GitHub Actions (recomendado):
   - Cree en el repositorio los secretos `SSH_HOST`, `SSH_PORT` (65002), `SSH_USER`, `SSH_KEY` (clave privada cuya pública agregó en hPanel) y `DEPLOY_PATH` (p. ej. `/home/u123456789/domains/su-dominio.com/portal`), y la variable `DEPLOY_ENABLED=true`.
   - Cada `push` a `main` ejecuta pruebas, instala dependencias y sube el código.
4. Opción B — manual: `git clone` en `~/domains/su-dominio.com/portal` y `composer install --no-dev --optimize-autoloader`.
5. Apunte el dominio a la carpeta `public`:
   ```bash
   cd ~/domains/su-dominio.com && rm -rf public_html && ln -s portal/public public_html
   ```
   (Si no puede usar el enlace simbólico, suba el proyecto dentro de `public_html`: el `.htaccess` de la raíz redirige a `public/`.)
6. Primera vez, en el servidor:
   ```bash
   cd ~/domains/su-dominio.com/portal
   cp .env.example .env && nano .env          # APP_URL, DB_*, GOOGLE_*, TEACHER_EMAILS
   php artisan key:generate
   php artisan migrate --force
   php artisan portal:docente su-correo@unicartagena.edu.co "Su Nombre"   # contraseña de respaldo
   php artisan optimize
   chmod -R 775 storage bootstrap/cache
   ```
7. Active SSL gratuito en hPanel y verifique `APP_URL=https://…` y `SESSION_SECURE_COOKIE=true`.

## Uso rápido

1. **Crear asignatura** → código, nombre, programa, periodo y grupo.
2. **Estudiantes → Cargar lista**: exporte la lista del sistema académico a CSV con columnas `codigo;nombre;correo` (descargue la plantilla) o péguela. Solo se aceptan correos `@unicartagena.edu.co`.
3. **Material**: suba archivos, enlaces o notas por unidad/semana.
4. **Evaluaciones**: cree la evaluación, agregue preguntas, revise la *vista previa* y márquela como **publicada**.
5. Formato de *completar frases*: `El patrón [[MVC|Modelo Vista Controlador]] separa la [[vista]] del modelo.` (use `|` para respuestas alternativas; se ignoran mayúsculas y tildes).
6. **Resultados**: califique respuestas libres, revise el análisis por pregunta y descargue las notas.

## Seguridad

- Acceso por rol (docente/estudiante) y por asignatura en cada ruta; el docente solo gestiona sus asignaturas.
- Archivos de material en disco privado, entregados tras validar permisos.
- Tiempo controlado en el servidor (90 s de gracia por latencia); respuestas posteriores al límite se rechazan.
- La clave de respuestas no se muestra antes del cierre de la evaluación.
- Protección CSRF, limitación de intentos de inicio de sesión, cookies seguras en producción.

---
Autor: Carlos Andrés Cuesta Yepes — Universidad de Cartagena.
