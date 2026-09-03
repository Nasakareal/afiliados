<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'afiliados_resumen';

    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create(self::TABLE, function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite') {
                $table->engine = 'InnoDB';
            }

            $table->id();
            $table->string('dimension_key', $driver === 'sqlite' ? 1000 : 64)->unique();
            $table->string('cve_mun', 3)->default('');
            $table->string('municipio', 120)->default('');
            $table->string('seccion', 6)->default('');
            $table->unsignedSmallInteger('distrito_local')->default(0);
            $table->unsignedSmallInteger('distrito_federal')->default(0);
            $table->unsignedBigInteger('capturista_id')->default(0);
            $table->string('referente', 191)->default('');
            $table->string('estatus', 20)->default('');
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamps();

            $table->index(['distrito_local', 'capturista_id'], 'idx_af_res_dl_capturista');
            $table->index(['cve_mun', 'distrito_local'], 'idx_af_res_mun_dl');
            $table->index('distrito_federal', 'idx_af_res_df');
            $table->index('municipio', 'idx_af_res_municipio');
            $table->index('seccion', 'idx_af_res_seccion');
            $table->index('referente', 'idx_af_res_referente');
            $table->index('estatus', 'idx_af_res_estatus');
        });

        $this->rebuild();
        $this->createTriggers();
    }

    public function down(): void
    {
        $this->dropTriggers();
        Schema::dropIfExists(self::TABLE);
    }

    private function rebuild(): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        $now = $sqlite ? 'CURRENT_TIMESTAMP' : 'NOW()';
        $key = $sqlite
            ? $this->sqliteKey('')
            : $this->mysqlKey('');

        DB::statement(<<<SQL
            INSERT INTO afiliados_resumen (
                dimension_key, cve_mun, municipio, seccion, distrito_local, distrito_federal,
                capturista_id, referente, estatus, total, created_at, updated_at
            )
            SELECT
                {$key},
                COALESCE(cve_mun, ''),
                COALESCE(municipio, ''),
                COALESCE(seccion, ''),
                COALESCE(distrito_local, 0),
                COALESCE(distrito_federal, 0),
                COALESCE(capturista_id, 0),
                SUBSTR(TRIM(COALESCE(perfil, '')), 1, 191),
                COALESCE(estatus, ''),
                COUNT(*),
                {$now},
                {$now}
            FROM afiliados
            WHERE deleted_at IS NULL
            GROUP BY
                {$key},
                COALESCE(cve_mun, ''),
                COALESCE(municipio, ''),
                COALESCE(seccion, ''),
                COALESCE(distrito_local, 0),
                COALESCE(distrito_federal, 0),
                COALESCE(capturista_id, 0),
                SUBSTR(TRIM(COALESCE(perfil, '')), 1, 191),
                COALESCE(estatus, '')
        SQL);
    }

    private function createTriggers(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->createSqliteTriggers();
            return;
        }

        $this->createMysqlTriggers();
    }

    private function createMysqlTriggers(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER afiliados_resumen_ai AFTER INSERT ON afiliados
            FOR EACH ROW
            BEGIN
                IF NEW.deleted_at IS NULL THEN
                    INSERT INTO afiliados_resumen (
                        dimension_key, cve_mun, municipio, seccion, distrito_local, distrito_federal,
                        capturista_id, referente, estatus, total, created_at, updated_at
                    ) VALUES (
                        SHA2(CONCAT_WS(CHAR(31), COALESCE(NEW.cve_mun, ''), COALESCE(NEW.municipio, ''), COALESCE(NEW.seccion, ''), COALESCE(NEW.distrito_local, 0), COALESCE(NEW.distrito_federal, 0), COALESCE(NEW.capturista_id, 0), LEFT(TRIM(COALESCE(NEW.perfil, '')), 191), COALESCE(NEW.estatus, '')), 256),
                        COALESCE(NEW.cve_mun, ''), COALESCE(NEW.municipio, ''),
                        COALESCE(NEW.seccion, ''), COALESCE(NEW.distrito_local, 0),
                        COALESCE(NEW.distrito_federal, 0), COALESCE(NEW.capturista_id, 0),
                        LEFT(TRIM(COALESCE(NEW.perfil, '')), 191), COALESCE(NEW.estatus, ''),
                        1, NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE total = total + 1, updated_at = NOW();
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER afiliados_resumen_au AFTER UPDATE ON afiliados
            FOR EACH ROW
            BEGIN
                IF OLD.deleted_at IS NULL THEN
                    UPDATE afiliados_resumen
                    SET total = total - 1, updated_at = NOW()
                    WHERE dimension_key = SHA2(CONCAT_WS(CHAR(31), COALESCE(OLD.cve_mun, ''), COALESCE(OLD.municipio, ''), COALESCE(OLD.seccion, ''), COALESCE(OLD.distrito_local, 0), COALESCE(OLD.distrito_federal, 0), COALESCE(OLD.capturista_id, 0), LEFT(TRIM(COALESCE(OLD.perfil, '')), 191), COALESCE(OLD.estatus, '')), 256);
                    DELETE FROM afiliados_resumen WHERE total = 0;
                END IF;

                IF NEW.deleted_at IS NULL THEN
                    INSERT INTO afiliados_resumen (
                        dimension_key, cve_mun, municipio, seccion, distrito_local, distrito_federal,
                        capturista_id, referente, estatus, total, created_at, updated_at
                    ) VALUES (
                        SHA2(CONCAT_WS(CHAR(31), COALESCE(NEW.cve_mun, ''), COALESCE(NEW.municipio, ''), COALESCE(NEW.seccion, ''), COALESCE(NEW.distrito_local, 0), COALESCE(NEW.distrito_federal, 0), COALESCE(NEW.capturista_id, 0), LEFT(TRIM(COALESCE(NEW.perfil, '')), 191), COALESCE(NEW.estatus, '')), 256),
                        COALESCE(NEW.cve_mun, ''), COALESCE(NEW.municipio, ''),
                        COALESCE(NEW.seccion, ''), COALESCE(NEW.distrito_local, 0),
                        COALESCE(NEW.distrito_federal, 0), COALESCE(NEW.capturista_id, 0),
                        LEFT(TRIM(COALESCE(NEW.perfil, '')), 191), COALESCE(NEW.estatus, ''),
                        1, NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE total = total + 1, updated_at = NOW();
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER afiliados_resumen_ad AFTER DELETE ON afiliados
            FOR EACH ROW
            BEGIN
                IF OLD.deleted_at IS NULL THEN
                    UPDATE afiliados_resumen
                    SET total = total - 1, updated_at = NOW()
                    WHERE dimension_key = SHA2(CONCAT_WS(CHAR(31), COALESCE(OLD.cve_mun, ''), COALESCE(OLD.municipio, ''), COALESCE(OLD.seccion, ''), COALESCE(OLD.distrito_local, 0), COALESCE(OLD.distrito_federal, 0), COALESCE(OLD.capturista_id, 0), LEFT(TRIM(COALESCE(OLD.perfil, '')), 191), COALESCE(OLD.estatus, '')), 256);
                    DELETE FROM afiliados_resumen WHERE total = 0;
                END IF;
            END
        SQL);
    }

    private function createSqliteTriggers(): void
    {
        DB::unprepared($this->sqliteIncrementTrigger(
            'afiliados_resumen_ai',
            'AFTER INSERT',
            'NEW'
        ));
        DB::unprepared($this->sqliteDecrementTrigger(
            'afiliados_resumen_au_old',
            'AFTER UPDATE',
            'OLD'
        ));
        DB::unprepared($this->sqliteIncrementTrigger(
            'afiliados_resumen_au_new',
            'AFTER UPDATE',
            'NEW'
        ));
        DB::unprepared($this->sqliteDecrementTrigger(
            'afiliados_resumen_ad',
            'AFTER DELETE',
            'OLD'
        ));
    }

    private function sqliteIncrementTrigger(string $name, string $event, string $row): string
    {
        return <<<SQL
            CREATE TRIGGER {$name} {$event} ON afiliados
            WHEN {$row}.deleted_at IS NULL
            BEGIN
                INSERT INTO afiliados_resumen (
                    dimension_key, cve_mun, municipio, seccion, distrito_local, distrito_federal,
                    capturista_id, referente, estatus, total, created_at, updated_at
                ) VALUES (
                    {$this->sqliteKey($row.'.')},
                    COALESCE({$row}.cve_mun, ''), COALESCE({$row}.municipio, ''),
                    COALESCE({$row}.seccion, ''), COALESCE({$row}.distrito_local, 0),
                    COALESCE({$row}.distrito_federal, 0), COALESCE({$row}.capturista_id, 0),
                    SUBSTR(TRIM(COALESCE({$row}.perfil, '')), 1, 191), COALESCE({$row}.estatus, ''),
                    1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
                ON CONFLICT (dimension_key)
                DO UPDATE SET total = total + 1, updated_at = CURRENT_TIMESTAMP;
            END
        SQL;
    }

    private function sqliteDecrementTrigger(string $name, string $event, string $row): string
    {
        return <<<SQL
            CREATE TRIGGER {$name} {$event} ON afiliados
            WHEN {$row}.deleted_at IS NULL
            BEGIN
                UPDATE afiliados_resumen
                SET total = total - 1, updated_at = CURRENT_TIMESTAMP
                WHERE dimension_key = {$this->sqliteKey($row.'.')};
                DELETE FROM afiliados_resumen WHERE total = 0;
            END
        SQL;
    }

    private function dropTriggers(): void
    {
        foreach ([
            'afiliados_resumen_ai',
            'afiliados_resumen_au',
            'afiliados_resumen_ad',
            'afiliados_resumen_au_old',
            'afiliados_resumen_au_new',
        ] as $trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        }
    }

    private function mysqlKey(string $prefix): string
    {
        return "SHA2(CONCAT_WS(CHAR(31), COALESCE({$prefix}cve_mun, ''), COALESCE({$prefix}municipio, ''), COALESCE({$prefix}seccion, ''), COALESCE({$prefix}distrito_local, 0), COALESCE({$prefix}distrito_federal, 0), COALESCE({$prefix}capturista_id, 0), LEFT(TRIM(COALESCE({$prefix}perfil, '')), 191), COALESCE({$prefix}estatus, '')), 256)";
    }

    private function sqliteKey(string $prefix): string
    {
        return "QUOTE(COALESCE({$prefix}cve_mun, '')) || '|' || QUOTE(COALESCE({$prefix}municipio, '')) || '|' || QUOTE(COALESCE({$prefix}seccion, '')) || '|' || QUOTE(COALESCE({$prefix}distrito_local, 0)) || '|' || QUOTE(COALESCE({$prefix}distrito_federal, 0)) || '|' || QUOTE(COALESCE({$prefix}capturista_id, 0)) || '|' || QUOTE(SUBSTR(TRIM(COALESCE({$prefix}perfil, '')), 1, 191)) || '|' || QUOTE(COALESCE({$prefix}estatus, ''))";
    }
};
