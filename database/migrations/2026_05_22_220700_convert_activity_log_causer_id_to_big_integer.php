<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConvertActivityLogCauserIdToBigInteger extends Migration
{
    public function up()
    {
        $this->convertCauserId('uuid', 'BIGINT', 'NULL::BIGINT');
    }

    public function down()
    {
        $this->convertCauserId('bigint', 'UUID', 'NULL::UUID');
    }

    private function convertCauserId(string $fromType, string $toType, string $usingExpression): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name');

        if (! Schema::connection($connection)->hasColumn($table, 'causer_id')) {
            return;
        }

        if (DB::connection($connection)->getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::connection($connection)->getColumnType($table, 'causer_id') !== $fromType) {
            return;
        }

        DB::connection($connection)->statement(sprintf(
            'ALTER TABLE %s ALTER COLUMN causer_id TYPE %s USING %s',
            $this->quoteIdentifier(DB::connection($connection)->getTablePrefix().$table),
            $toType,
            $usingExpression,
        ));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return collect(explode('.', $identifier))
            ->map(fn (string $part): string => '"'.str_replace('"', '""', $part).'"')
            ->implode('.');
    }
}
