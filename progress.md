# Smart Medicine Box — Progress

Laravel 12 web app (SQLite) that acts as the control interface for an ESP32-based
medicine reminder device (6 compartments, buzzer, reed switch lid sensor, Wi-Fi/HTTP JSON API).

## Done so far

### Database
- `device_settings` — `esp32_ip_address`, `operating_mode` (enum: `dose_mode`/`medicine_mode`),
  `missed_dose_timeout_minutes` (default 10). Single-row table (settings singleton, id=1).
- `dose_schedules` — `compartment_number` (1-6), `compartment_label`, `reminder_time`, `is_enabled`.
- `medicine_schedules` — `name`, `compartments` (json array of compartment numbers), `reminder_time`, `is_enabled`.
- Models: `DeviceSetting`, `DoseSchedule`, `MedicineSchedule` — all have `$fillable` + `$casts`
  (`compartments` casts to array, booleans cast properly).
- Seeders: `DeviceSettingSeeder` (one default row), `DoseScheduleSeeder` (all 6 fixed compartments:
  Before/After Breakfast, Before/After Lunch, Before/After Dinner). Both wired into `DatabaseSeeder`.

### Esp32Service (`app/Services/Esp32Service.php`)
No longer stubbed — makes real HTTP calls via the `Http` facade (Guzzle under the hood) to the
ESP32's built-in server, base URL built from `device_settings.esp32_ip_address`
(`http://{ip}`, no path prefix — e.g. `esp32_ip_address = 192.168.1.50` → `http://192.168.1.50`).
Every call has a 5s timeout.

- `getStatus()` → GET `/status` → on success returns the device's raw JSON
  (`{ connected, mode, status, missed_dose_timeout }`) with `success: true` merged in.
- `testConnection()` → GET `/ping` → returns bool (`$response->successful()`), never throws.
- `sendMode(string $mode)` → POST `/set-mode`, body `{ mode }`.
- `sendDoseSchedules(array $schedules)` → POST `/set-dose-schedules`, body `{ schedules }`
  (wraps whatever array it's given — see gap note below about what `DoseModeController` actually sends).
- `sendMedicineSchedules(array $schedules)` → POST `/set-medicine-schedules`, body `{ schedules }`.
- `sendTimeout(int $minutes)` → POST `/set-timeout`, body `{ timeout_minutes }`.
- `syncTime()` → POST `/sync-time`, body `{ timestamp: <unix>, datetime: "Y-m-d H:i:s" }`.
- `restartDevice()` → POST `/restart`, empty body.

**Failure contract**: every method (except `testConnection()`, which just returns `false`) returns
`{ success: false, error: "<message>" }` on any network failure or non-2xx response — never
throws out to callers, never crashes a request. On success, all the `send*`/`sync*`/`restart*`
methods return `array_merge(['success' => true], $response->json())`, so the device's own response
body is preserved but every caller can uniformly check `$result['success']`.

**Internals**: a private `request(string $method, string $path, array $payload = [])` helper does
the actual `Http::timeout(5)->{$method}(...)` call and throws `App\Exceptions\Esp32ConnectionException`
(new custom exception, see below) on network failure or `$response->failed()`. Every public method
wraps its call to `request()` in a `try/catch (Esp32ConnectionException $e)` and converts it to the
`{success: false, error}` array, logging via `Log::warning()`. This keeps the custom exception
meaningful (it's genuinely thrown and caught, not decorative) while preserving the "never throws to
the controller" contract every existing controller already relies on (`if (! $result['success'])`).

**Esp32ConnectionException** (`app/Exceptions/Esp32ConnectionException.php`) — plain `Exception`
subclass with a message + optional previous exception. As defense-in-depth (since the service
never actually lets it escape under normal operation), `bootstrap/app.php` registers a global
`$exceptions->render()` handler for it: JSON requests get `{success: false, error}` with a 503
status, normal requests get `back()->with('error', $e->getMessage())` — so even if a future code
path lets it bubble uncaught, the app degrades gracefully instead of showing Laravel's default 500
page. Verified this actually fires correctly (both the JSON and redirect-with-flash branches) by
temporarily adding a route that throws it directly, hitting it with curl, and confirming the flash
message rendered on a page that displays `session('error')` — then removed the test route.

**How this was verified** (no real ESP32 exists, so this needed more than curl-against-the-app):
wrote a throwaway Node HTTP server (`fake-esp32.js`, not committed) implementing all 7 endpoints
with realistic JSON responses, pointed `device_settings.esp32_ip_address` at it, and called every
`Esp32Service` method directly via `tinker` — confirmed exact request bodies received (matched the
spec byte-for-byte, e.g. `{"mode":"medicine_mode"}`, `{"timeout_minutes":20}`,
`{"timestamp":...,"datetime":"..."}`) and correct response parsing. Then pointed at a closed port
to force real connection failures and confirmed every method returned the structured error shape
with no crash, and that failures were logged. Then re-ran the full set of pages (Mode Selection,
Dose Mode, Medicine Mode sync, Settings via tinker since the port-suffixed test address fails the
`ip` validation rule as expected, Device Controls' three actions, Live Status poll) against the
fake server over real HTTP and confirmed success messages/flags now flow through correctly instead
of always reporting "could not be reached". Reset `device_settings` back to the seeded IP
afterward.

### Pages built (each: Controller + Blade view + named routes)

**Layout** — `resources/views/layouts/app.blade.php`: Tailwind (CDN), left sidebar with links to
all 7 planned sections. Only routes that exist are clickable (checked via `Route::has()`); others
render as `#` until built. Active link highlighted via `request()->routeIs()`.

**Dashboard** (`/dashboard`, route `dashboard`) — `DashboardController::index()`.
Shows connection badge (green/red), operating mode, missed-dose timeout, device status badge
(color-coded per status value). "Test Connection" button does an AJAX GET to
`esp32.test-connection` (`DashboardController::testConnection()`) and swaps in the result with no
page reload.

**Mode Selection** (`/mode-selection`, route `mode-selection` / `mode-selection.update`) —
`ModeController`. Two large radio-card selectors (Dose Mode / Medicine Mode) with icon + description,
live highlight on selection (Tailwind `peer-checked`), "Active" badge reflects the actually-saved
mode. `update()` validates, saves to `device_settings`, calls `Esp32Service::sendMode()`, flashes
success/error back via `session()`.

**Dose Mode** (`/dose-mode`, route `dose-mode` / `dose-mode.update`) — `DoseModeController`.
Table of all 6 compartments (number, label, `<input type="time">`, Tailwind toggle switch for
`is_enabled`), all submitted together as one form (`schedules[{compartment_number}][reminder_time|is_enabled]`).
`update()` validates (`schedules` array of exactly 6, each with `date_format:H:i` time; `is_enabled`
read via `$request->boolean()` per compartment since unchecked checkboxes aren't submitted at all),
saves each row, calls `Esp32Service::sendDoseSchedules()` with the fresh full set, flashes
success/error.

**Medicine Mode** (`/medicine-mode`, full CRUD) — `MedicineModeController`. Unlike Dose Mode
(fixed 6 rows edited in one form), this is a real resource: list, create, edit, delete individual
medicine schedules, each spanning any subset of the 6 compartments (`compartments` json array), plus
a separate "Save All to ESP32" action.
- Routes: `medicine-mode` (index), `medicine-mode.create`, `medicine-mode.store`,
  `medicine-mode.edit`, `medicine-mode.update`, `medicine-mode.destroy`, `medicine-mode.sync`
  (POST, calls `Esp32Service::sendMedicineSchedules()` with all rows, flashes success/error —
  separate from individual save/update, matches the "sync everything" pattern used for Mode
  Selection and Dose Mode).
- Views: `resources/views/medicine-mode/{index,create,edit}.blade.php` +
  `medicine-mode/_form.blade.php` (shared partial for the name/compartments/time/enabled fields,
  included by both create and edit — the two forms are otherwise identical).
- Compartments picked via 6 checkboxes styled as toggle buttons (Tailwind `has-[:checked]`).
- `edit($id)`/`update($request, $id)`/`destroy($id)` use `MedicineSchedule::findOrFail($id)`
  (plain ID lookup, not route-model binding) per the requested controller signatures.

**Settings** (`/settings`, route `settings` / `settings.update`) — `SettingsController`.
Single form: `esp32_ip_address` (validated with Laravel's `ip` rule) and
`missed_dose_timeout_minutes` (integer 1–60). `update()` saves both to `device_settings` then
calls `Esp32Service::sendTimeout()` with the new value, flashes success/error.

**Device Controls** (`/device-controls`) — `DeviceControlsController`. Three independent action
cards, each pure AJAX via `fetch()`, no page reload:
- **Sync Time** — POST `device-controls.sync-time` → `Esp32Service::syncTime()`, shows `{success, message}`.
- **Restart ESP32** — POST `device-controls.restart` → `Esp32Service::restartDevice()`, gated
  behind a JS `confirm()` dialog before firing, shows `{success, message}`.
- **Refresh Status** — GET `device-controls.refresh-status` → `Esp32Service::getStatus()`, renders
  every key/value from the returned status object directly (works whatever shape `getStatus()`
  mock data happens to be at the time).
- Added a `<meta name="csrf-token">` tag to `layouts/app.blade.php` `<head>` — needed since these
  are `fetch()` POSTs, not form submits, so the CSRF token has to come from JS reading the meta tag
  rather than a `@csrf` hidden field.

**Live Status** (`/live-status`) — `LiveStatusController`. `index()` server-renders the initial
status card; `poll()` (GET `live-status.poll`) returns raw `Esp32Service::getStatus()` JSON,
polled by the view every 5s via `setInterval` + `fetch()`, updating the card, connection badge,
mode, and timeout in place (no reload).
- Status color coding: Ready → blue, Medicine Time → amber + `animate-pulse`, Medicine Taken →
  green, Missed Dose → red. Class names are swapped wholesale on each poll rather than toggled
  incrementally.
- Browser notifications: requests `Notification` permission on load (only if not already
  decided), tracks `previousStatus` in a JS closure variable seeded from the server-rendered
  initial status (so a poll that returns the *same* status as first paint doesn't fire), and only
  calls `notify()` when the polled status differs from `previousStatus` — so repeated polls
  returning the same status (e.g. "Medicine Time" three polls in a row) fire exactly one
  notification, not three. Title/body text matches the spec exactly for the three trigger states.
- This is the last of the 7 sidebar sections — every nav link now resolves to a real page.

All seven pages were verified with real HTTP requests (dev server + curl, including full
CSRF-token PUT/POST/DELETE cycles), not just code review — for Dose Mode this included confirming
a changed time and an unchecked/disabled compartment both persisted and re-rendered correctly; for
Medicine Mode the full create → edit (compartments changed, disabled) → sync → delete → empty-state
→ validation-error lifecycle was driven end-to-end and checked against the DB at each step; for
Settings, both the happy path and an invalid-IP/out-of-range-timeout submission were confirmed
(errors flashed, DB left untouched); for Device Controls, all three JSON endpoints were hit
directly and confirmed to return the expected `{success, message}` / `{success, status}` shapes.
For Live Status, since curl can't drive real DOM/JS, the actual rendered `<script>` block was
extracted from the live HTTP response and executed under Node with DOM/fetch/Notification stubs —
a scripted 5-poll sequence (Ready → Medicine Time ×2 → Medicine Taken → Missed Dose) confirmed the
final UI state matched the last poll and exactly 3 notifications fired (not 5), and each of the 4
statuses individually was confirmed to produce the correct card color/pulse class.

## Known gaps / things to revisit
- `ModeController::update()` assumes a `device_settings` row already exists
  (`DeviceSetting::first()->update(...)`) — fine given the seeder, but will throw on an unseeded DB.
  Consider `firstOrCreate` if that's ever a real risk.
- No auth/middleware on any route yet — everything is open.
- `SettingsController::update()` also assumes a `device_settings` row already exists, same
  `DeviceSetting::first()->update(...)` pattern/risk as `ModeController`.
- `DoseModeController::update()` validates `schedules` as `size:6` — assumes exactly the 6 seeded
  rows always exist with compartment_number keys 1-6. Fine given current scope, but not generic.
- No server-side check that a medicine schedule's chosen compartments don't collide with another
  enabled medicine schedule (or with Dose Mode) at the same time/compartment — device-side
  behavior on overlap is undefined until real firmware exists.
- Live Status polls unconditionally every 5s even when the browser tab is backgrounded/hidden —
  fine for a stub, but worth pausing via the Page Visibility API once this hits a real device to
  avoid needless requests.
- Browser notification permission is only requested, never surfaced in the UI if the user denies
  it or if `Notification` isn't supported (e.g. non-HTTPS context) — status changes still show via
  the on-page card regardless, but there's no in-app hint telling the user notifications are off.
- `DoseModeController::update()` calls `sendDoseSchedules(DoseSchedule::orderBy(...)->get()->toArray())`,
  which sends full model objects (`id`, `compartment_label`, `created_at`, `updated_at`) to the
  device, not just the `{compartment_number, reminder_time, is_enabled}` shape implied by the
  spec. Harmless against the fake test server, but worth trimming to just the needed fields before
  pointing at real embedded hardware with a small JSON parse buffer (confirmed via the fake
  server's request log during this session).
- `SettingsController`'s `esp32_ip_address` validation uses Laravel's plain `ip` rule, which
  rejects `ip:port` — fine since `Esp32Service::baseUrl()` assumes the device listens on the
  default HTTP port 80, but means there's currently no way to point this app at an ESP32 (or test
  server) running on a non-standard port through the UI; has to be done directly in the DB.
- `Esp32Service::request()` treats any non-2xx HTTP response the same as a network-level failure
  (both become `Esp32ConnectionException` → `{success: false, error}`). That's fine for "device
  didn't respond correctly", but means an application-level 4xx from real firmware (e.g. "bad
  schedule payload") surfaces to the user as a generic connectivity error rather than a specific
  validation message — revisit once real firmware's error responses are known.

## Not started yet
- Real ESP32 firmware integration. The HTTP client side is done — endpoints, payload shapes, and
  timeout/error handling all match this spec and were verified against a stand-in server. What's
  left is pointing at actual hardware and confirming its real responses match what was assumed
  here (see the gaps above, especially the non-2xx handling and the port-in-IP limitation).

## Conventions established (follow these for new pages)
- Controller per page, `index()` to show + `update()`/`store()` to mutate, inject `Esp32Service`
  via constructor where needed.
- Named routes matching the sidebar's `$navItems` route names in `layouts/app.blade.php`.
- Views extend `layouts.app`, use `@section('page-title', ...)`.
- Flash messages via `session('success')` / `session('error')`, rendered at top of each view.
- Tailwind via CDN, no build step, no JS framework — plain `fetch()` for AJAX, reading the CSRF
  token from the `<meta name="csrf-token">` tag in the layout `<head>` for POST/DELETE requests
  fired via JS (as opposed to `@csrf` hidden fields for normal form submits).
