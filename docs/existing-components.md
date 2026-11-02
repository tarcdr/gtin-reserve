# BOM and component completion

Implemented and applied to the application's Oracle database on 2026-10-25.

## Completed flows

- FG, Semi FG LV1 and LV2 have separate New and Existing component sections.
- Existing LV1 BOMs are selected by Mattype/Sub Mattype; LV2 retains Brand/Mattype/Sub Mattype. Both can be viewed and detached. LV1 creation requires the FG’s New LV2 BOM; Existing LV2 attachments are excluded from parent selection. The parent BOM is retained internally and shown on the creation form; the FG summary card does not repeat it.
- Existing component selectors support FG, LV1, LV2 and nested Mattype 4. LV2 includes Product SUB Category.
- Mattype 4 supports nested New creation/edit/deletion and Existing attachment/view/deletion, with the complete parent BOM headers.
- Existing attachments preserve the source BOM. They have no update operation. Nested Mattype 4 mutations through a reused Existing LV1 remain read-only because the nested tables have no FG key to isolate source modifications.

Selectable source BOMs use actual saved BOM/ID pairs. LV1 now reads only `PROJ1_2_SEMI_FG_LV1_ID_V`; the former LOV/ERP merge described in the historical 2026-11-01 notes below has been superseded. Sub Mattype choices come from the same canonical source views, since the older LOV can be empty. Category filtering uses actual master fields: combined MAT_TYPE (for example 50) and product SUB_TYPE, rather than guessing from material IDs.

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

## Component refinements — 2026-11-01

- FG Components and the single Raw Material Components card show Existing before New. Raw Material row actions and ownership/status checks remain in place.
- Semi FG LV1/LV2 New and Existing tables share fixed columns (6%, 24%, 38%, 10%, 22%) and a 960px minimum width, with horizontal scrolling on smaller screens.
- Existing Component Save, Back and Delete for `semi1`/`semi2` return to the canonical owning Semi FG detail page in View mode. Existing BOM attachments still return to FG. Validation errors preserve form input.
- LV1 options merge the native `PROJ1_2_SEMI_FG_L1_ID_EXIST_LS` catalog with saved DML BOMs. ERP master attributes take priority over the source FG brand. Split and combined master material category fields are supported; material IDs are not parsed for filtering.
- Options include `selectable` and `unavailableReason`. A catalog material without a matching source BOM is visible but disabled with “ไม่มี BOM ต้นทาง”; the server also rejects its submission. Multiple real BOMs remain separate choices.
- No database definition or business-record changes were needed. Read-only local Oracle inspection found neither `30SW0001` nor other `30SW%` materials in the native LOV, detail view or ERP master; the screenshot's exact record cannot be verified against this local dataset. Fixtures cover that record's missing-BOM and valid/multiple-BOM cases.
- Regression tests cover canonical return URLs and validation retention, missing BOMs, ERP-only records, category/brand filtering, source brand discrepancies and forged submissions. Browser verification was unavailable because the browser access host could not be reached.

## Semi FG LV1 BOM detail flow — 2026-11-01

- Creating or updating a New Mattype 4 BOM Component now redirects to that component's own Detail (`actionMode=view`), with canonical FG/LV2/LV1 context and the saved Component ID.
- Detail fields are read-only; Edit/Delete actions use the stored creator/status and parent completion gates. Edit/confirm-delete Back returns to Component Detail; Detail Back and successful deletion return to the owning Semi FG LV1.
- Saved views reload descriptions, categories and parent headers from Oracle rather than URL field values. Category/ID fields stay locked, and missing/foreign components or unauthorized mutations are rejected. Validation failures retain form input.
- The LV1 page remounts its form when mode/component changes, so an Inertia redirect after Save cannot retain editable Create/Edit state. The shared form has an optional detail-action slot used only by the LV1 BOM Component page; other BOM pages keep their existing behavior.
- Regression: 51 tests / 516 assertions pass, including nine dedicated LV1 flow tests with simulated procedure writes in SQLite. Production build passes. No schema/procedure changes or application-data test writes were made.
- Read-only Oracle checks of the screenshot's `400200004` return the correct Detail/Edit component and parent path (`B11BR00012_RK_01` → `B20BR00007_RK_01` → `B30BR0001_RK_01`). Browser screenshot verification remains unavailable because the browser access host cannot be reached.

### Detail/Edit layout

The LV1 BOM Component Detail now groups read-only component fields (including UOM and Status), embedded Raw Material Components (Existing before New), and then Back/Edit/Delete inside the same form card. Edit shows only the main component form; Raw Material management is mounted only in Detail. Optional form slots preserve other BOM pages' layouts. Five server-rendered React tests verify section order, read-only/editable fields, mode-specific content, disabled actions and shared-form defaults. All five pass, alongside the 51 PHP regression tests and production build. Browser access remains unavailable, so desktop/mobile screenshot verification is not claimed.

## Existing LV1, canonical parents and menu settings — 2026-11-02

This revision supersedes the LV1 LOV/ERP merge and Brand filtering described above.

- Add Existing LV1 BOM uses Mattype 3 and Sub Mattype (including `0`), without Brand. A single source query reads `PROJ1_2_SEMI_FG_LV1_ID_V`; its correlated `NOT EXISTS` checks `PROJ1_2_DML_SEMI_L1_ID_EXIST.SEMI_FG_LV1_ID_EXIST` plus the target FG/LV2 BOMs. Multiple source BOMs remain separate choices until that material is attached to the same parent. Save repeats this query using database-validated parents. The procedure enforces the same duplicate rule.
- LV1 parent resolution uses stored BOM/Material foreign keys and the full FG/LV2/LV1 path. Missing or ambiguous mappings produce a parent validation error. BOM identifiers are never derived from Material identifiers. New Component Save redirects to its own Detail; the same canonical path is used on reload and Existing Component navigation.
- Both Semi FG component lists place Existing before New, retaining shared column widths and horizontal overflow. Existing add buttons use the same white/green outlined style as FG.
- Account Management exposes admin-only Enable-TAB/Disable-TAB controls. `PATCH /admin/settings/export-to-sap-tab` stores the global flag in `PROJ1_2_APP_SETTINGS`, with updater and timestamp. Missing settings default to enabled. Inertia shares `features.exportToSapTabEnabled` for desktop/mobile menus. Disabling hides navigation only; Export routes and permissions are unchanged.

### Installation and rollback evidence

The additive migration `2026_11_02_000001_create_app_settings_table.php` creates/seeds settings without resetting an existing value. The focused Oracle installer also installs the corrected LV1 procedure:

```sh
docker compose exec -T laravel.test php scripts/apply-bom-sql.php --refinements
docker compose exec -T laravel.test php scripts/apply-bom-sql.php --refinements --apply
```

Applied locally on 2026-11-02. Pre-apply definition backup: `storage/app/bom-object-backups/SYSTEM_20261102_154410_67a796.json`. The installer reported all changed procedures/views VALID. Read-only application checks confirmed LV1 readiness and the default enabled menu flag. The table and initial configuration seed are additive; restoring a procedure definition does not remove settings. No legacy business records were repaired or moved.

### Verification and limits

- Related PHPUnit regression: **71 tests / 669 assertions pass** with SQLite fixtures and simulated procedure writes. Coverage includes no Brand, subtype zero, multiple source BOMs, parent-specific duplicate exclusion, forged submissions, Material `30BS0001` mapped to BOM `B30000008_RK_01`, missing/ambiguous parents, Component Detail round trips, authorization, audit fields and persistent menu settings.
- React server-render tests: **14 pass**, including section order, matching columns, Detail/Edit separation, View/Delete separation and both navigation variants. Production build passes (Vite reports an existing CSS minification warning for `-: T`).
- Browser access host is unavailable, so live desktop/mobile screenshot verification and a browser Save/Refresh round trip are not claimed. Application-database business writes were not used for tests. The local Oracle dataset has no matching source options for the screenshot's tested LV1 parent/filter combination; fixture tests cover these cases instead.
- App and Oracle containers were already running when this task began and remain running.

## Oracle parent lock correction — 2026-11-02

The reported Existing Component Save failed before the procedure call with ORA-02014. `limit(2)->lockForUpdate()` caused the installed Oracle grammar to wrap the parent query in a ROWNUM pagination inline view and append FOR UPDATE to that view.

`BomContextService` now uses unpaginated `lockForUpdate()->get()` when locking BOM/ID rows, then rejects ambiguous results. Read-only lookups retain `limit(2)`. The shared `ExistingComponentService` parent helper follows the same pattern, rejects duplicate parents, and preserves missing-parent errors. Existing predicates, transactions, status/ownership gates, APIs and business procedures are unchanged.

Verification:

- Related regression: **80 tests / 729 assertions pass**, including nine Oracle grammar tests that compile real service queries without OCI/network. These assert unwrapped FOR UPDATE SQL, complete FG/LV2/LV1 bindings, bounded read-only queries, and missing/duplicate-parent rejection.
- **14 Oracle integration checks pass** in a newly created, isolated `GTIN_LOCK_QA` schema in XEPDB1. The old query reproduces ORA-02014; the corrected helper holds row locks against a second independent Oracle session (ORA-00054 with NOWAIT). Actual copied application procedures save Existing components for LV1, LV2 and nested Raw Material. Controller redirects target View mode and saved Existing detail reloads twice successfully.
- Integration uses copied table/procedure definitions, synthetic LOVs and fixture rows only. No application-schema DDL or business-data writes occur. This verifies controller responses/data reloads, not browser screenshots or full owning-page rendering.
- The disposable QA schema was dropped after verification; DBA_USERS confirmed zero remaining `GTIN_LOCK_QA` users. Existing application containers remain running.

Reproduction helper: `scripts/verify-parent-lock-oracle.php`. It requires explicit `GTIN_LOCK_QA=1`, a `GTIN_LOCK_QA_PASSWORD`, and a freshly provisioned `GTIN_LOCK_QA` user with CREATE SESSION/TABLE/VIEW/PROCEDURE/SEQUENCE and quota in XEPDB1. It refuses a different user/PDB or nonempty schema, copies only source definitions, and switches application services to QA before fixture writes. Use temporary test credentials, run inside the app container, then drop that disposable schema. It does not apply migrations or modify application objects.
