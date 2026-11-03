# Página Web de la Carrera de Administración de Empresas (FCEE – UAGRM)

Proyecto de la materia **Tecnología Web (INF513-SA)** – Grupo 17-B.
Portal institucional con sitio público y panel de administración, desarrollado con
**Laravel 13 + Inertia 3 + Vue 3 (TypeScript) + Tailwind CSS 4 + MySQL 8**.

## Casos de uso implementados

| CU | Nombre | Dónde está |
|----|--------|------------|
| CU1 | Gestión de Usuarios (roles y permisos) | Panel › Usuarios y seguridad |
| CU2 | Gestión de Portal Web y Bitácora de Auditoría | Panel › Portal web (banners, destacados, enlaces, contacto y redes, bitácora) |
| CU3 | Información Académica, Malla Curricular Interactiva y Plantel Docente | Panel › Académico · Sitio › La Carrera / Académica |
| CU4 | Investigaciones, Revista INNOVAR y Repositorio Documental | Panel › Investigación · Sitio › Investigación / INNOVAR |
| CU5 | Vinculación, Empresa Junior INNOVA y Seguimiento a Graduados (Alumni) | Panel › Vinculación · Sitio › Vinculación / Alumni |
| CU6 | Noticias, Comunicados, Eventos Académicos y Galería Multimedia | Panel › Difusión · Sitio › Noticias |
| CU7 | Consultas mediante Formulario de Contacto y Búsqueda Interna Global | Panel › Consultas · Sitio › Contacto / Buscador |
| CU8 | Reportes y Estadísticas | Panel › Reportes y estadísticas |

Además: **contador de visitas en cada página** (pie de página), **tema claro y oscuro**
(botón con luna/sol) y **diseño sin bordes redondeados** en todos los componentes.

## Requisitos

- PHP 8.3 o superior con extensiones `pdo_mysql`, `gd`, `fileinfo`, `mbstring`
- Composer 2
- Node.js 20 o superior
- MySQL 8 (o MariaDB 10.6+)

## Instalación

```bash
composer install
npm install
cp .env.example .env          # luego edite DB_USERNAME / DB_PASSWORD
php artisan key:generate
```

Cree la base de datos en MySQL:

```sql
CREATE DATABASE carrera_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cree las tablas, los datos iniciales y el enlace a los archivos subidos:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Abra http://localhost:8000 (sitio público) y http://localhost:8000/admin (panel).

> El script SQL equivalente a las migraciones está en `database/sql/esquema_mysql.sql`.

## Usuarios de prueba

Todos usan la contraseña `password`.

| Correo | Rol |
|--------|-----|
| admin@carrera.test | Administrador (acceso total) |
| noticias@carrera.test | Responsable de Noticias |
| revista@carrera.test | Encargado Revista INNOVAR |
| graduados@carrera.test | Administrador de Graduados |
| secretaria@carrera.test | Secretaría Académica |
| graduado@carrera.test | Graduado (solo su ficha en el Programa Alumni) |

## Cómo funciona

- **Panel genérico:** cada módulo es un controlador en `app/Http/Controllers/Admin` que extiende
  `CrudController` y solo declara sus campos, columnas, filtros y reglas. Las vistas
  `resources/js/pages/admin/crud/Index.vue` y `Form.vue` dibujan el listado y el formulario.
- **Permisos (RF-18):** tablas `roles`, `permisos` y `rol_permiso`; middleware `permiso:` en `routes/admin.php`.
- **Menú dinámico:** tabla `menu_items`; cada opción se muestra según los permisos del rol.
- **Bitácora (RF-19):** el trait `App\Models\Concerns\RegistraBitacora` guarda crear, editar,
  publicar, archivar y eliminar; los inicios y cierres de sesión se registran en `AppServiceProvider`.
- **Contador de visitas:** el middleware `ContarVisita` suma una visita por página en
  `contador_visitas` (total) y `visitas_diarias` (por día, para los reportes).
- **Formularios públicos:** campo trampa oculto, tiempo mínimo de llenado, pregunta de verificación
  (contacto) y límite de envíos por minuto. La consulta se guarda y se notifica por correo.
- **Contenido editable:** las páginas institucionales y académicas se escriben en Markdown desde el panel.

## Datos de ejemplo

Los seeders cargan textos, imágenes y PDF **de ejemplo** para que el sitio se vea completo.
Reemplácelos desde el panel con la información oficial de la Carrera (misión, visión, docentes,
malla curricular, contactos, convenios, etc.).

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria (no tocan la base MySQL).

## Publicar en un subdirectorio

Configure `APP_URL` con la ruta completa (por ejemplo `https://servidor/inf513/grupo17sa/proyecto/public`).
Los enlaces del frontend usan esa base automáticamente.
# company
