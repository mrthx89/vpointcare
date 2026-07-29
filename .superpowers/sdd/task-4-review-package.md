# Review Package: Task 4 Final Re-review

## Base
HEAD f31f8c45628ac25d14149b2b8c02b03521b0fe99

## Status
Uncommitted changes only. No commit made per user instruction.

## Stat

## Diff

## Report
# Task 4 Report: WahaMediaController Feature Test RED

## OpenSpec

- Change: `inbox-whatsapp-improvement`
- Scope implemented: Task 4 only.
- Production files, routes, helper, Livewire, Blade, and OpenSpec task checklist were not changed.

## Files Created

- `src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php`
- `.superpowers/sdd/task-4-report.md`

## Test Coverage

- Creates and drops an SQLite in-memory `TChatD` table with only `Id`, `UrlMedia`, `PayloadJson`, `NamaFileMedia`, `TipeMime`, and `JenisPesan`.
- Uses the internal `App\Models\Master\Pengguna` model with `actingAs()`; no `users` model or SQL Server migration is used.
- Covers embedded PayloadJson fallback, `download=1`, URL failure fallback, WAHA request API-key header, authenticated/guest access, missing IDs, generic unavailable media, response secrecy, HTML/SVG attachment policy, CRLF filename safety, and safe upstream failure logging.

## Validation

From `src`:

```powershell
php -l tests/Feature/Http/Controllers/WahaMediaControllerTest.php
```

Result: PASS, no syntax errors.

```powershell
vendor/bin/phpunit --filter WahaMediaControllerTest
```

Result: cannot initialize SQLite because the default PHP CLI configuration does not load `pdo_sqlite` or `sqlite3`; all 9 tests error before the controller is reached with `could not find driver`.

The installed PHP distribution contains both extension DLLs, so the test was re-run without changing application configuration:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter WahaMediaControllerTest
```

Result: RED as intended: 9 tests, 14 assertions, 6 failures, and 1 logging assertion error; the guest and missing-ID protection tests pass.

## Confirmed RED Gaps

- Empty `UrlMedia` returns `404` instead of decoding `PayloadJson`.
- `download=1` does not reach an embedded payload and therefore cannot produce an attachment response.
- A `404` upstream URL returns `424` instead of falling back to valid embedded media.
- Invalid embedded media returns `404` instead of the generic `424` unavailable response.
- HTML and SVG responses omit `X-Content-Type-Options: nosniff` and are not forced to attachment.
- `NamaFileMedia` containing CRLF is emitted in `Content-Disposition`.
- The current upstream failure log does not satisfy the required safe event/context contract; Task 5 must emit only the approved metadata and reason code.

## Environment Note

- Enable `pdo_sqlite` and `sqlite3` in the PHP CLI `php.ini` to run the requested PHPUnit command directly. The test remains isolated from SQL Server and does not run WACS migrations.

## Deployment and Rollback

- No migration, queue, scheduler, Reverb, cache, or deployment action is required.
- Rollback consists of removing the two Task 4 files if the approved OpenSpec change is abandoned.

## Review Follow-up (2026-07-29)

- Safe-log coverage now captures the `Log::warning()` event and context, then requires an exact allowlist: `message_id`, `source`, `status`, and `reason_code`. Any additional key, including URL, signed URL/token, password, access token, API key, body, payload, base64, or exception message, fails the test.
- The invalid-all-sources case now includes a failed URL plus malformed payload and requires status `424`, `Content-Type: text/plain; charset=UTF-8`, a `Cache-Control` directive containing `no-store`, and the localized generic `ui.controllers.waha_media.unavailable` response. It also rejects URL/token, upstream body, payload/base64, exception, internal, and error details.
- Re-validation with temporary SQLite extensions: `9` tests, `20` assertions, `7` expected controller-contract failures, and `0` test setup or Mockery errors. Guest and missing-ID protection remain green.

## Second Review Follow-up (2026-07-29)

- Added a `ConnectionException` fixture for an HTTP media request. The test captures `Log::warning()` and requires exactly `message_id`, `source`, and `reason_code=upstream_exception`; no status is permitted because the connection failure has no HTTP status. Any URL, exception text, token, body, payload, base64, or API-key field causes the exact-context assertion to fail.
- The generic unavailable-response test now verifies that `ui.controllers.waha_media.unavailable` resolves to an actual translation in both `id` and `en` before comparing the Indonesian response body. The current language files do not define that key, so the RED test prevents a raw translation-key response from being accepted.
- Re-validation with temporary SQLite extensions: `10` tests, `23` assertions, `8` expected controller/localization contract failures, and `0` test setup or Mockery errors. Guest and missing-ID protection remain green.

