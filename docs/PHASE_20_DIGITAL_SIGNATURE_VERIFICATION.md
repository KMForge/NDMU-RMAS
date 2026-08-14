# Phase 20 — Digital Signature Verification

## Status
- **Phase Status**: Completed
- **Phase Completion**: 100%
- **Verification Date**: August 15, 2026

## Repository Baseline
- **Repository**: `NDMU-RMAS`
- **Branch**: `main`
- **Starting HEAD**: `be5054b2ca2e9c9b713829d03a178ee617991271`
- **Ending HEAD**: `be5054b2ca2e9c9b713829d03a178ee617991271`
- **Working Tree**: Clean

## Scope
Phase 20 provides end-to-end digital signature capabilities for NDMU-RMAS:
1. User signature specimen enrollment, transparent background normalization, and secure private storage.
2. Server-derived academic actor identity mapping and fail-closed form action authorization.
3. Transactional signed action orchestration with optimistic/pessimistic version locking and snapshot preservation.
4. Deterministic canonical version hashing and secret-keyed HMAC-SHA256 attestation.
5. Public verification pages, Inline SVG QR code generation via `chillerlan/php-qrcode`, and privacy-preserving headers.
6. Support for RES-049 multi-researcher authorship attestation without state transition.
7. Print template integration rendering exact applied signature snapshots for active and historical versions.

## Architecture Overview
NDMU-RMAS integrates digital signature verification directly into the authoritative Phase 19 Official Form engine. When an academic user (Adviser, Instructor, Dean, Editor, Validator, or Student Researcher) performs a signed action, the system atomically:
- Validates current form version (`expected_version_id === current_version_id`).
- Derives the exact required `actor_type` server-side via `OfficialFormAuthorization::requiredActorType()`.
- Verifies user signature enrollment eligibility (`isEligibleForSignatureEnrollment()`) and specimen content integrity (`content_sha256`).
- Previews signature specimen and writes an immutable PNG snapshot to private storage (`storage/app/private/official_form_signatures/`).
- Executes the Phase 19 state transition action (`ApproveOfficialForm` / `CertifyOfficialForm`) or records attestation (RES-049).
- Computes canonical version payload SHA256 and secret-keyed HMAC-SHA256 attestation hash.
- Creates or reuses a public verification record (`official_form_verifications`) with a unique public UUID reference.

## Signature Enrollment
Users manage their signature specimen at `/settings/signature`.
- Uploaded files are validated for size (max 2048 KB) and extension (`png`, `jpg`, `jpeg`).
- The image is processed through `UserSignatureNormalizer`.
- Specimen files are stored in `storage/app/private/signatures/{user_id}/{uuid}.png`.
- Specimen metadata (`storage_disk`, `storage_path`, `content_sha256`, `registered_at`) is saved in `user_signatures`.
- All enrollment actions (`registered`, `replaced`, `removed`) are recorded in `signature_audits` and `audit_logs`.

## Eligibility Rules
- **Eligible Account Types**: `UserType::Student`, `Faculty`, `Panelist`.
- **Ineligible Account Types**: `UserType::Admin` system administration accounts.
- **Account Status Requirements**: User must be active (`is_active = true`), approved, and email verified.
- Base Admin accounts (`users.manage`) are explicitly denied academic signature enrollment.

## Private Storage
- Enrolled signature specimens and applied form signature snapshots are stored exclusively on the private disk (`storage/app/private/`).
- Files are never placed in `public/` directories or exposed via static asset URLs.
- Access to raw specimen preview and applied signature snapshots is controlled strictly through authenticated controller endpoints (`UserSignatureController::preview` and `OfficialFormSignatureController::image`).

## PNG Normalization
`UserSignatureNormalizer` enforces standard specimen image formatting:
- Accepts raw PNG and JPEG binary streams.
- Converts dark/white backgrounds into transparent backgrounds.
- Resizes images while preserving aspect ratio (max dimensions: 600px width x 200px height).
- Strips EXIF metadata to prevent privacy leaks.
- Re-encodes output strictly as standard 8-bit PNG binary.
- Includes a binary PNG fallback parser if `ext-gd` hardware extension is unavailable.

## Enrollment Audit
Every enrollment modification generates structured audit records:
- `signature_audits` table: captures `user_signature_id`, `user_id`, `action` (`registered`, `replaced`, `removed`), `ip_address`, `user_agent`, and `occurred_at`.
- `audit_logs` table: logs system audit trails with `subject_snapshot` containing `content_sha256`.

## Applied Signature Model
Applied signatures are stored in `official_form_signatures`:
- `official_form_version_id`: Foreign key to exact `official_form_versions` record.
- `signer_user_id`: User ID of the signer.
- `signer_name`: Authoritative full name at time of signing.
- `signer_email`: Authoritative email at time of signing.
- `actor_type`: Server-derived academic role (`adviser`, `research_instructor`, `program_coordinator`, `dean`, `instrument_validator`, `language_editor`, `technical_editor`, `student_researcher`).
- `academic_action`: Action name (`endorse`, `receive`, `approve`, `certify`, `validate`, `sign_authorship`).
- `signature_storage_disk` & `signature_storage_path`: Path to immutable signature snapshot.
- `specimen_sha256`: SHA256 hash of the signature specimen at signing time.
- `version_payload_sha256`: Canonical SHA256 hash of the form version payload.
- `attestation_hash`: HMAC-SHA256 cryptographic signature.
- `attestation_key_version`: Secret key version tag (`v1`).
- `signed_at`: Timestamp of signature application.

## Verification Record Model
Public document verifications are stored in `official_form_verifications`:
- `official_form_version_id`: Unique foreign key to `official_form_versions`.
- `public_reference`: Public UUID reference (`GET /official-forms/verify/{reference}`).
- `issued_at`: Creation timestamp.

## Exact Version Binding
Signatures are bound strictly to an exact immutable `OfficialFormVersion`:
- Signatures on Version 1 belong exclusively to Version 1.
- When Version 2 is created, Version 2 starts unsigned.
- Historical Version 1 retains all original applied signatures and remains publicly verifiable.

## Server-Derived Actor Identity
All signing requests reject client-supplied `actor_type` input:
- `OfficialFormWorkspaceController::signAction` accepts ONLY `expected_version_id`.
- `ApplyOfficialFormSignature::handle` derives `$actorType` via `OfficialFormAuthorization::requiredActorType($lockedInstance, $academicAction)`.
- Attempting to pass custom or spoofed `actor_type` in request payloads results in HTTP validation failure or authorization rejection.

## Supported Signed Action Matrix
Only verified academic form actions allow signature attestation:

| Form Code | Action | Required Actor Type | Target Workflow Status |
| :--- | :--- | :--- | :--- |
| **RES-040** | `endorse` | `adviser` | `endorsed` |
| **RES-040** | `receive` | `research_instructor` | `approved` |
| **RES-041** | `endorse` | `research_instructor` | `endorsed` |
| **RES-041** | `receive` | `program_coordinator` | `approved` |
| **RES-043A** | `validate` | `instrument_validator` | `completed` |
| **RES-043B** | `validate` | `instrument_validator` | `completed` |
| **RES-045** | `certify` | `language_editor` | `completed` |
| **RES-046** | `certify` | `technical_editor` | `completed` |
| **RES-047** | `endorse` | `adviser` | `endorsed` |
| **RES-047** | `approve` | `dean` | `approved` |
| **RES-049** | `sign_authorship` | `student_researcher` | *No transition (Attestation only)* |

Any unsupported form code or action combination fails closed with an `InvalidArgumentException`.

## RES-049 Authorship Attestation
- Form RES-049 allows student researchers to sign authorship attestations (`sign_authorship`).
- Authorized signers must be enrolled as active members of the research group (`isCurrentGroupMember()` matching `student_id`).
- RES-049 signing does NOT require an explicit `OfficialFormActorAssignment`.
- RES-049 signing is **attestation-only** and does not alter the `OfficialFormInstance` workflow status.

## Phase 19 Authorization Reuse
Phase 20 signature enforcement reuses the authoritative `OfficialFormAuthorization` policy engine:
- Verifies user permissions (`FORM_ACTION_PERMISSIONS`).
- Verifies academic actor assignment or contextual role (`FORM_ACTION_ACTOR_TYPES`).
- Verifies ownership and group/class context (`checkSpecificActorTypeContext`).

## Canonical Version Hash
`OfficialFormSignatureHasher::hashVersion()` computes deterministic canonical version hashes:
- Extracts form code, instance ID, version ID, version number, creator ID, creation timestamp, and normalized payload array.
- Sorts payload keys recursively to guarantee deterministic JSON output.
- Computes SHA256 hash of the canonical JSON representation.

## Signature Snapshot Hash
When a signature is applied, a raw binary copy of the user's enrolled signature specimen is saved as a snapshot. The SHA256 hash of these exact bytes is recorded in `specimen_sha256`.

## HMAC Attestation
The attestation signature is computed using HMAC-SHA256:
```php
hash_hmac('sha256', implode('|', [
    $instanceId,
    $versionId,
    $versionNumber,
    $signerUserId,
    $actorType,
    $academicAction,
    $payloadSha256,
    $specimenSha256,
    $keyVersion,
    $signedAtTimestamp,
]), $secretKey);
```

## Verification Key / Key Version
- Secret key is configured via `SIGNATURE_VERIFICATION_KEY` in environment config (`config/signatures.php`).
- Key version tag defaults to `v1` (`attestation_key_version`).
- Verification evaluation uses active system key. Rotating keys requires a documented compatibility strategy.

## QR Verification
- QR codes are generated using `OfficialFormQrCodeGenerator` powered by `chillerlan/php-qrcode`.
- The QR code contains the full public verification URL (`https://.../official-forms/verify/{public_reference}`).
- Output is rendered as Inline SVG directly inside print and verification Blade templates.

## Public Verification Privacy
The public verification endpoint (`GET /official-forms/verify/{reference}`) strictly respects user privacy:
- **Exposed Information**: Form title, form code, version number, verification status, signer printed names, actor titles, academic actions, and signed timestamps.
- **Hidden Information**: Payload JSON, student IDs, faculty IDs, internal database primary keys, group/class rosters, storage paths, raw signature images, and HMAC keys.
- **Privacy Headers**: Sends `X-Robots-Tag: noindex, nofollow, noarchive` and HTML `<meta name="robots" content="noindex, nofollow">`.
- **Unknown Reference**: Returns standard 404 response without leaking system state.

## Verification Statuses
`OfficialFormVerificationService::evaluate()` derives four verification states:
1. `VALID_CURRENT`: Current form version with intact signatures and valid HMAC attestation.
2. `VALID_HISTORICAL`: Non-current (historical) form version with valid signatures and intact HMAC attestation.
3. `TAMPERED`: Payload, version, or signature HMAC mismatch detected.
4. `INVALID_SPECIMEN`: Signer specimen integrity check failed.

## Current vs Historical Versions
- Active versions display `VALID_CURRENT` verification badges.
- Historical versions display `VALID_HISTORICAL` verification badges indicating the document was signed at that historical point in time.

## Multiple Signatures
Documents supporting multiple signers (e.g. RES-049 with multiple student authors, or RES-047 with adviser and dean) store distinct signature records per signer. Verification evaluates all applied signatures; if any single signature fails validation, the overall document verification reflects the tamper/invalid status.

## Concurrency Protections
- `ApplyOfficialFormSignature::handle` executes inside a database transaction with pessimistic locking (`lockForUpdate()`).
- Stale version checks prevent concurrent edits from overwriting signatures.
- Database unique constraints prevent race conditions on duplicate signature creation.

## Duplicate Prevention
Unique index on `official_form_signatures`:
`[official_form_version_id, signer_user_id, actor_type, academic_action]`
Prevents duplicate signature attestations by the same user for the same action on the same version.

## IDOR Protection
`OfficialFormSignatureController::image` checks user authorization against the parent `OfficialFormInstance`:
- Users can view signature snapshots only if authorized to view the underlying form instance.
- Prevents guessing signature IDs to view private signature images.

## Print Integration
- Print template (`resources/views/pages/official-forms/print.blade.php`) renders applied signature snapshots for the target version.
- Unsigned actions display pending signature lines.
- QR code and public verification reference are embedded on printed documents.

## Routes

| Method | URI | Controller Action | Name |
| :--- | :--- | :--- | :--- |
| `GET` | `/settings/signature` | `UserSignatureController::edit` | `signature.edit` |
| `PUT` | `/settings/signature` | `UserSignatureController::store` | `signature.store` |
| `DELETE` | `/settings/signature` | `UserSignatureController::destroy` | `signature.destroy` |
| `GET` | `/settings/signature/preview` | `UserSignatureController::preview` | `signature.preview` |
| `POST` | `/official-forms/workspace/{instance}/sign/{action}` | `OfficialFormWorkspaceController::signAction` | `official-forms.workspace.sign` |
| `GET` | `/official-forms/signatures/{signature}/image` | `OfficialFormSignatureController::image` | `official-forms.signatures.image` |
| `GET` | `/official-forms/verify/{reference}` | `OfficialFormVerificationController::verify` | `official-forms.verify` |

## Database Tables
1. `user_signatures`
2. `signature_audits`
3. `official_form_signatures`
4. `official_form_verifications`

## Environment Variables
- `SIGNATURE_DISK=local`
- `SIGNATURE_VERIFICATION_KEY=...`
- `SIGNATURE_VERIFICATION_KEY_VERSION=v1`

## Tests
Focused signature and official form test suites:
- `tests/Feature/Signatures/UserSignatureEnrollmentTest.php` (5 tests, 41 assertions, PASSED)
- `tests/Feature/Signatures/UserSignatureSecurityTest.php` (6 tests, 14 assertions, 1 skipped, PASSED)
- `tests/Feature/OfficialForms/OfficialFormSignatureTest.php` (12 tests, 37 assertions, PASSED)
- `tests/Feature/OfficialForms/OfficialFormVerificationTest.php` (6 tests, 26 assertions, PASSED)
- `tests/Feature/OfficialForms/` directory (76 tests, 376 assertions, PASSED)

## Full Regression
Full test suite output (`php artisan test`):
- **Total Tests**: 338
- **Passed**: 314
- **Skipped**: 24
- **Failures**: 0
- **Errors**: 0
- **Assertions**: 1,465
- **Exit Code**: 0 (`PASSED`)

## Build / Quality Gates
- `vendor/bin/pint --test`: PASSED
- `npm run build`: PASSED (Vite build completed in 7.72s)
- `php artisan view:cache`: PASSED (Blade templates cached)
- `php artisan migrate:status`: PASSED (All 38 migrations ran)
- `composer.json` syntax: PASSED

## Known Limitations
1. Signature rotation requires manual re-indexing if key secret changes.
2. Binary fallback converter is used in CLI environments where `ext-gd` is not loaded.

## Phase Boundaries
Phase 20 strictly owns digital signature verification and user signature enrollment. Defense scheduling, rubrics, and formal evaluation workflows belong to Phase 21.

## Definition of Done
All 38 Phase 20 completion gates have been verified and passed.

## Ready for Phase 21
**YES**. Phase 20 implementation is complete, fully tested, documented, and committed. Phase 21 (Defense Scheduling) is now the active feature phase.
