# BOM and component completion

Implemented and applied to the application's Oracle database on 2026-10-25.

## Completed flows

- FG, Semi FG LV1 and LV2 have separate New and Existing component sections.
- Existing LV1/LV2 BOMs can be selected by Brand, Mattype and Sub Mattype, viewed and detached. LV1 requires an explicit LV2 parent; multiple available parents are selectable.
- Existing component selectors support FG, LV1, LV2 and nested Mattype 4. LV2 includes Product SUB Category.
- Mattype 4 supports nested New creation/edit/deletion and Existing attachment/view/deletion, with the complete parent BOM headers.
- Existing attachments preserve the source BOM. They have no update operation. Nested Mattype 4 mutations through a reused Existing LV1 remain read-only because the nested tables have no FG key to isolate source modifications.

Source BOM selectors use actual saved BOM/ID pairs. ERP-only materials without a saved BOM are excluded. Sub Mattype choices come from the same canonical source views, since the older LOV can be empty. Category filtering uses actual master fields: combined MAT_TYPE (for example 50) and product SUB_TYPE, rather than guessing from material IDs.

Mutations resolve descriptions and parent IDs from Oracle, lock parent rows, reject completed/exported paths and duplicate links, and scope changes to the full parent path. Editing/deleting saved INS rows requires their creator. Deletion removes the attachment, not the source master, and records an audit entry. Nested raw materials attached through an Existing LV1 cannot modify the shared source BOM.

## Oracle changes

`database/sql/complete_existing_bom_flows.sql` is idempotent and includes:

- Four source/detail and compatibility views for LV1/LV2.
- Eight corrected save/generator procedures, including the correct Existing target tables, LV1 argument mapping, scoped New updates, and nested Mattype 4 metadata.
- An explicit source BOM column on the LV2 Existing ID table and four category columns on the nested New Mattype 4 table.
- A link sequence and `PROJ1_2_COMPONENT_COUNTER` table. The material ID generator locks the category counter, accounts for saved/reserved/nested IDs, and reuses only the caller's unused reservation.

New LV2 component calls now match the live 17-argument procedure signature. Procedures use savepoints and leave transaction commit control to the application.

The installer backs up definitions before DDL, checks ambiguous legacy mappings, recompiles affected previously valid objects, and rejects compile errors:

```sh
docker compose exec -T laravel.test php scripts/apply-bom-sql.php
# Review the dry-run output, then apply:
docker compose exec -T laravel.test php scripts/apply-bom-sql.php --apply
```

Applied backup: `storage/app/bom-object-backups/SYSTEM_20261025_152253_58e05c.json` (mode 0600). This contains original view/procedure definitions; added columns, sequences and tables are additive and are not removed by restoring definitions.

## Verification

- Final read-only audit: all 55 project procedures, 56 views, 128 tables and 2 sequences are VALID; USER_ERRORS contains no compile errors. No project functions were present in that inventory. All six application flow readiness checks pass.
- Isolated Oracle XE integration: 26 checks pass, exercising actual procedure inserts, updates, deletes, source preservation, parent isolation, ID reservations and application procedure bindings. Installer reapplication also passes.
- Related PHPUnit regression: 33 tests / 288 assertions pass. JavaScript overview: 3 tests pass. Production frontend build passes.
- After the final dropdown correction, the affected component, business-supply and upgraded-authentication tests pass again: 18 tests / 86 assertions with `DB_CONNECTION=sqlite DB_DATABASE=:memory:`. Host PHP has no OCI8 extension, so running the authentication fixtures against the default Oracle connection is unsupported; real Oracle checks ran inside the PHP/OCI8 container.
- Local browser verification loads actual LV2 Existing source BOM options after selecting Brand/Mattype/Sub Mattype. Earlier FG selection verification also passed. No Save/Delete or ID-generation action was submitted against application data.
- Test DML ran only in an isolated disposable GTIN_QA Oracle schema, guarded by the verification script. No test records were inserted into the application's database. Some nested flows lack suitable real records for browser testing; they are covered by the isolated Oracle checks.
- The historical full PHPUnit Auth/Profile schema mismatches remain documented in `dependency-security-update.md`; the 33 passing tests above are the relevant regression set, not the complete suite.
