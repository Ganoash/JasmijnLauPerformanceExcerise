# Commit Review Points

Reviewed repository: `lau-performance-training`  
Reviewed range: `a14e044` through `4472b08` on `main`  
Review date: 2026-08-15

## Open Findings

### P2 - Frontend feedback write permission is broader than the capability name implies

Introduced in `a154f1f` / used by `2b2dcf1`.

`SchemaAccess::canUpdateFeedback()` currently delegates to `canViewSchema()`, so any user who can view all schemas can also write athlete feedback fields through the frontend AJAX endpoint. That includes `view_all_training_schemas`, even though the name reads as read-only. If a future role receives only view-all access for monitoring, that role will still be able to update sport-specific distance fields, `execution_comment`, and `injury_comment`.

References:
- `includes/Permissions/SchemaAccess.php:28`
- `includes/Permissions/SchemaAccess.php:33`
- `includes/Services/FrontendFeedbackService.php:45`

Recommended fix: Rename `canViewSchema` to `canViewAndEditSchema`

### P2 - Admin schema saves trust posted training IDs without binding them to the selected schema

Introduced in `0659c2f`; still present after `5880bce` and `39ff7ca`.

`SchemaEditorPage::save()` accepts `user_id` and `week_start_date`, but the service only saves the submitted `training_id` rows. It does not load the expected schema for that user/week or verify that every posted training belongs to it. A forged or stale admin form can update arbitrary training rows by ID and then redirect back to a different user/week, creating silent data corruption.

References:
- `includes/Admin/SchemaEditorPage.php:85`
- `includes/Admin/SchemaEditorPage.php:94`
- `includes/Services/SchemaEditorService.php:29`
- `includes/Repositories/TrainingRepository.php:97`

Recommended fix: derive the schema from the submitted `user_id` and `week_start_date`, load that schema's training IDs server-side, and reject any posted row outside that set before writing.

### P2 - Primary training type IDs are not validated before being persisted

Introduced in `0659c2f`; partially improved for extra exercises in `39ff7ca`.

`SchemaRequestValidator` normalizes any positive `primary_training_type_id`, and `SchemaEditorService` persists it without checking that the type exists or is selectable. The form limits choices in normal UI use, but a forged request can store non-existent or unintended IDs. The current frontend then drops totals for missing types, and inactive/unexpected types can be attached as primary selections.

References:
- `includes/Validation/SchemaRequestValidator.php:31`
- `includes/Validation/SchemaRequestValidator.php:39`
- `includes/Services/SchemaEditorService.php:30`
- `includes/Repositories/TrainingRepository.php:101`

Recommended fix: validate primary type IDs against `TrainingTypeRepository`, ideally against the same active-or-already-used option set used to render the editor.

### P2 - Invalid week strings can throw uncaught exceptions and return a 500

Introduced in `0659c2f` for admin editor dates and `9ec7ed0` for frontend routes.

Both the admin editor and frontend schema page call `Week::fromDateString()` directly on request input. The frontend rewrite pattern checks shape only, not calendar validity, and the admin route accepts any sanitized text. Invalid values such as malformed dates can bubble up from `DateTimeImmutable` as uncaught exceptions.

References:
- `includes/Admin/SchemaEditorPage.php:50`
- `includes/Frontend/RewriteRoutes.php:25`
- `includes/Frontend/SchemaPage.php:42`
- `includes/Domain/Week.php:24`

Recommended fix: add a request date validator that requires `Y-m-d`, verifies `DateTimeImmutable::getLastErrors()`, and returns a controlled 400/404 or admin notice instead of creating a `Week` directly from request input.

### P3 - Frontend save retries client validation failures and hides server messages

Introduced in `2b2dcf1`.

`schema-view.js` retries every failed save up to three times, including deterministic 400/403 responses such as invalid distance or expired nonce. It also discards the JSON error message returned by the server, so users only see `Niet opgeslagen` even when the API can tell them what is wrong.

References:
- `assets/frontend/schema-view.js:69`
- `assets/frontend/schema-view.js:75`
- `assets/frontend/schema-view.js:81`
- `includes/Ajax/FrontendTrainingSaveAction.php:48`

Recommended fix: parse error payloads, show the server message, and retry only transient network errors or 5xx responses.

## Commit-by-Commit Review

### `a14e044` - Add plugin skeleton and tooling

No new review point. The plugin entry point guards direct access, registers after `plugins_loaded`, and the fallback autoloader is small and scoped to the plugin namespace.

### `90bc792` - Add activation capabilities and database tables

No blocking review point in the current schema. One operational note: database installation runs on activation, and `lpt_db_version` is written, but there is no runtime upgrade path keyed from that version. That is acceptable for a first `0.1.0` schema, but future schema changes should add an upgrade hook instead of relying on deactivate/reactivate.

### `6e9df6e` - Add domain objects repositories and week handling

Review point covered above: `Week::fromDateString()` centralizes week parsing but does not distinguish invalid request input from programmer input. Once later commits route user-controlled dates into it, callers need validation or exception handling.

### `a09a9ff` - Add schema creation service and cron

No new review point. The schema and fixed-slot creation uses unique keys and idempotent repository calls, which is a good fit for cron and repeated page loads.

### `a154f1f` - Add schema access and feedback guardrails

Review point covered above: feedback write access is the same as schema view access. This should be made explicit if coaches are meant to edit feedback, and separated if view-all users should be read-only.

### `0d579cf` - Add training type admin management

No blocking review point. The page has capability checks, nonce verification, validation, and escaped output. Consider constraining `category` and `unit` to controlled values later because total calculations depend on exact strings such as `running`, `cycling`, `swimming`, `meter`, and `meters`.

### `0659c2f` - Add coach schema admin editor

Review points covered above:
- Posted training IDs are not verified against the schema being saved.
- Primary training type IDs are not validated before persistence.
- Admin `week_start_date` request input can throw on invalid dates.

### `9ec7ed0` - Add frontend schema route and view

Review point covered above: the frontend route validates only date shape. Invalid calendar dates can still reach `Week::fromDateString()` and should produce a controlled response.

### `2b2dcf1` - Add frontend AJAX saves and distance totals

Review points covered above:
- The AJAX save path uses broad feedback write permission inherited from view access.
- The frontend retry loop treats validation and authorization failures as retryable and hides the API message.

### `5c65e3b` - Add dashboard schema block

No new review point. The dynamic block returns no links for anonymous visitors and only renders links for the current user.

### `107de7b` - Add acceptance scenarios and hardening checks

No new review point. This commit improves scenario documentation and hardening coverage. The remaining gaps are the negative cases listed above: forged admin training IDs, invalid week input, and primary type ID validation.

### `968d792` - Finalize schema editor cleanup

No new review point. This commit mainly cleans input handling in the editor. It does not address the schema ownership/binding validation gap, which remains covered above.

### `8f69e63` - Wire integration tests and JavaScript linting

No new review point. The split unit/integration setup and JavaScript linting are useful. Note that the configured test suite excludes WordPress ajax tests by default in the integration run; keep explicit AJAX coverage where endpoint behavior matters.

### `646158c` - Automate acceptance tests with Behat

No new review point. Behat automation was wired successfully and the current scenarios pass.

### `5f37214` - Split acceptance contexts by workflow

No new review point. Splitting contexts improves maintainability without changing production behavior.

### `09ace74` - Add acceptance coverage for core flows

No new review point. The added scenarios cover core happy paths and access denial. The open review points would benefit from additional acceptance or integration scenarios.

### `5db7148` - Cover frontend schema route resolution

No new review point. The route resolution coverage is useful, but it should be extended with invalid date values once request date validation is added.

### `b22707c` - Render schema page with block theme shell

No new review point. The block theme wrapper includes `wp_head()`, `wp_body_open()`, and `wp_footer()`, so frontend assets and theme hooks have the expected lifecycle.

### `617dfa4` - Simplify training admin submenu

No new review point. The submenu simplification is covered by admin menu tests.

### `9300744` - Keep hidden schema editor admin page accessible

No new review point. The hidden page remains registered and capability-protected while being removed from the visible submenu.

### `a613bda` - Describe training type admin fields

No new review point. This is a copy/help-text change. The underlying category/unit normalization note remains attached to `0d579cf`.

### `5880bce` - Register schema editor save handler

No new review point beyond the existing admin save validation gaps. The save handler registration itself is covered by integration tests.

### `39ff7ca` - Clarify extra exercise selection

No new review point. The commit improves extra exercise handling by filtering linked exercise IDs to strength training types. Primary training type validation remains separate and is still open.

### `4472b08` - Fix spacing issues in schema page

No new review point. This is limited to markup spacing in `templates/frontend/schema-page.php`; no behavior change was identified.

## Verification

Commands run:

```sh
docker exec jasmijn_lau_performance-www-1 sh -lc 'cd /var/www/html/wp-content/plugins/lau-performance-training && composer test:unit'
docker exec jasmijn_lau_performance-www-1 sh -lc 'cd /var/www/html/wp-content/plugins/lau-performance-training && composer test:integration'
docker exec jasmijn_lau_performance-www-1 sh -lc 'cd /var/www/html/wp-content/plugins/lau-performance-training && composer test:acceptance'
docker exec jasmijn_lau_performance-www-1 sh -lc 'cd /var/www/html/wp-content/plugins/lau-performance-training && composer stan'
docker exec jasmijn_lau_performance-www-1 sh -lc 'cd /var/www/html/wp-content/plugins/lau-performance-training && composer lint'
npm run lint
```

Results:
- Unit tests: 14 tests, 24 assertions, passing.
- Integration tests: 22 tests, 63 assertions, passing.
- Acceptance tests: 11 scenarios, 43 steps, passing.
- PHPStan: no errors.
- PHPCS: 34 files checked, passing.
- ESLint: passing.
