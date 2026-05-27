<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Location;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientLocationImportService
{
    /**
     * @return array{created: int, updated: int}
     */
    public function importClients(UploadedFile $file): array
    {
        $rows = collect($this->readRows($file))
            ->map(fn (array $row): array => [
                'line' => $row['line'],
                'data' => [
                    'name' => $this->value($row['data'], ['name', 'client_name']),
                    'code' => $this->value($row['data'], ['code', 'client_code']),
                    'status' => $this->value($row['data'], ['status']) ?: 'active',
                    'primary_contact_name' => $this->value($row['data'], ['primary_contact_name', 'contact_name', 'primary_contact']),
                    'primary_contact_email' => $this->value($row['data'], ['primary_contact_email', 'contact_email', 'email']),
                    'primary_contact_phone' => $this->value($row['data'], ['primary_contact_phone', 'contact_phone', 'phone']),
                    'notes' => $this->value($row['data'], ['notes']),
                ],
            ])
            ->all();

        $this->validateRows($rows, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'string', 'max:80'],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($rows): array {
            return collect($rows)->reduce(function (array $totals, array $row): array {
                $data = $row['data'];
                $client = filled($data['code'])
                    ? Client::firstOrNew(['code' => $data['code']])
                    : Client::firstOrNew(['name' => $data['name'], 'code' => null]);
                $created = ! $client->exists;

                $client->fill($data)->save();
                $totals[$created ? 'created' : 'updated']++;

                return $totals;
            }, ['created' => 0, 'updated' => 0]);
        });
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function importLocations(UploadedFile $file): array
    {
        $rows = collect($this->readRows($file))
            ->map(fn (array $row): array => [
                'line' => $row['line'],
                'data' => [
                    'client_id' => $this->clientIdFor($row['data']),
                    'type' => $this->locationType($this->value($row['data'], ['type', 'location_type'])),
                    'name' => $this->value($row['data'], ['name', 'location_name']),
                    'code' => $this->value($row['data'], ['code', 'location_code']),
                    'address_line_1' => $this->value($row['data'], ['address_line_1', 'address1', 'address']),
                    'address_line_2' => $this->value($row['data'], ['address_line_2', 'address2']),
                    'city' => $this->value($row['data'], ['city']),
                    'state' => $this->value($row['data'], ['state']),
                    'postal_code' => $this->value($row['data'], ['postal_code', 'zip', 'zip_code']),
                    'contact_name' => $this->value($row['data'], ['contact_name']),
                    'contact_email' => $this->value($row['data'], ['contact_email', 'email']),
                    'contact_phone' => $this->value($row['data'], ['contact_phone', 'phone']),
                    'notes' => $this->value($row['data'], ['notes']),
                    'is_active' => $this->booleanValue($this->value($row['data'], ['is_active', 'active']), true),
                ],
            ])
            ->all();

        $this->validateRows($rows, [
            'client_id' => ['nullable', 'exists:clients,id'],
            'type' => ['required', Rule::in(['internal', 'client'])],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return DB::transaction(function () use ($rows): array {
            return collect($rows)->reduce(function (array $totals, array $row): array {
                $data = $row['data'];
                $location = filled($data['code'])
                    ? Location::firstOrNew(['code' => $data['code']])
                    : Location::firstOrNew(['client_id' => $data['client_id'], 'name' => $data['name']]);
                $created = ! $location->exists;

                $location->fill($data)->save();
                $totals[$created ? 'created' : 'updated']++;

                return $totals;
            }, ['created' => 0, 'updated' => 0]);
        });
    }

    /**
     * @return array<int, array{line: int, data: array<string, string|null>}>
     */
    private function readRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        $headers = null;
        $rows = [];
        $line = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isBlankRow($values)) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn (?string $header): string => $this->normalizeHeader((string) $header), $values);

                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = trim((string) ($values[$index] ?? ''));
                $row[$header] = $value === '' ? null : $value;
            }

            $rows[] = ['line' => $line, 'data' => $row];
        }

        fclose($handle);

        if ($headers === null) {
            throw ValidationException::withMessages(['csv_file' => 'CSV must include a header row.']);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['csv_file' => 'CSV must include at least one data row.']);
        }

        return $rows;
    }

    /**
     * @param  array<int, array{line: int, data: array<string, mixed>}>  $rows
     * @param  array<string, mixed>  $rules
     */
    private function validateRows(array $rows, array $rules): void
    {
        $messages = [];

        foreach ($rows as $row) {
            $validator = Validator::make($row['data'], $rules);

            foreach ($validator->errors()->all() as $error) {
                $messages['csv_file'][] = "Line {$row['line']}: {$error}";
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function value(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                return trim((string) $data[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function clientIdFor(array $data): ?string
    {
        $clientId = $this->value($data, ['client_id']);

        if ($clientId) {
            return $clientId;
        }

        $clientCode = $this->value($data, ['client_code']);
        $clientName = $this->value($data, ['client_name', 'client']);

        return Client::query()
            ->when($clientCode, fn ($query) => $query->where('code', $clientCode))
            ->when(! $clientCode && $clientName, fn ($query) => $query->where('name', $clientName))
            ->value('id');
    }

    private function locationType(?string $type): string
    {
        $normalized = str($type ?: 'internal')->lower()->replace([' ', '-'], '_')->toString();

        return match ($normalized) {
            'client_site', 'site' => 'client',
            default => $normalized,
        };
    }

    private function booleanValue(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        return collect($values)->every(fn ($value): bool => trim((string) $value) === '');
    }

    private function normalizeHeader(string $header): string
    {
        return str($header)
            ->replace("\xEF\xBB\xBF", '')
            ->lower()
            ->replace([' ', '-', '.'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }
}
