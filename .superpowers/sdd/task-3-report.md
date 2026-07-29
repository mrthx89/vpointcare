# Task 3 Report - inbox-whatsapp-improvement

## Scope

- OpenSpec: inbox-whatsapp-improvement
- Task: MIME detection, safe filename, and inline preview allowlist.
- Modified files: src/app/Support/WahaMediaPayload.php and src/tests/Unit/Support/WahaMediaPayloadTest.php.
- No controller, Livewire, Blade, OpenSpec task, dependency, migration, or lock file was changed.

## Implementation

- Added WahaMediaPayload::canPreviewInline() with a strict allowlist for JPEG, PNG, GIF, WebP, audio, video, and PDF.
- Resolved MIME in this order: declared metadata, payload or data URI metadata, filename extension through Symfony Mime, file signature through finfo_buffer, then application/octet-stream.
- Rejected MIME values containing control characters or invalid type/subtype syntax.
- Sanitized filenames by removing traversal paths, controls, quotes, excess whitespace, and leading/trailing dots or spaces; added MIME extensions and category fallbacks.
- Added tests for MIME precedence, extension and signature fallback, WebP stickers, voice notes, unknown binary, Unicode filename safety, and SVG/HTML/unknown inline rejection.

## TDD Evidence

- RED: vendor/bin/phpunit --filter WahaMediaPayloadTest failed before implementation with 5 assertion failures and 1 undefined-method error.
- GREEN: the same command passed after implementation.

## Validation

- php -l app/Support/WahaMediaPayload.php - passed.
- php -l tests/Unit/Support/WahaMediaPayloadTest.php - passed.
- vendor/bin/phpunit --filter WahaMediaPayloadTest - passed: 39 tests, 182 assertions.
- vendor/bin/pint --test app/Support/WahaMediaPayload.php tests/Unit/Support/WahaMediaPayloadTest.php - passed.

## Concern

- PHPUnit reports 1 pre-existing deprecation while this test class runs; it does not fail the suite. No unrelated change was made to address it.

## Concern Resolution

- Root cause: finfo_close() in WahaMediaPayload::mimeFromContents() is deprecated in PHP 8.5 because finfo objects are freed automatically.
- Resolution: removed the explicit finfo_close() call without changing MIME detection behavior.
- php -l app/Support/WahaMediaPayload.php - passed.
- vendor/bin/phpunit --filter WahaMediaPayloadTest --display-deprecations - passed: 39 tests, 182 assertions, no deprecations.
- vendor/bin/pint --test app/Support/WahaMediaPayload.php tests/Unit/Support/WahaMediaPayloadTest.php - passed.

## Review Fix - Long Filename Extension

- Root cause: a generated MIME extension was appended before the final 180-character truncation, allowing .pdf to be truncated away.
- Resolution: truncate the extensionless basename to reserve the dot and MIME extension before appending it.
- Added a regression test for a 180-character basename with application/pdf; the final filename is at most 180 characters and ends in .pdf.
- Validation: php -l passed for the helper and test, PHPUnit passed with 40 tests and 185 assertions without deprecations, and targeted Pint passed.
