<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $table = config('activitylog.table_name');

        if (! Schema::connection($connection)->hasColumn($table, 'causer_id')) {
            return;
        }

        $database = DB::connection($connection);

        if ($database->getDriverName() !== 'pgsql') {
            return;
        }

        $tableParts = $this->postgresTableParts($connection, $database->getTablePrefix(), $table);
        $columnType = $this->postgresColumnType($connection, $tableParts['schema'], $tableParts['table'], 'causer_id');

        if ($columnType === 'bigint') {
            return;
        }

        $database->statement(sprintf(
            <<<'SQL'
                ALTER TABLE %s
                ALTER COLUMN causer_id DROP NOT NULL,
                ALTER COLUMN causer_id TYPE BIGINT USING (
                    CASE
                        WHEN causer_id IS NULL THEN NULL
                        WHEN causer_id::text ~ '^[0-9]+$' THEN causer_id::text::bigint
                        ELSE NULL
                    END
                )
                SQL,
            $this->quoteIdentifier($tableParts['qualified']),
        ));
    }

    public function down(): void
    {
        // Users use BIGINT primary keys, so the activity log causer id should
        // remain BIGINT. Rolling back to UUID would reintroduce production 500s.
    }

    /**
     * @return array{schema: string, table: string, qualified: string}
     */
    private function postgresTableParts(?string $connection, string $prefix, string $table): array
    {
        if (str_contains($table, '.')) {
            [$schema, $tableName] = explode('.', $table, 2);

            return [
                'schema' => $schema,
                'table' => $prefix.$tableName,
                'qualified' => $schema.'.'.$prefix.$tableName,
            ];
        }

        return [
            'schema' => DB::connection($connection)->selectOne('select current_schema() as schema')->schema,
            'table' => $prefix.$table,
            'qualified' => $prefix.$table,
        ];
    }

    private function postgresColumnType(?string $connection, string $schema, string $table, string $column): ?string
    {
        $result = DB::connection($connection)->selectOne(
            <<<'SQL'
                select data_type
                from information_schema.columns
                where table_schema = ?
                    and table_name = ?
                    and column_name = ?
                SQL,
            [$schema, $table, $column],
        );

        return $result?->data_type;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return collect(explode('.', $identifier))
            ->map(fn (string $part): string => '"'.str_replace('"', '""', $part).'"')
            ->implode('.');
    }
};
