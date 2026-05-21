# Operational Word Document Handling

The repository includes four legacy Word `.doc` examples in the root folder:

- `BANTANJ_20260501.doc`
- `MetAnschutz_20260520.doc`
- `UnifiedLA_20260505.doc`
- `WVState_20260511.doc`

These examples are compact one-page movement logs. They share the same operational structure:

1. Centered title: `INVENTORY MOVEMENT LOG`
2. Printed/generated date line: `DATE: n/j/YYYY`
3. Bordered five-column table:
   - `UNIT ID`
   - `PHONE`
   - `DESCRIPTION`
   - `FROM`
   - `TO`
4. Movement date and staff initials/signature line, such as `5/5/2026 -MRF`.

## Current implementation

`MovementDocumentService` uses PHPWord to generate a Microsoft Word movement log matching the example format for:

- Incoming inventory
- Outgoing deployments
- Transfers
- Returns
- Repair intake/return
- Equipment swaps
- Retirements

The generated document intentionally mirrors the current staff-facing paperwork instead of exposing every ledger field. It prints the operational unit ID, phone number when applicable, item description, origin, destination, generated date, and movement date/staff initials.

Richer traceability remains permanently available in the application through:

- `inventory_movements`
- `inventory_movement_lines`
- movement line JSON snapshots
- Spatie Activitylog entries
- generated document archive records

## Importing real templates later

If the business later wants pixel-level template reuse instead of generated PHPWord layout:

1. Store canonical templates under `resources/document-templates/`.
2. Add a profile in `config/document_templates.php` for each workflow.
3. Extend `MovementDocumentService` to select either a styled PHPWord layout or a template processor per movement type.
4. Add visual regression review samples in `storage/app/generated-documents` during QA, but do not commit generated archives.

The document archive records template key, file path, checksum, generator user, and generated timestamp so staff can reproduce and audit operational paperwork.
