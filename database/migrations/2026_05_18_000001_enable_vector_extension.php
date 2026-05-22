<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

            $installed = DB::scalar("SELECT extname FROM pg_extension WHERE extname = 'vector' LIMIT 1");
            if ($installed !== 'vector') {
                throw new \RuntimeException(
                    'La extensión pgvector no está disponible en esta base PostgreSQL. '.
                    'En Railway habilita una base con soporte pgvector y vuelve a ejecutar migraciones.'
                );
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'No fue posible habilitar/verificar la extensión pgvector (vector). '.
                'Configura PostgreSQL con soporte pgvector en Railway y vuelve a desplegar. '.
                'Detalle: '.$exception->getMessage(),
                previous: $exception
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP EXTENSION IF EXISTS vector;');
    }
};
