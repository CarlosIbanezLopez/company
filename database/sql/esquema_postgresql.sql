-- =============================================================================
--  Portal de la Carrera de Administración de Empresas - FCEE - UAGRM
--  INF513 Tecnología Web - Proyecto 2 - Grupo 23
--
--  CAPA 3 (PERSISTENCIA): esquema completo de la base de datos en PostgreSQL.
--
--  Equivale a ejecutar "php artisan migrate" (database/migrations) e incluye los
--  datos mínimos de seguridad: permisos de la matriz de acceso, roles, menú del
--  panel y la cuenta del administrador. Las tablas de contadores se mantienen con
--  triggers: la aplicación solo inserta en visitas, inicios_sesion y accesos.
--
--  Uso (sobre una base de datos vacía):
--    psql -h db.tecnoweb.org.bo -p 5432 -U grupo23sa -d db_grupo23sa \
--         -v ON_ERROR_STOP=1 -f database/sql/esquema_postgresql.sql
--
--  Requiere PostgreSQL 9.5 o superior (INSERT ... ON CONFLICT).
--  Cuenta inicial: admin@carrera.test / password  (cambie la contraseña al ingresar).
-- =============================================================================

SET client_encoding = 'UTF8';
SET client_min_messages = warning;

-- Esquema de trabajo: debe coincidir con DB_SCHEMA del archivo .env (por defecto public).
SET search_path TO public;

BEGIN;

-- =============================================================================
-- 1. TABLAS INTERNAS DE LARAVEL: migraciones, caché y colas de trabajo
-- =============================================================================

-- Migraciones ejecutadas (al final del script se registran todas como aplicadas).
CREATE TABLE migrations (
    id        SERIAL       PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch     INTEGER      NOT NULL
);

-- Caché de la aplicación (CACHE_STORE=database) y sus bloqueos atómicos.
CREATE TABLE cache (
    key        VARCHAR(255) PRIMARY KEY,
    value      TEXT         NOT NULL,
    expiration BIGINT       NOT NULL
);
CREATE INDEX cache_expiration_index ON cache (expiration);

CREATE TABLE cache_locks (
    key        VARCHAR(255) PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration BIGINT       NOT NULL
);
CREATE INDEX cache_locks_expiration_index ON cache_locks (expiration);

-- Colas de trabajo (QUEUE_CONNECTION=database): envío de correos y tareas diferidas.
CREATE TABLE jobs (
    id           BIGSERIAL    PRIMARY KEY,
    queue        VARCHAR(255) NOT NULL,
    payload      TEXT         NOT NULL,
    attempts     SMALLINT     NOT NULL,
    reserved_at  INTEGER,
    available_at INTEGER      NOT NULL,
    created_at   INTEGER      NOT NULL
);
CREATE INDEX jobs_queue_index ON jobs (queue);

CREATE TABLE job_batches (
    id             VARCHAR(255) PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    total_jobs     INTEGER      NOT NULL,
    pending_jobs   INTEGER      NOT NULL,
    failed_jobs    INTEGER      NOT NULL,
    failed_job_ids TEXT         NOT NULL,
    options        TEXT,
    cancelled_at   INTEGER,
    created_at     INTEGER      NOT NULL,
    finished_at    INTEGER
);

CREATE TABLE failed_jobs (
    id         BIGSERIAL    PRIMARY KEY,
    uuid       VARCHAR(255) NOT NULL,
    connection VARCHAR(255) NOT NULL,
    queue      VARCHAR(255) NOT NULL,
    payload    TEXT         NOT NULL,
    exception  TEXT         NOT NULL,
    failed_at  TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid)
);
CREATE INDEX failed_jobs_connection_queue_failed_at_index ON failed_jobs (connection, queue, failed_at);

-- =============================================================================
-- 2. CU1 - USUARIOS, ROLES Y MATRIZ DE CONTROL DE ACCESO (RBAC)
-- =============================================================================

-- Roles del sistema. acceso_panel indica si el rol puede ingresar al panel de administración.
CREATE TABLE roles (
    id           BIGSERIAL    PRIMARY KEY,
    nombre       VARCHAR(60)  NOT NULL,
    descripcion  VARCHAR(255),
    acceso_panel BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP(0),
    updated_at   TIMESTAMP(0),
    CONSTRAINT roles_nombre_unique UNIQUE (nombre)
);

-- Permisos de la matriz de acceso: nombre = '{recurso}.{accion}' (ver | crear | editar | eliminar).
-- modulo es el grupo visible en el panel (p. ej. 'Difusión').
CREATE TABLE permisos (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    recurso     VARCHAR(60),
    accion      VARCHAR(20),
    descripcion VARCHAR(255),
    modulo      VARCHAR(60)  NOT NULL,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT permisos_nombre_unique UNIQUE (nombre),
    CONSTRAINT permisos_recurso_accion_unique UNIQUE (recurso, accion)
);

-- Permisos asignados a cada rol (relación muchos a muchos).
CREATE TABLE rol_permiso (
    rol_id     BIGINT NOT NULL,
    permiso_id BIGINT NOT NULL,
    CONSTRAINT rol_permiso_pkey PRIMARY KEY (rol_id, permiso_id),
    CONSTRAINT rol_permiso_rol_id_foreign FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT rol_permiso_permiso_id_foreign FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE
);
-- PostgreSQL no crea índices para las claves foráneas (MySQL sí): se declaran explícitamente.
CREATE INDEX rol_permiso_permiso_id_index ON rol_permiso (permiso_id);

-- Cuentas de usuario. preferencias = {tema, modo, fuente, contraste} de la interfaz.
-- ultimo_acceso e intentos_fallidos los actualiza el trigger trg_inicios_sesion_usuario.
CREATE TABLE users (
    id                BIGSERIAL    PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    telefono          VARCHAR(20),
    rol_id            BIGINT,
    activo            BOOLEAN      NOT NULL DEFAULT TRUE,
    preferencias      JSON,
    ultimo_acceso     TIMESTAMP(0),
    intentos_fallidos INTEGER      NOT NULL DEFAULT 0,
    email_verified_at TIMESTAMP(0),
    password          VARCHAR(255) NOT NULL,
    remember_token    VARCHAR(100),
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0),
    CONSTRAINT users_email_unique UNIQUE (email),
    CONSTRAINT users_rol_id_foreign FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE SET NULL
);
CREATE INDEX users_rol_id_index ON users (rol_id);

-- Enlaces de restablecimiento de contraseña.
CREATE TABLE password_reset_tokens (
    email      VARCHAR(255) PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0)
);

-- Tokens Bearer de Laravel Sanctum con los que la SPA se autentica ante la API REST.
CREATE TABLE personal_access_tokens (
    id             BIGSERIAL    PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id   BIGINT       NOT NULL,
    name           TEXT         NOT NULL,
    token          VARCHAR(64)  NOT NULL,
    abilities      TEXT,
    last_used_at   TIMESTAMP(0),
    expires_at     TIMESTAMP(0),
    created_at     TIMESTAMP(0),
    updated_at     TIMESTAMP(0),
    CONSTRAINT personal_access_tokens_token_unique UNIQUE (token)
);
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE INDEX personal_access_tokens_expires_at_index ON personal_access_tokens (expires_at);

-- Sesiones web de Laravel (SESSION_DRIVER=database).
CREATE TABLE sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT,
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    payload       TEXT         NOT NULL,
    last_activity INTEGER      NOT NULL
);
CREATE INDEX sessions_user_id_index ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);

-- =============================================================================
-- 3. CU2 - PORTAL WEB, MENÚ DINÁMICO Y BITÁCORA DE AUDITORÍA
-- =============================================================================

-- Acciones realizadas por los usuarios (crear, editar, publicar, eliminar, inicio de sesión...).
CREATE TABLE bitacora (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     BIGINT,
    accion      VARCHAR(30)  NOT NULL,
    modulo      VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    registro_id BIGINT,
    ip          VARCHAR(45),
    agente      VARCHAR(255),
    created_at  TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT bitacora_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX bitacora_user_id_index ON bitacora (user_id);
CREATE INDEX bitacora_created_at_index ON bitacora (created_at);

-- Menú del panel: ruta = ruta de la SPA (NULL en los grupos), permiso = permiso requerido,
-- icono = nombre del ícono lucide. Un ítem con padre_id pertenece a ese grupo.
CREATE TABLE menu_items (
    id         BIGSERIAL    PRIMARY KEY,
    etiqueta   VARCHAR(80)  NOT NULL,
    ruta       VARCHAR(120),
    icono      VARCHAR(40),
    permiso    VARCHAR(80),
    padre_id   BIGINT,
    orden      INTEGER      NOT NULL DEFAULT 0,
    activo     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT menu_items_padre_id_foreign FOREIGN KEY (padre_id) REFERENCES menu_items (id) ON DELETE SET NULL
);
CREATE INDEX menu_items_padre_id_index ON menu_items (padre_id);

-- Carrusel de la página principal.
CREATE TABLE banners (
    id          BIGSERIAL    PRIMARY KEY,
    titulo      VARCHAR(150) NOT NULL,
    subtitulo   VARCHAR(255),
    imagen      VARCHAR(255),
    enlace      VARCHAR(255),
    texto_boton VARCHAR(40),
    orden       INTEGER      NOT NULL DEFAULT 0,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Cifras e información destacada de la página principal.
CREATE TABLE destacados (
    id          BIGSERIAL    PRIMARY KEY,
    titulo      VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255),
    valor       VARCHAR(40),
    enlace      VARCHAR(255),
    orden       INTEGER      NOT NULL DEFAULT 0,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Enlaces a plataformas institucionales.
CREATE TABLE enlaces_plataforma (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    url         VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255),
    orden       INTEGER      NOT NULL DEFAULT 0,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Parámetros clave/valor del portal (presentación, contacto, redes sociales).
CREATE TABLE configuraciones (
    id         BIGSERIAL   PRIMARY KEY,
    clave      VARCHAR(80) NOT NULL,
    valor      TEXT,
    grupo      VARCHAR(40) NOT NULL DEFAULT 'general',
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT configuraciones_clave_unique UNIQUE (clave)
);

-- Contador histórico de visitas por página de la SPA (lo mantiene trg_visitas_contador).
CREATE TABLE contador_visitas (
    id            BIGSERIAL    PRIMARY KEY,
    ruta          VARCHAR(191) NOT NULL,
    total         BIGINT       NOT NULL DEFAULT 0,
    ultima_visita TIMESTAMP(0),
    created_at    TIMESTAMP(0),
    updated_at    TIMESTAMP(0),
    CONSTRAINT contador_visitas_ruta_unique UNIQUE (ruta)
);

-- Visitas por día y página (lo mantiene trg_visitas_contador).
CREATE TABLE visitas_diarias (
    id    BIGSERIAL    PRIMARY KEY,
    fecha DATE         NOT NULL,
    ruta  VARCHAR(191) NOT NULL,
    total INTEGER      NOT NULL DEFAULT 0,
    CONSTRAINT visitas_diarias_fecha_ruta_unique UNIQUE (fecha, ruta)
);

-- =============================================================================
-- 4. CU3 - INFORMACIÓN ACADÉMICA, MALLA CURRICULAR Y PLANTEL DOCENTE
-- =============================================================================

-- Páginas de contenido (institucional, académico, revista, innova, graduados).
CREATE TABLE paginas (
    id         BIGSERIAL    PRIMARY KEY,
    seccion    VARCHAR(30)  NOT NULL,
    slug       VARCHAR(120) NOT NULL,
    titulo     VARCHAR(150) NOT NULL,
    resumen    VARCHAR(255),
    contenido  TEXT,
    imagen     VARCHAR(255),
    orden      INTEGER      NOT NULL DEFAULT 0,
    publicado  BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT paginas_slug_unique UNIQUE (slug)
);
CREATE INDEX paginas_seccion_index ON paginas (seccion);

-- Autoridades, docentes y personal administrativo.
CREATE TABLE docentes (
    id                BIGSERIAL    PRIMARY KEY,
    nombre            VARCHAR(150) NOT NULL,
    tipo              VARCHAR(20)  NOT NULL DEFAULT 'docente',
    cargo             VARCHAR(120),
    formacion         VARCHAR(255),
    especialidad      VARCHAR(150),
    materias          VARCHAR(255),
    correo            VARCHAR(150),
    foto              VARCHAR(255),
    mostrar_correo    BOOLEAN      NOT NULL DEFAULT FALSE,
    mostrar_formacion BOOLEAN      NOT NULL DEFAULT TRUE,
    visible           BOOLEAN      NOT NULL DEFAULT TRUE,
    orden             INTEGER      NOT NULL DEFAULT 0,
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0)
);

-- Materias de la malla curricular.
CREATE TABLE materias (
    id              BIGSERIAL    PRIMARY KEY,
    sigla           VARCHAR(20)  NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    semestre        SMALLINT     NOT NULL,
    creditos        SMALLINT     NOT NULL DEFAULT 0,
    horas_teoricas  SMALLINT     NOT NULL DEFAULT 0,
    horas_practicas SMALLINT     NOT NULL DEFAULT 0,
    tipo            VARCHAR(20)  NOT NULL DEFAULT 'obligatoria',
    descripcion     TEXT,
    created_at      TIMESTAMP(0),
    updated_at      TIMESTAMP(0),
    CONSTRAINT materias_sigla_unique UNIQUE (sigla)
);

-- Prerrequisitos entre materias (relación reflexiva muchos a muchos).
CREATE TABLE materia_prerequisito (
    materia_id      BIGINT NOT NULL,
    prerequisito_id BIGINT NOT NULL,
    CONSTRAINT materia_prerequisito_pkey PRIMARY KEY (materia_id, prerequisito_id),
    CONSTRAINT materia_prerequisito_materia_id_foreign FOREIGN KEY (materia_id) REFERENCES materias (id) ON DELETE CASCADE,
    CONSTRAINT materia_prerequisito_prerequisito_id_foreign FOREIGN KEY (prerequisito_id) REFERENCES materias (id) ON DELETE CASCADE
);
CREATE INDEX materia_prerequisito_prerequisito_id_index ON materia_prerequisito (prerequisito_id);

-- Calendario académico.
CREATE TABLE actividades_calendario (
    id           BIGSERIAL    PRIMARY KEY,
    titulo       VARCHAR(150) NOT NULL,
    descripcion  VARCHAR(255),
    fecha_inicio DATE         NOT NULL,
    fecha_fin    DATE,
    tipo         VARCHAR(30)  NOT NULL DEFAULT 'academico',
    gestion      VARCHAR(20)  NOT NULL,
    created_at   TIMESTAMP(0),
    updated_at   TIMESTAMP(0)
);

-- Preguntas frecuentes.
CREATE TABLE preguntas_frecuentes (
    id         BIGSERIAL    PRIMARY KEY,
    pregunta   VARCHAR(255) NOT NULL,
    respuesta  TEXT         NOT NULL,
    categoria  VARCHAR(60),
    orden      INTEGER      NOT NULL DEFAULT 0,
    activo     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

-- =============================================================================
-- 5. CU4 - INVESTIGACIÓN, REVISTA INNOVAR Y BIBLIOTECA DIGITAL
-- =============================================================================

CREATE TABLE categorias_documento (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT categorias_documento_slug_unique UNIQUE (slug)
);

-- Documentos descargables (reglamentos, formularios, convocatorias...).
CREATE TABLE documentos (
    id                     BIGSERIAL    PRIMARY KEY,
    categoria_documento_id BIGINT       NOT NULL,
    titulo                 VARCHAR(200) NOT NULL,
    descripcion            VARCHAR(500),
    archivo                VARCHAR(255),
    fecha_publicacion      DATE,
    descargas              INTEGER      NOT NULL DEFAULT 0,
    publicado              BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at             TIMESTAMP(0),
    updated_at             TIMESTAMP(0),
    CONSTRAINT documentos_categoria_documento_id_foreign FOREIGN KEY (categoria_documento_id) REFERENCES categorias_documento (id) ON DELETE RESTRICT
);
CREATE INDEX documentos_categoria_documento_id_index ON documentos (categoria_documento_id);

CREATE TABLE lineas_investigacion (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Proyectos, PIA, semilleros y producción académica.
CREATE TABLE proyectos_investigacion (
    id                     BIGSERIAL    PRIMARY KEY,
    linea_investigacion_id BIGINT,
    tipo                   VARCHAR(20)  NOT NULL DEFAULT 'proyecto',
    titulo                 VARCHAR(200) NOT NULL,
    slug                   VARCHAR(220) NOT NULL,
    descripcion            TEXT,
    responsables           VARCHAR(255),
    fecha_inicio           DATE,
    fecha_fin              DATE,
    estado                 VARCHAR(20)  NOT NULL DEFAULT 'en_curso',
    resultados             TEXT,
    enlace_publicacion     VARCHAR(255),
    enlace_repositorio     VARCHAR(255),
    imagen                 VARCHAR(255),
    publicado              BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at             TIMESTAMP(0),
    updated_at             TIMESTAMP(0),
    CONSTRAINT proyectos_investigacion_slug_unique UNIQUE (slug),
    CONSTRAINT proyectos_investigacion_linea_investigacion_id_foreign FOREIGN KEY (linea_investigacion_id) REFERENCES lineas_investigacion (id) ON DELETE SET NULL
);
CREATE INDEX proyectos_investigacion_linea_investigacion_id_index ON proyectos_investigacion (linea_investigacion_id);
CREATE INDEX proyectos_investigacion_tipo_index ON proyectos_investigacion (tipo);

CREATE TABLE revista_ediciones (
    id                BIGSERIAL    PRIMARY KEY,
    numero            INTEGER      NOT NULL,
    titulo            VARCHAR(200) NOT NULL,
    fecha_publicacion DATE,
    descripcion       TEXT,
    portada           VARCHAR(255),
    archivo_pdf       VARCHAR(255),
    publicado         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0),
    CONSTRAINT revista_ediciones_numero_unique UNIQUE (numero)
);

CREATE TABLE revista_articulos (
    id                 BIGSERIAL    PRIMARY KEY,
    revista_edicion_id BIGINT       NOT NULL,
    titulo             VARCHAR(255) NOT NULL,
    autores            VARCHAR(255) NOT NULL,
    resumen            TEXT,
    palabras_clave     VARCHAR(255),
    archivo_pdf        VARCHAR(255),
    orden              INTEGER      NOT NULL DEFAULT 0,
    created_at         TIMESTAMP(0),
    updated_at         TIMESTAMP(0),
    CONSTRAINT revista_articulos_revista_edicion_id_foreign FOREIGN KEY (revista_edicion_id) REFERENCES revista_ediciones (id) ON DELETE CASCADE
);
CREATE INDEX revista_articulos_revista_edicion_id_index ON revista_articulos (revista_edicion_id);

-- =============================================================================
-- 6. CU5 - VINCULACIÓN, EMPRESA JUNIOR INNOVA Y SEGUIMIENTO A GRADUADOS
-- =============================================================================

CREATE TABLE convenios (
    id           BIGSERIAL    PRIMARY KEY,
    institucion  VARCHAR(200) NOT NULL,
    tipo         VARCHAR(30)  NOT NULL DEFAULT 'convenio',
    finalidad    TEXT,
    fecha_inicio DATE,
    fecha_fin    DATE,
    logo         VARCHAR(255),
    estado       VARCHAR(20)  NOT NULL DEFAULT 'vigente',
    publicado    BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP(0),
    updated_at   TIMESTAMP(0)
);

CREATE TABLE innova_servicios (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT,
    orden       INTEGER      NOT NULL DEFAULT 0,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

CREATE TABLE innova_integrantes (
    id         BIGSERIAL    PRIMARY KEY,
    nombre     VARCHAR(150) NOT NULL,
    cargo      VARCHAR(100),
    semestre   VARCHAR(30),
    foto       VARCHAR(255),
    orden      INTEGER      NOT NULL DEFAULT 0,
    activo     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE TABLE innova_proyectos (
    id          BIGSERIAL    PRIMARY KEY,
    titulo      VARCHAR(200) NOT NULL,
    cliente     VARCHAR(150),
    descripcion TEXT,
    fecha       DATE,
    imagen      VARCHAR(255),
    publicado   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Solicitudes de servicios de consultoría enviadas desde el sitio.
CREATE TABLE innova_solicitudes (
    id                 BIGSERIAL    PRIMARY KEY,
    innova_servicio_id BIGINT,
    empresa            VARCHAR(150) NOT NULL,
    contacto           VARCHAR(150) NOT NULL,
    correo             VARCHAR(150) NOT NULL,
    telefono           VARCHAR(30),
    mensaje            TEXT         NOT NULL,
    estado             VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    observacion        TEXT,
    created_at         TIMESTAMP(0),
    updated_at         TIMESTAMP(0),
    CONSTRAINT innova_solicitudes_innova_servicio_id_foreign FOREIGN KEY (innova_servicio_id) REFERENCES innova_servicios (id) ON DELETE SET NULL
);
CREATE INDEX innova_solicitudes_innova_servicio_id_index ON innova_solicitudes (innova_servicio_id);

-- Postulaciones de estudiantes para integrar INNOVA.
CREATE TABLE innova_postulaciones (
    id          BIGSERIAL    PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    registro    VARCHAR(20)  NOT NULL,
    correo      VARCHAR(150) NOT NULL,
    telefono    VARCHAR(30),
    semestre    VARCHAR(30),
    motivacion  TEXT         NOT NULL,
    estado      VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    observacion TEXT,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);

-- Ficha de seguimiento del graduado (user_id: cuenta con la que la actualiza, si tiene).
CREATE TABLE graduados (
    id                   BIGSERIAL    PRIMARY KEY,
    user_id              BIGINT,
    nombre               VARCHAR(150) NOT NULL,
    ci                   VARCHAR(20)  NOT NULL,
    registro             VARCHAR(20),
    anio_graduacion      SMALLINT     NOT NULL,
    modalidad_titulacion VARCHAR(60),
    situacion_laboral    VARCHAR(30)  NOT NULL,
    empresa              VARCHAR(150),
    cargo                VARCHAR(120),
    ciudad               VARCHAR(80),
    correo               VARCHAR(150) NOT NULL,
    telefono             VARCHAR(30),
    linkedin             VARCHAR(255),
    created_at           TIMESTAMP(0),
    updated_at           TIMESTAMP(0),
    CONSTRAINT graduados_user_id_unique UNIQUE (user_id),
    CONSTRAINT graduados_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);

-- =============================================================================
-- 7. CU6 - NOTICIAS, EVENTOS ACADÉMICOS Y GALERÍA MULTIMEDIA
-- =============================================================================

CREATE TABLE categorias_noticia (
    id         BIGSERIAL    PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT categorias_noticia_slug_unique UNIQUE (slug)
);

-- Noticias y comunicados (estado: borrador | publicado | archivado).
CREATE TABLE noticias (
    id                   BIGSERIAL    PRIMARY KEY,
    categoria_noticia_id BIGINT,
    user_id              BIGINT,
    titulo               VARCHAR(200) NOT NULL,
    slug                 VARCHAR(220) NOT NULL,
    resumen              VARCHAR(300),
    contenido            TEXT,
    imagen               VARCHAR(255),
    fecha_publicacion    DATE         NOT NULL,
    estado               VARCHAR(20)  NOT NULL DEFAULT 'publicado',
    destacado            BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at           TIMESTAMP(0),
    updated_at           TIMESTAMP(0),
    CONSTRAINT noticias_slug_unique UNIQUE (slug),
    CONSTRAINT noticias_categoria_noticia_id_foreign FOREIGN KEY (categoria_noticia_id) REFERENCES categorias_noticia (id) ON DELETE SET NULL,
    CONSTRAINT noticias_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX noticias_categoria_noticia_id_index ON noticias (categoria_noticia_id);
CREATE INDEX noticias_user_id_index ON noticias (user_id);

CREATE TABLE eventos (
    id                 BIGSERIAL    PRIMARY KEY,
    titulo             VARCHAR(200) NOT NULL,
    slug               VARCHAR(220) NOT NULL,
    tipo               VARCHAR(30)  NOT NULL DEFAULT 'conferencia',
    descripcion        TEXT,
    fecha              DATE         NOT NULL,
    hora_inicio        TIME(0),
    hora_fin           TIME(0),
    lugar              VARCHAR(200),
    afiche             VARCHAR(255),
    enlace_inscripcion VARCHAR(255),
    publicado          BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMP(0),
    updated_at         TIMESTAMP(0),
    CONSTRAINT eventos_slug_unique UNIQUE (slug)
);

CREATE TABLE albumes (
    id          BIGSERIAL    PRIMARY KEY,
    titulo      VARCHAR(200) NOT NULL,
    slug        VARCHAR(220) NOT NULL,
    descripcion VARCHAR(500),
    fecha       DATE,
    portada     VARCHAR(255),
    publicado   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT albumes_slug_unique UNIQUE (slug)
);

CREATE TABLE fotos (
    id          BIGSERIAL    PRIMARY KEY,
    album_id    BIGINT       NOT NULL,
    imagen      VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255),
    orden       INTEGER      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT fotos_album_id_foreign FOREIGN KEY (album_id) REFERENCES albumes (id) ON DELETE CASCADE
);
CREATE INDEX fotos_album_id_index ON fotos (album_id);

CREATE TABLE videos (
    id          BIGSERIAL    PRIMARY KEY,
    album_id    BIGINT,
    titulo      VARCHAR(200) NOT NULL,
    url         VARCHAR(255) NOT NULL,
    descripcion VARCHAR(500),
    publicado   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT videos_album_id_foreign FOREIGN KEY (album_id) REFERENCES albumes (id) ON DELETE SET NULL
);
CREATE INDEX videos_album_id_index ON videos (album_id);

-- =============================================================================
-- 8. CU7 - CONSULTAS DEL FORMULARIO DE CONTACTO
-- =============================================================================

CREATE TABLE categorias_consulta (
    id             BIGSERIAL    PRIMARY KEY,
    nombre         VARCHAR(80)  NOT NULL,
    correo_destino VARCHAR(150),
    created_at     TIMESTAMP(0),
    updated_at     TIMESTAMP(0)
);

-- Consultas recibidas (estado: nueva | en_revision | respondida | cerrada).
CREATE TABLE consultas (
    id                    BIGSERIAL    PRIMARY KEY,
    categoria_consulta_id BIGINT,
    nombre                VARCHAR(150) NOT NULL,
    correo                VARCHAR(150) NOT NULL,
    telefono              VARCHAR(30),
    mensaje               TEXT         NOT NULL,
    estado                VARCHAR(20)  NOT NULL DEFAULT 'nueva',
    respuesta             TEXT,
    ip                    VARCHAR(45),
    created_at            TIMESTAMP(0),
    updated_at            TIMESTAMP(0),
    CONSTRAINT consultas_categoria_consulta_id_foreign FOREIGN KEY (categoria_consulta_id) REFERENCES categorias_consulta (id) ON DELETE SET NULL
);
CREATE INDEX consultas_categoria_consulta_id_index ON consultas (categoria_consulta_id);

-- =============================================================================
-- 9. CU8 - ANALÍTICA: VISITAS, INICIOS DE SESIÓN, TRÁFICO DE LA API Y CONTADORES
-- =============================================================================

-- Registro crudo de páginas vistas en la SPA (POST /api/visitas).
CREATE TABLE visitas (
    id         BIGSERIAL    PRIMARY KEY,
    ruta       VARCHAR(191) NOT NULL,
    user_id    BIGINT,
    ip         VARCHAR(45),
    agente     VARCHAR(255),
    created_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT visitas_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX visitas_ruta_index ON visitas (ruta);
CREATE INDEX visitas_user_id_index ON visitas (user_id);
CREATE INDEX visitas_created_at_index ON visitas (created_at);

-- Bitácora de autenticación: cada intento de inicio de sesión (exitoso, fallido o bloqueado).
CREATE TABLE inicios_sesion (
    id         BIGSERIAL    PRIMARY KEY,
    user_id    BIGINT,
    email      VARCHAR(150) NOT NULL,
    exitoso    BOOLEAN      NOT NULL,
    motivo     VARCHAR(150),
    ip         VARCHAR(45),
    agente     VARCHAR(255),
    created_at TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT inicios_sesion_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX inicios_sesion_user_id_index ON inicios_sesion (user_id);
CREATE INDEX inicios_sesion_exitoso_index ON inicios_sesion (exitoso);
CREATE INDEX inicios_sesion_ip_index ON inicios_sesion (ip);
CREATE INDEX inicios_sesion_created_at_index ON inicios_sesion (created_at);

-- Tráfico de la API REST: una fila por solicitud (ruta = URI de la ruta, recurso = nombre de la ruta).
CREATE TABLE accesos (
    id          BIGSERIAL    PRIMARY KEY,
    user_id     BIGINT,
    ip          VARCHAR(45),
    metodo      VARCHAR(10)  NOT NULL,
    ruta        VARCHAR(191) NOT NULL,
    recurso     VARCHAR(150) NOT NULL,
    estado      SMALLINT     NOT NULL,
    duracion_ms INTEGER      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT accesos_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX accesos_user_id_index ON accesos (user_id);
CREATE INDEX accesos_ip_index ON accesos (ip);
CREATE INDEX accesos_recurso_index ON accesos (recurso);
CREATE INDEX accesos_created_at_index ON accesos (created_at);

-- Total de solicitudes por recurso y método HTTP (lo mantiene trg_accesos_contador).
CREATE TABLE contador_recursos (
    id            BIGSERIAL    PRIMARY KEY,
    recurso       VARCHAR(150) NOT NULL,
    metodo        VARCHAR(10)  NOT NULL,
    ruta          VARCHAR(191) NOT NULL,
    total         BIGINT       NOT NULL DEFAULT 0,
    ultimo_acceso TIMESTAMP(0),
    created_at    TIMESTAMP(0),
    updated_at    TIMESTAMP(0),
    CONSTRAINT contador_recursos_recurso_metodo_unique UNIQUE (recurso, metodo)
);

-- =============================================================================
-- 10. FUNCIONES PL/pgSQL Y TRIGGERS DE LOS CONTADORES
--     (mismo SQL que database/migrations/2026_11_26_000007_create_triggers_contadores.php)
-- =============================================================================

-- trg_visitas_contador: cada visita suma 1 al total histórico de la página (contador_visitas)
-- y al total del día (visitas_diarias), creando la fila si aún no existe (upsert).
CREATE OR REPLACE FUNCTION fn_visitas_contador() RETURNS trigger AS $$
BEGIN
    -- Total histórico de la página.
    INSERT INTO contador_visitas AS c (ruta, total, ultima_visita, created_at, updated_at)
    VALUES (NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
    ON CONFLICT (ruta) DO UPDATE
        SET total = c.total + 1,
            ultima_visita = GREATEST(c.ultima_visita, NEW.created_at),
            updated_at = NEW.created_at;

    -- Total del día para la página.
    INSERT INTO visitas_diarias AS d (fecha, ruta, total)
    VALUES (CAST(NEW.created_at AS DATE), NEW.ruta, 1)
    ON CONFLICT (fecha, ruta) DO UPDATE
        SET total = d.total + 1;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_visitas_contador AFTER INSERT ON visitas
    FOR EACH ROW EXECUTE PROCEDURE fn_visitas_contador();

-- trg_inicios_sesion_usuario: un intento exitoso registra el último acceso y reinicia los
-- intentos fallidos; un intento fallido de un usuario existente los incrementa.
CREATE OR REPLACE FUNCTION fn_inicios_sesion_usuario() RETURNS trigger AS $$
BEGIN
    IF NEW.user_id IS NOT NULL THEN
        IF NEW.exitoso THEN
            UPDATE users SET ultimo_acceso = NEW.created_at, intentos_fallidos = 0 WHERE id = NEW.user_id;
        ELSE
            UPDATE users SET intentos_fallidos = intentos_fallidos + 1 WHERE id = NEW.user_id;
        END IF;
    END IF;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_inicios_sesion_usuario AFTER INSERT ON inicios_sesion
    FOR EACH ROW EXECUTE PROCEDURE fn_inicios_sesion_usuario();

-- trg_accesos_contador: cada solicitud a la API suma 1 al contador de su recurso y método.
CREATE OR REPLACE FUNCTION fn_accesos_contador() RETURNS trigger AS $$
BEGIN
    INSERT INTO contador_recursos AS c (recurso, metodo, ruta, total, ultimo_acceso, created_at, updated_at)
    VALUES (NEW.recurso, NEW.metodo, NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
    ON CONFLICT (recurso, metodo) DO UPDATE
        SET total = c.total + 1,
            ruta = NEW.ruta,
            ultimo_acceso = GREATEST(c.ultimo_acceso, NEW.created_at),
            updated_at = NEW.created_at;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_accesos_contador AFTER INSERT ON accesos
    FOR EACH ROW EXECUTE PROCEDURE fn_accesos_contador();

-- =============================================================================
-- 11. REGISTRO DE MIGRACIONES: "php artisan migrate" reconoce el esquema como aplicado
-- =============================================================================

INSERT INTO migrations (migration, batch) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2026_10_01_000001_create_portal_tables', 1),
    ('2026_10_01_000002_create_academico_tables', 1),
    ('2026_10_01_000003_create_investigacion_tables', 1),
    ('2026_10_01_000004_create_vinculacion_tables', 1),
    ('2026_10_01_000005_create_difusion_tables', 1),
    ('2026_10_01_000006_create_consultas_tables', 1),
    ('2026_11_26_000001_add_preferencias_acceso_to_users_table', 1),
    ('2026_11_26_000002_create_personal_access_tokens_table', 1),
    ('2026_11_26_000003_add_recurso_accion_to_permisos_table', 1),
    ('2026_11_26_000004_create_visitas_table', 1),
    ('2026_11_26_000005_create_inicios_sesion_table', 1),
    ('2026_11_26_000006_create_accesos_table', 1),
    ('2026_11_26_000007_create_triggers_contadores', 1);

-- =============================================================================
-- 12. DATOS MÍNIMOS DE SEGURIDAD (mismos valores que database/seeders/SeguridadSeeder.php)
-- =============================================================================

-- 12.1 Matriz de control de acceso: permisos '{recurso}.{accion}'.
INSERT INTO permisos (nombre, recurso, accion, descripcion, modulo, created_at, updated_at) VALUES
    ('usuarios.ver', 'usuarios', 'ver', 'Ver usuarios del sistema', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('usuarios.crear', 'usuarios', 'crear', 'Crear usuarios del sistema', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('usuarios.editar', 'usuarios', 'editar', 'Editar usuarios del sistema', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('usuarios.eliminar', 'usuarios', 'eliminar', 'Eliminar usuarios del sistema', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('roles.ver', 'roles', 'ver', 'Ver roles y sus permisos', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('roles.crear', 'roles', 'crear', 'Crear roles y sus permisos', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('roles.editar', 'roles', 'editar', 'Editar roles y sus permisos', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('roles.eliminar', 'roles', 'eliminar', 'Eliminar roles y sus permisos', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('menu.ver', 'menu', 'ver', 'Ver opciones del menú del panel', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('menu.crear', 'menu', 'crear', 'Crear opciones del menú del panel', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('menu.editar', 'menu', 'editar', 'Editar opciones del menú del panel', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('menu.eliminar', 'menu', 'eliminar', 'Eliminar opciones del menú del panel', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('bitacora.ver', 'bitacora', 'ver', 'Ver la bitácora de auditoría, inicios de sesión y tráfico', 'Usuarios y seguridad', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('portal.ver', 'portal', 'ver', 'Ver contenido de la página principal (banners, destacados, enlaces y contacto)', 'Portal web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('portal.crear', 'portal', 'crear', 'Crear contenido de la página principal (banners, destacados, enlaces y contacto)', 'Portal web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('portal.editar', 'portal', 'editar', 'Editar contenido de la página principal (banners, destacados, enlaces y contacto)', 'Portal web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('portal.eliminar', 'portal', 'eliminar', 'Eliminar contenido de la página principal (banners, destacados, enlaces y contacto)', 'Portal web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('academico.ver', 'academico', 'ver', 'Ver páginas de contenido, malla curricular, calendario y preguntas frecuentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('academico.crear', 'academico', 'crear', 'Crear páginas de contenido, malla curricular, calendario y preguntas frecuentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('academico.editar', 'academico', 'editar', 'Editar páginas de contenido, malla curricular, calendario y preguntas frecuentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('academico.eliminar', 'academico', 'eliminar', 'Eliminar páginas de contenido, malla curricular, calendario y preguntas frecuentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('docentes.ver', 'docentes', 'ver', 'Ver autoridades y docentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('docentes.crear', 'docentes', 'crear', 'Crear autoridades y docentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('docentes.editar', 'docentes', 'editar', 'Editar autoridades y docentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('docentes.eliminar', 'docentes', 'eliminar', 'Eliminar autoridades y docentes', 'Académico', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('documentos.ver', 'documentos', 'ver', 'Ver documentos de la biblioteca digital', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('documentos.crear', 'documentos', 'crear', 'Crear documentos de la biblioteca digital', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('documentos.editar', 'documentos', 'editar', 'Editar documentos de la biblioteca digital', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('documentos.eliminar', 'documentos', 'eliminar', 'Eliminar documentos de la biblioteca digital', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('investigacion.ver', 'investigacion', 'ver', 'Ver líneas y proyectos de investigación', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('investigacion.crear', 'investigacion', 'crear', 'Crear líneas y proyectos de investigación', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('investigacion.editar', 'investigacion', 'editar', 'Editar líneas y proyectos de investigación', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('investigacion.eliminar', 'investigacion', 'eliminar', 'Eliminar líneas y proyectos de investigación', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('revista.ver', 'revista', 'ver', 'Ver ediciones y artículos de la Revista INNOVAR', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('revista.crear', 'revista', 'crear', 'Crear ediciones y artículos de la Revista INNOVAR', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('revista.editar', 'revista', 'editar', 'Editar ediciones y artículos de la Revista INNOVAR', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('revista.eliminar', 'revista', 'eliminar', 'Eliminar ediciones y artículos de la Revista INNOVAR', 'Investigación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('vinculacion.ver', 'vinculacion', 'ver', 'Ver convenios institucionales', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('vinculacion.crear', 'vinculacion', 'crear', 'Crear convenios institucionales', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('vinculacion.editar', 'vinculacion', 'editar', 'Editar convenios institucionales', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('vinculacion.eliminar', 'vinculacion', 'eliminar', 'Eliminar convenios institucionales', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('innova.ver', 'innova', 'ver', 'Ver servicios, integrantes, proyectos, solicitudes y postulaciones de INNOVA', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('innova.crear', 'innova', 'crear', 'Crear servicios, integrantes, proyectos, solicitudes y postulaciones de INNOVA', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('innova.editar', 'innova', 'editar', 'Editar servicios, integrantes, proyectos, solicitudes y postulaciones de INNOVA', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('innova.eliminar', 'innova', 'eliminar', 'Eliminar servicios, integrantes, proyectos, solicitudes y postulaciones de INNOVA', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('graduados.ver', 'graduados', 'ver', 'Ver fichas de seguimiento a graduados', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('graduados.crear', 'graduados', 'crear', 'Crear fichas de seguimiento a graduados', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('graduados.editar', 'graduados', 'editar', 'Editar fichas de seguimiento a graduados', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('graduados.eliminar', 'graduados', 'eliminar', 'Eliminar fichas de seguimiento a graduados', 'Vinculación', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('noticias.ver', 'noticias', 'ver', 'Ver noticias, comunicados y sus categorías', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('noticias.crear', 'noticias', 'crear', 'Crear noticias, comunicados y sus categorías', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('noticias.editar', 'noticias', 'editar', 'Editar noticias, comunicados y sus categorías', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('noticias.eliminar', 'noticias', 'eliminar', 'Eliminar noticias, comunicados y sus categorías', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('eventos.ver', 'eventos', 'ver', 'Ver eventos académicos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('eventos.crear', 'eventos', 'crear', 'Crear eventos académicos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('eventos.editar', 'eventos', 'editar', 'Editar eventos académicos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('eventos.eliminar', 'eventos', 'eliminar', 'Eliminar eventos académicos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('galeria.ver', 'galeria', 'ver', 'Ver álbumes de fotos y videos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('galeria.crear', 'galeria', 'crear', 'Crear álbumes de fotos y videos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('galeria.editar', 'galeria', 'editar', 'Editar álbumes de fotos y videos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('galeria.eliminar', 'galeria', 'eliminar', 'Eliminar álbumes de fotos y videos', 'Difusión', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('consultas.ver', 'consultas', 'ver', 'Ver consultas recibidas y sus categorías', 'Consultas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('consultas.crear', 'consultas', 'crear', 'Crear consultas recibidas y sus categorías', 'Consultas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('consultas.editar', 'consultas', 'editar', 'Editar consultas recibidas y sus categorías', 'Consultas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('consultas.eliminar', 'consultas', 'eliminar', 'Eliminar consultas recibidas y sus categorías', 'Consultas', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('reportes.ver', 'reportes', 'ver', 'Ver reportes y estadísticas', 'Reportes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- 12.2 Roles (Graduado no tiene acceso al panel).
INSERT INTO roles (nombre, descripcion, acceso_panel, created_at, updated_at) VALUES
    ('Administrador', 'Acceso total al sistema', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Responsable de Noticias', 'Publica noticias, eventos y la galería multimedia', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Encargado Revista INNOVAR', 'Administra la Revista INNOVAR, la investigación y la biblioteca digital', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Administrador de Graduados', 'Seguimiento a graduados, Empresa Junior INNOVA y convenios', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Secretaría Académica', 'Contenido académico, plantel docente, biblioteca y consultas', TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Graduado', 'Titulado que actualiza sus datos desde el sitio (sin acceso al panel)', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- 12.3 Permisos de cada rol:
--   Administrador ............... todos
--   Responsable de Noticias ..... noticias.*, eventos.*, galeria.*, reportes.ver
--   Encargado Revista INNOVAR ... revista.*, investigacion.*, documentos.ver/crear/editar
--   Administrador de Graduados .. graduados.*, innova.*, vinculacion.*, reportes.ver
--   Secretaría Académica ........ academico.*, docentes.*, documentos.*, consultas.ver/editar
--   Graduado .................... ninguno
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
  FROM roles r
  JOIN permisos p ON
       r.nombre = 'Administrador'
    OR (r.nombre = 'Responsable de Noticias'
        AND (p.recurso IN ('noticias', 'eventos', 'galeria') OR p.nombre = 'reportes.ver'))
    OR (r.nombre = 'Encargado Revista INNOVAR'
        AND (p.recurso IN ('revista', 'investigacion') OR p.nombre IN ('documentos.ver', 'documentos.crear', 'documentos.editar')))
    OR (r.nombre = 'Administrador de Graduados'
        AND (p.recurso IN ('graduados', 'innova', 'vinculacion') OR p.nombre = 'reportes.ver'))
    OR (r.nombre = 'Secretaría Académica'
        AND (p.recurso IN ('academico', 'docentes', 'documentos') OR p.nombre IN ('consultas.ver', 'consultas.editar')));

-- 12.4 Cuenta del administrador (contraseña "password" cifrada con bcrypt).
INSERT INTO users (name, email, rol_id, activo, email_verified_at, password, created_at, updated_at)
SELECT 'Administrador del Sistema', 'admin@carrera.test', id, TRUE, CURRENT_TIMESTAMP,
       '$2y$12$FzUhKXzMcGCFWrdTATlA5.MHj.cA2cj1niG82G0MQ9vKuBPAZ9gB2', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
  FROM roles
 WHERE nombre = 'Administrador';

-- 12.5 Menú dinámico del panel: primero los grupos (y las opciones sin grupo)...
INSERT INTO menu_items (etiqueta, ruta, icono, permiso, padre_id, orden, activo, created_at, updated_at) VALUES
    ('Inicio', '/admin', 'layout-dashboard', NULL, NULL, 1, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Usuarios y seguridad', NULL, 'shield', NULL, NULL, 2, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Portal web', NULL, 'home', NULL, NULL, 3, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Académico', NULL, 'graduation-cap', NULL, NULL, 4, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Investigación', NULL, 'flask-conical', NULL, NULL, 5, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Vinculación', NULL, 'handshake', NULL, NULL, 6, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Difusión', NULL, 'newspaper', NULL, NULL, 7, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Consultas', NULL, 'mail', NULL, NULL, 8, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('Reportes y estadísticas', '/admin/reportes', 'bar-chart-3', 'reportes.ver', NULL, 9, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- ... y luego las opciones de cada grupo, con la ruta de la SPA y el permiso ".ver" requerido.
INSERT INTO menu_items (etiqueta, ruta, icono, permiso, padre_id, orden, activo, created_at, updated_at)
SELECT i.etiqueta, i.ruta, i.icono, i.permiso, g.id, i.orden, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
  FROM (VALUES
    ('Usuarios y seguridad', 'Usuarios', '/admin/usuarios', 'users', 'usuarios.ver', 1),
    ('Usuarios y seguridad', 'Roles y permisos', '/admin/roles', 'shield', 'roles.ver', 2),
    ('Usuarios y seguridad', 'Matriz de acceso', '/admin/roles/matriz', 'network', 'roles.ver', 3),
    ('Usuarios y seguridad', 'Menú del panel', '/admin/menu', 'menu', 'menu.ver', 4),
    ('Usuarios y seguridad', 'Bitácora y accesos', '/admin/bitacora', 'history', 'bitacora.ver', 5),
    ('Portal web', 'Página principal', '/admin/configuracion/portal', 'settings', 'portal.ver', 1),
    ('Portal web', 'Carrusel de banners', '/admin/banners', 'image', 'portal.ver', 2),
    ('Portal web', 'Información destacada', '/admin/destacados', 'star', 'portal.ver', 3),
    ('Portal web', 'Enlaces a plataformas', '/admin/enlaces', 'link', 'portal.ver', 4),
    ('Portal web', 'Contacto y redes', '/admin/configuracion/contacto', 'contact', 'portal.ver', 5),
    ('Académico', 'Páginas de contenido', '/admin/paginas', 'file-text', 'academico.ver', 1),
    ('Académico', 'Autoridades y docentes', '/admin/docentes', 'users', 'docentes.ver', 2),
    ('Académico', 'Malla curricular', '/admin/materias', 'network', 'academico.ver', 3),
    ('Académico', 'Calendario académico', '/admin/calendario', 'calendar', 'academico.ver', 4),
    ('Académico', 'Preguntas frecuentes', '/admin/preguntas', 'help-circle', 'academico.ver', 5),
    ('Investigación', 'Biblioteca digital', '/admin/documentos', 'library', 'documentos.ver', 1),
    ('Investigación', 'Categorías de documentos', '/admin/categorias-documento', 'folder', 'documentos.ver', 2),
    ('Investigación', 'Líneas de investigación', '/admin/lineas', 'flask-conical', 'investigacion.ver', 3),
    ('Investigación', 'Proyectos de investigación', '/admin/proyectos', 'flask-conical', 'investigacion.ver', 4),
    ('Investigación', 'Revista INNOVAR - Ediciones', '/admin/ediciones', 'book-open', 'revista.ver', 5),
    ('Investigación', 'Revista INNOVAR - Artículos', '/admin/articulos', 'file-text', 'revista.ver', 6),
    ('Vinculación', 'Convenios', '/admin/convenios', 'handshake', 'vinculacion.ver', 1),
    ('Vinculación', 'INNOVA - Servicios', '/admin/innova-servicios', 'briefcase', 'innova.ver', 2),
    ('Vinculación', 'INNOVA - Integrantes', '/admin/innova-integrantes', 'users', 'innova.ver', 3),
    ('Vinculación', 'INNOVA - Proyectos', '/admin/innova-proyectos', 'briefcase', 'innova.ver', 4),
    ('Vinculación', 'INNOVA - Solicitudes', '/admin/innova-solicitudes', 'inbox', 'innova.ver', 5),
    ('Vinculación', 'INNOVA - Postulaciones', '/admin/innova-postulaciones', 'user-check', 'innova.ver', 6),
    ('Vinculación', 'Graduados', '/admin/graduados', 'graduation-cap', 'graduados.ver', 7),
    ('Difusión', 'Noticias', '/admin/noticias', 'newspaper', 'noticias.ver', 1),
    ('Difusión', 'Categorías de noticias', '/admin/categorias-noticia', 'tags', 'noticias.ver', 2),
    ('Difusión', 'Eventos', '/admin/eventos', 'calendar-days', 'eventos.ver', 3),
    ('Difusión', 'Álbumes de fotos', '/admin/albumes', 'images', 'galeria.ver', 4),
    ('Difusión', 'Videos', '/admin/videos', 'video', 'galeria.ver', 5),
    ('Consultas', 'Consultas recibidas', '/admin/consultas', 'inbox', 'consultas.ver', 1),
    ('Consultas', 'Categorías de consulta', '/admin/categorias-consulta', 'tags', 'consultas.ver', 2)
  ) AS i (grupo, etiqueta, ruta, icono, permiso, orden)
  JOIN menu_items g ON g.etiqueta = i.grupo AND g.padre_id IS NULL;

COMMIT;
