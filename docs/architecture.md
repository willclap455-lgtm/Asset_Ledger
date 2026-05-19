# Clancy Asset Ledger Architecture

Clancy Asset Ledger is a Laravel 12 server-rendered operations platform for parking-company inventory and asset traceability. It is intentionally built as a traditional internal business application: Blade templates, Bootstrap 5, Eloquent, PostgreSQL, queues, policies, form requests, services, migrations, seeders, and auditable domain events.

## Core design

- `inventory_items` is the canonical asset table with UUID primary keys, current assignment fields, status, condition, dates, and generic inventory attributes.
- Typed detail tables (`phones`, `printers`, `modems`, `sim_cards`) preserve device-specific identifiers and relationships without overloading the base asset row.
- `clients` and `locations` support internal stock areas and external client sites. Locations may be internal or attached to a client.
- `inventory_movements` and `inventory_movement_lines` are the immutable operational ledger. Movement lines capture previous and new assignment/status fields plus a JSON snapshot of identifiers such as phone number, carrier, SIM ICCID, IMEI, and printer relationships.
- `inventory_notes`, `repairs`, `generated_documents`, and Spatie Activitylog provide permanent operational context and auditability.

## Business services

- `InventoryItemService` owns creation/update of assets and typed detail records.
- `InventoryMovementService` records receiving, deployment, transfers, returns, repairs, swaps, and retirements in a transaction. It creates immutable movement lines before updating current asset state.
- `MovementDocumentService` generates archived DOCX movement forms using PHPWord.
- `GenerateMovementDocumentJob` allows queued document generation for bulk/background workflows.

## Permissions

Spatie Permission roles are seeded:

- Administrator
- Inventory Staff
- Repair Technician
- Read-Only User

Policies allow read access to all operational roles, inventory/client/location management to administrators and inventory staff, repair entry to technicians, and deny destructive deletes for assets and movements.

## Audit and traceability invariants

- Movement history is append-only through the UI and policies.
- Movement lines snapshot identifying data at the time of movement.
- Current assignment fields on `inventory_items` are convenience state; the historical source of truth is the movement ledger.
- Generated movement documents are archived and activity logged.
- Field changes, note additions, repairs, document generation, and movement recording are logged through Spatie Activitylog.

## Future expansion seams

The schema and services leave clear extension points for barcode labels, QR labels, photos/attachments, warranty data, carrier integrations, mobile app/API access, alerts, forecasting, and device diagnostics. Those should build on the asset UUIDs and movement ledger rather than bypassing them.
