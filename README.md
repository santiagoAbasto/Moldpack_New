<div align="center">

<img src="public/assets/figma/exact/logo-header.png" alt="Moldpack" width="180">

# Moldpack — Sitio web, CMS y Zona Privada de Clientes

Plataforma web de **Moldpack**, fabricante argentino de packaging gastronómico.
Sitio público editorial, panel de administración y portal B2B de clientes en una sola aplicación Laravel.

![Laravel](https://img.shields.io/badge/Laravel-13.26-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![React](https://img.shields.io/badge/React-19.2-61DAFB?logo=react&logoColor=black)
![Inertia](https://img.shields.io/badge/Inertia-3-9553E9)
![Vite](https://img.shields.io/badge/Vite-8.2-646CFF?logo=vite&logoColor=white)
![Tests](https://img.shields.io/badge/tests-80%20passing-2ea44f)

</div>

---

## Índice

- [Descripción](#descripción)
- [Funcionalidades](#funcionalidades)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Requisitos](#requisitos)
- [Instalación local](#instalación-local)
- [Variables de entorno](#variables-de-entorno)
- [Scripts disponibles](#scripts-disponibles)
- [Testing](#testing)
- [Seguridad](#seguridad)
- [Despliegue a producción](#despliegue-a-producción)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Licencia](#licencia)

---

## Descripción

Moldpack necesita presentarse como fabricante confiable, facilitar que cada visita encuentre productos y catálogos, y convertir esas visitas en consultas comerciales. Esta aplicación resuelve tres frentes:

| Módulo | Público | Tecnología de UI |
|---|---|---|
| **Sitio público** | Visitantes, distribuidores | Blade + CSS propio (fiel al diseño Figma) |
| **CMS / Panel admin** | Equipo de Moldpack | React 19 + Inertia 3 + TipTap |
| **Zona privada** | Clientes aprobados | Blade, integrada al layout público |

## Funcionalidades

### Sitio público
- Home con sliders (imagen, video MP4/WebM o YouTube), categorías, productos destacados, novedades y CTA de catálogo.
- Catálogo de productos con familias, filtros, detalle, presentaciones y productos relacionados (automáticos o manuales).
- Páginas **Nosotros**, **Calidad**, **Catálogo** (PDF descargable), **Novedades** y **Dónde comprar** (mapa Leaflet + geocodificación).
- **Formulario de contacto** con notificación al equipo comercial y confirmación al visitante.
- **Newsletter** con alta desde el footer y baja con enlace tokenizado.
- **IA Moldpack**: buscador semántico liviano sobre todo el contenido del CMS.
- SEO por página: title, description, canonical, Open Graph, `noindex` y JSON-LD de organización.

### Panel de administración (`/admin`)
- Edición de páginas, secciones, elementos y medios, con editor enriquecido.
- Gestión de contacto, redes sociales, tiendas, calidad y newsletter.
- Bandeja de **consultas de contacto** y envío de **campañas de newsletter** en cola.
- Gestión de **clientes**, **pedidos**, **facturas** y **pagos informados**, con exportación CSV.
- Gestión de usuarios administradores.

### Zona privada de clientes (`/area-clientes`)
- Registro con aprobación manual y login independiente del admin (guard `cliente`).
- Catálogo con precios y descuento por cliente, carrito y checkout.
- Seguimiento de pedidos, facturas descargables, informe de pagos con comprobante y estado de cuenta.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13.26, PHP 8.5 |
| Frontend admin | React 19.2, Inertia.js 3.7, TipTap 3.30, Lucide |
| Frontend público | Blade, CSS nativo (Lightning CSS), Leaflet |
| Build | Vite 8.2 (Rolldown) con separación de chunks |
| Base de datos | MySQL (producción) / SQLite (desarrollo y tests) |
| Colas | Driver `database` (envíos de newsletter y mails de contacto) |
| Testing | PHPUnit 13 |

## Arquitectura

```text
                ┌──────────────────────────── Navegador ────────────────────────────┐
                │  Sitio público (Blade)   Zona privada (Blade)   Admin (React/Inertia) │
                └──────────────┬───────────────────┬──────────────────────┬─────────┘
                               │                   │                      │
             web middleware: EncryptCookies · Session · CSRF · SecurityHeaders
                               │                   │                      │
                     SiteController       ClientAreaController     Admin\* Controllers
                     ContactInquiry…      (guard: cliente)         (guard: web + is_admin)
                     NewsletterController                           auth.session
                               │                   │                      │
                               └────────── Eloquent (Page · Section · ContentItem · Media
                                           Cliente · ClientOrder · ClientInvoice · …) ──┘
                                                         │
                                  MySQL · storage/app/public · cola "mail" → SMTP
```

El contenido se modela como **Página → Secciones → Elementos → Medios**. El header y el footer son fijos. Cada sección tiene un `type` (`hero`, `products`, `news`, `about_page`, `catalog_page`, …) que define su plantilla.

## Requisitos

- PHP **8.5** con extensiones `pdo_mysql`/`pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd`
- Composer **2.9+**
- Node.js **26.7.0** (fijado en `.nvmrc` / `.node-version`) y npm **11.12+**
- MySQL 8 en producción (SQLite alcanza para desarrollo)

## Instalación local

```bash
git clone https://github.com/santiagoAbasto/Moldpack_New.git
cd Moldpack_New

nvm use
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite      # si usás SQLite (DB_CONNECTION=sqlite)
php artisan migrate --seed
php artisan storage:link

npm run build                       # o `npm run dev` para HMR
php artisan serve --port=8001
```

- Sitio: <http://127.0.0.1:8001>
- CMS: <http://127.0.0.1:8001/admin>

> [!WARNING]
> El seeder crea un administrador inicial (`admin@moldpack.com.ar`) y un cliente demo **solo para desarrollo**.
> **Nunca ejecutes `db:seed` en producción** y cambiá cualquier credencial inicial antes de publicar.

## Variables de entorno

Las claves completas están documentadas en [`.env.example`](.env.example). Estas son las más relevantes:

| Variable | Descripción | Producción |
|---|---|---|
| `APP_ENV` | Entorno | `production` |
| `APP_DEBUG` | Muestra trazas de error | **`false`** |
| `APP_KEY` | Clave de cifrado (sesiones, cookies, datos cifrados) | Generada y **nunca** versionada |
| `APP_URL` | URL canónica | `https://moldpack.com.ar` |
| `SESSION_SECURE_COOKIE` | Cookie de sesión solo por HTTPS | **`true`** |
| `SESSION_LIFETIME` | Minutos de inactividad | `120` |
| `QUEUE_CONNECTION` | Driver de colas | `database` |
| `MAIL_*` | Credenciales SMTP | Configuradas en el servidor |
| `CONTACT_MAIL_TO` | Destinatario de las consultas | `ventas@moldpack.com.ar` |

> [!CAUTION]
> Rotar `APP_KEY` invalida sesiones, cookies "recordarme" y cualquier dato cifrado con `Crypt`. Planificá la rotación con anticipación.

## Scripts disponibles

| Comando | Acción |
|---|---|
| `npm run dev` | Servidor Vite con HMR |
| `npm run build` | Build de producción en `public/build` |
| `php artisan test` | Suite completa de tests |
| `php artisan queue:work --queue=mail,default` | Worker de colas (newsletter y mails) |
| `php artisan catalog:import-legacy {dump} --dry-run` | Importa el catálogo desde un respaldo SQL legacy |
| `php artisan commerce:import-legacy {dump} --dry-run` | Importa clientes, pedidos y facturas legacy |
| `php artisan commerce:sync-client-details {dump} --dry-run` | Sincroniza datos de perfil de clientes legacy |

## Testing

```bash
php artisan test
```

La suite actual tiene **80 tests y 574 aserciones**, con esta cobertura:

| Suite | Cubre |
|---|---|
| `CmsTest` / `ClientPortalTest` | Contenido público, CMS, catálogo, zona privada, carrito, pagos y facturas |
| `Security/ContactFormSecurityTest` | Envío legítimo, validación, honeypot, time-trap, rate limit con recuperación, XSS, SQLi, header injection y anti-relay |
| `Security/NewsletterSecurityTest` | Alta, duplicados, baja, validación, honeypot y rate limit |
| `Security/CsrfProtectionTest` | Middleware CSRF real: con token pasa, sin token o con Origin falso devuelve 419 |
| `Security/SessionSecurityTest` | Flags de cookie, regeneración de sesión, logout, tampering, remember token y exposición de credenciales |
| `Security/AccessControlAndInputTest` | IDOR, escalada de privilegios, mass assignment, uploads, CSV injection, headers y errores |

## Seguridad

Resumen de los controles implementados. El detalle está en [`SECURITY.md`](SECURITY.md).

- **Formularios públicos**: CSRF, validación server-side con límites de tamaño, honeypot, time-trap tolerante y limitadores independientes por endpoint (un usuario que usa el buscador no queda bloqueado para enviar el contacto).
- **Autenticación**: regeneración de sesión en login, invalidación en logout, rate limit por cuenta + IP, mensajes que no revelan si un usuario existe, y `auth.session` en el admin (un cambio de contraseña cierra las otras sesiones).
- **Sesiones y cookies**: `HttpOnly`, `SameSite=Strict`, sesión cifrada, `Secure` por entorno y cookies cifradas sin excepciones.
- **Uploads**: allowlist de MIME real (finfo), nombres aleatorios generados por el servidor y rechazo de SVG con contenido activo.
- **Cabeceras**: CSP estricta en el admin; `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y HSTS en el sitio público.
- **Datos**: Eloquent/Query Builder con bindings (sin SQL crudo con input del usuario) y exportaciones CSV protegidas contra inyección de fórmulas.

Para reportar una vulnerabilidad, seguí las indicaciones de [`SECURITY.md`](SECURITY.md).

## Despliegue a producción

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. Configurar `.env` del servidor (`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`).
4. `php artisan migrate --force`
5. `php artisan storage:link`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Mantener un worker: `php artisan queue:work --queue=mail,default --tries=3`
8. El document root del servidor web debe apuntar a **`public/`**, nunca a la raíz del proyecto.
9. Configurar el servidor web para **no ejecutar PHP** dentro de `public/storage`.

## Estructura del proyecto

```text
app/
├── Console/Commands/      Importadores del sistema legacy
├── Http/
│   ├── Controllers/       Sitio público, zona privada y Admin/*
│   └── Middleware/        Admin, cliente y cabeceras de seguridad
├── Jobs/                  Envío de newsletter en cola
├── Mail/                  Mails de contacto y campañas
├── Models/                Page, Section, ContentItem, Media, Cliente, ClientOrder, …
├── Services/              Taxonomía y relaciones de productos
└── Support/               Utilidades (validación segura de SVG)
database/migrations/       Esquema y migraciones de contenido
resources/
├── css/                   Estilos públicos, admin y zona privada
├── js/admin.jsx           Panel React + Inertia
└── views/                 Plantillas Blade públicas, zona privada y emails
routes/web.php             Rutas públicas, zona privada y admin
tests/Feature/             Tests funcionales y de seguridad
```

## Licencia

Software **propietario** de Moldpack. Todos los derechos reservados.
No se permite su uso, copia ni distribución sin autorización expresa.
