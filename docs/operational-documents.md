# Operational Word Document Handling

The repository did not contain DOC/DOCX operational examples at implementation time. The platform therefore includes a production-ready fallback DOCX generator and a clear path for importing the company's real Word forms later.

## Current implementation

`MovementDocumentService` uses PHPWord to generate a Microsoft Word movement form for:

- Incoming inventory
- Outgoing deployments
- Transfers
- Returns
- Repair intake/return
- Equipment swaps
- Retirements

The generated document includes movement number, date, user, client, origin/destination, notes, equipment identifiers, phone numbers, carriers, SIM ICCIDs, statuses, and sign-off rows.

## Importing real templates later

When the actual Word documents are added to the repository:

1. Store canonical templates under `resources/document-templates/`.
2. Add a profile in `config/document_templates.php` for each workflow.
3. Extend `MovementDocumentService` to select either a styled PHPWord layout or a template processor per movement type.
4. Add visual regression review samples in `storage/app/generated-documents` during QA, but do not commit generated archives.

The document archive records template key, file path, checksum, generator user, and generated timestamp so staff can reproduce and audit operational paperwork.
