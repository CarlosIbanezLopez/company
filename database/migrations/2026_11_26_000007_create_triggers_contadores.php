<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Capa 3 - Triggers de los contadores. La aplicación solo inserta filas en las tablas
 * de registro (visitas, inicios_sesion, accesos) y la base de datos actualiza los totales:
 *
 *  - trg_visitas_contador        AFTER INSERT ON visitas
 *        upsert en contador_visitas (total histórico por ruta y última visita) y en
 *        visitas_diarias (total por fecha y ruta).
 *  - trg_inicios_sesion_usuario  AFTER INSERT ON inicios_sesion
 *        intento exitoso: users.ultimo_acceso = fecha del intento e intentos_fallidos = 0;
 *        intento fallido de un usuario existente: intentos_fallidos + 1.
 *  - trg_accesos_contador        AFTER INSERT ON accesos
 *        upsert en contador_recursos (total por recurso y método HTTP, último acceso).
 *
 * El SQL depende del motor: PostgreSQL (funciones PL/pgSQL + INSERT ... ON CONFLICT),
 * SQLite (pruebas automatizadas, ON CONFLICT dentro del trigger) y MySQL/MariaDB
 * (ON DUPLICATE KEY UPDATE). El mismo SQL de PostgreSQL está en database/sql/esquema_postgresql.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->sentencias(DB::getDriverName()) as $sql) {
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        foreach (['visitas' => 'trg_visitas_contador', 'inicios_sesion' => 'trg_inicios_sesion_usuario', 'accesos' => 'trg_accesos_contador'] as $tabla => $trigger) {
            DB::unprepared($driver === 'pgsql'
                ? "DROP TRIGGER IF EXISTS {$trigger} ON {$tabla}"
                : "DROP TRIGGER IF EXISTS {$trigger}");
        }

        if ($driver === 'pgsql') {
            foreach (['fn_visitas_contador', 'fn_inicios_sesion_usuario', 'fn_accesos_contador'] as $funcion) {
                DB::unprepared("DROP FUNCTION IF EXISTS {$funcion}()");
            }
        }
    }

    /**
     * @return list<literal-string>
     */
    private function sentencias(string $driver): array
    {
        return match ($driver) {
            'pgsql' => $this->postgresql(),
            'sqlite' => $this->sqlite(),
            'mysql', 'mariadb' => $this->mysql(),
            default => throw new RuntimeException("Motor de base de datos no soportado para los triggers de contadores: {$driver}."),
        };
    }

    /**
     * PostgreSQL: una función PL/pgSQL por trigger. Se usa EXECUTE PROCEDURE
     * (equivalente a EXECUTE FUNCTION) para ser compatible también con PostgreSQL 9.5 a 10.
     *
     * @return list<literal-string>
     */
    private function postgresql(): array
    {
        return [
            <<<'SQL'
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
            $$ LANGUAGE plpgsql
            SQL,
            'DROP TRIGGER IF EXISTS trg_visitas_contador ON visitas',
            'CREATE TRIGGER trg_visitas_contador AFTER INSERT ON visitas FOR EACH ROW EXECUTE PROCEDURE fn_visitas_contador()',

            <<<'SQL'
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
            $$ LANGUAGE plpgsql
            SQL,
            'DROP TRIGGER IF EXISTS trg_inicios_sesion_usuario ON inicios_sesion',
            'CREATE TRIGGER trg_inicios_sesion_usuario AFTER INSERT ON inicios_sesion FOR EACH ROW EXECUTE PROCEDURE fn_inicios_sesion_usuario()',

            <<<'SQL'
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
            $$ LANGUAGE plpgsql
            SQL,
            'DROP TRIGGER IF EXISTS trg_accesos_contador ON accesos',
            'CREATE TRIGGER trg_accesos_contador AFTER INSERT ON accesos FOR EACH ROW EXECUTE PROCEDURE fn_accesos_contador()',
        ];
    }

    /**
     * SQLite (base en memoria de las pruebas): UPSERT dentro del cuerpo del trigger.
     *
     * @return list<literal-string>
     */
    private function sqlite(): array
    {
        return [
            'DROP TRIGGER IF EXISTS trg_visitas_contador',
            <<<'SQL'
            CREATE TRIGGER trg_visitas_contador AFTER INSERT ON visitas
            FOR EACH ROW
            BEGIN
                INSERT INTO contador_visitas (ruta, total, ultima_visita, created_at, updated_at)
                VALUES (NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
                ON CONFLICT (ruta) DO UPDATE
                    SET total = total + 1,
                        ultima_visita = MAX(COALESCE(ultima_visita, NEW.created_at), NEW.created_at),
                        updated_at = NEW.created_at;

                INSERT INTO visitas_diarias (fecha, ruta, total)
                VALUES (DATE(NEW.created_at), NEW.ruta, 1)
                ON CONFLICT (fecha, ruta) DO UPDATE
                    SET total = total + 1;
            END
            SQL,

            'DROP TRIGGER IF EXISTS trg_inicios_sesion_usuario',
            <<<'SQL'
            CREATE TRIGGER trg_inicios_sesion_usuario AFTER INSERT ON inicios_sesion
            FOR EACH ROW
            WHEN NEW.user_id IS NOT NULL
            BEGIN
                UPDATE users SET ultimo_acceso = NEW.created_at, intentos_fallidos = 0
                 WHERE id = NEW.user_id AND NEW.exitoso;

                UPDATE users SET intentos_fallidos = intentos_fallidos + 1
                 WHERE id = NEW.user_id AND NOT NEW.exitoso;
            END
            SQL,

            'DROP TRIGGER IF EXISTS trg_accesos_contador',
            <<<'SQL'
            CREATE TRIGGER trg_accesos_contador AFTER INSERT ON accesos
            FOR EACH ROW
            BEGIN
                INSERT INTO contador_recursos (recurso, metodo, ruta, total, ultimo_acceso, created_at, updated_at)
                VALUES (NEW.recurso, NEW.metodo, NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
                ON CONFLICT (recurso, metodo) DO UPDATE
                    SET total = total + 1,
                        ruta = NEW.ruta,
                        ultimo_acceso = MAX(COALESCE(ultimo_acceso, NEW.created_at), NEW.created_at),
                        updated_at = NEW.created_at;
            END
            SQL,
        ];
    }

    /**
     * MySQL / MariaDB: ON DUPLICATE KEY UPDATE sobre los índices únicos de los contadores.
     * Con el registro binario activo, el usuario necesita privilegio TRIGGER (y SUPER o
     * log_bin_trust_function_creators = 1).
     *
     * @return list<literal-string>
     */
    private function mysql(): array
    {
        return [
            'DROP TRIGGER IF EXISTS trg_visitas_contador',
            <<<'SQL'
            CREATE TRIGGER trg_visitas_contador AFTER INSERT ON visitas
            FOR EACH ROW
            BEGIN
                INSERT INTO contador_visitas (ruta, total, ultima_visita, created_at, updated_at)
                VALUES (NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
                ON DUPLICATE KEY UPDATE
                    total = total + 1,
                    ultima_visita = GREATEST(COALESCE(ultima_visita, NEW.created_at), NEW.created_at),
                    updated_at = NEW.created_at;

                INSERT INTO visitas_diarias (fecha, ruta, total)
                VALUES (DATE(NEW.created_at), NEW.ruta, 1)
                ON DUPLICATE KEY UPDATE
                    total = total + 1;
            END
            SQL,

            'DROP TRIGGER IF EXISTS trg_inicios_sesion_usuario',
            <<<'SQL'
            CREATE TRIGGER trg_inicios_sesion_usuario AFTER INSERT ON inicios_sesion
            FOR EACH ROW
            BEGIN
                IF NEW.user_id IS NOT NULL THEN
                    IF NEW.exitoso THEN
                        UPDATE users SET ultimo_acceso = NEW.created_at, intentos_fallidos = 0 WHERE id = NEW.user_id;
                    ELSE
                        UPDATE users SET intentos_fallidos = intentos_fallidos + 1 WHERE id = NEW.user_id;
                    END IF;
                END IF;
            END
            SQL,

            'DROP TRIGGER IF EXISTS trg_accesos_contador',
            <<<'SQL'
            CREATE TRIGGER trg_accesos_contador AFTER INSERT ON accesos
            FOR EACH ROW
            BEGIN
                INSERT INTO contador_recursos (recurso, metodo, ruta, total, ultimo_acceso, created_at, updated_at)
                VALUES (NEW.recurso, NEW.metodo, NEW.ruta, 1, NEW.created_at, NEW.created_at, NEW.created_at)
                ON DUPLICATE KEY UPDATE
                    total = total + 1,
                    ruta = NEW.ruta,
                    ultimo_acceso = GREATEST(COALESCE(ultimo_acceso, NEW.created_at), NEW.created_at),
                    updated_at = NEW.created_at;
            END
            SQL,
        ];
    }
};
