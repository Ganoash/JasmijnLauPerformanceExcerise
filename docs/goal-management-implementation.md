# Goal Management Implementation Plan

## Purpose

Add user-managed race goals to the Lau Performance Training plugin. Goals are date-based, attached to WordPress users, visible in the coach overview, and shown as visual markers in both frontend and admin training schedules.

## Locked Product Spec

### Goal fields

Store goals in one shared table keyed by `user_id`.

Fields:

- `id`
- `user_id`
- `name`
- `description`
- `goal_date`
- `target_time`
- `actual_time`
- `active`
- `created_at`
- `updated_at`

Rules:

- `name` is required.
- `goal_date` is required.
- `goal_date` is stored as `YYYY-MM-DD`.
- Dates are displayed as `09 sep 2026`.
- `description` is optional.
- `target_time` is optional free text.
- Empty target time is displayed as `Geen streeftijd`.
- `actual_time` is optional, but must be exact `HH:MM:SS` when provided.
- `active` defaults to true.
- A user can have at most 3 active goals.
- A user cannot have two goals on the same date, including inactive goals.
- Goals are hard-deleted.
- Goals are visual-only and do not affect schema generation.
- Goals are not automatically deactivated after their date passes.

### Access rules

- Logged-out users are redirected with `auth_redirect()`.
- Regular users can only manage their own goals.
- Users can add, edit, delete, activate, and deactivate their own goals.
- Coaches can manage another user's goals through the same frontend goals page.
- Coach access uses the existing `manage_training_schemas` capability.

### Routes and shortcode

Frontend routes:

- Own goals: `/training-goals/`
- Coach managing a user: `/training-goals/{user_id}/`

Shortcode:

- Add a shortcode that renders a compact active-goal overview and a link to the goals page.
- The overview shows active goals only.
- If active goals exist, show the current goals and link text like `Mijn doelen bekijken`.
- If there are no active goals, invite the user to set a goal.

### Schedule display

- All goals, active and inactive, appear in schedules.
- Schedule badges show goal `name` and `target_time`.
- Schedule badges do not show `actual_time`.
- Admin schedule shows each goal once per day.
- Frontend schedule shows the badge in each visible row for that day.
- Goals do not create, edit, or regenerate training schema rows.

### Coach overview display

In `templates/admin/user-overview.php`, add a goals column.

- Show active goals only.
- Date does not affect visibility.
- Display each goal as concatenated text:
  `name, 09 sep 2026, target time`
- If no target time exists, use `Geen streeftijd`.
- Add a link from each user row to `/training-goals/{user_id}/`.

### Goals page UI

Page title: `Doelen`.

Sections:

- `Actieve doelen`
- `Inactieve doelen`

Form behavior:

- Add and edit goals from the page.
- Delete requires confirmation.
- Validation errors are shown on the page.
- When `actual_time` changes from empty to filled, show a small celebration animation.
- Frontend styling should have parity with the existing schema page.

## Proposed Technical Design

### Database

Extend `includes/Activation/DatabaseInstaller.php` with a new `lpt_goals` table.

Suggested schema:

```sql
CREATE TABLE {$goals} (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(190) NOT NULL,
	description TEXT NULL,
	goal_date DATE NOT NULL,
	target_time VARCHAR(80) NULL,
	actual_time VARCHAR(8) NULL,
	active TINYINT(1) NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY user_goal_date (user_id, goal_date),
	KEY user_id (user_id),
	KEY active (active),
	KEY goal_date (goal_date)
) {$charset_collate};
```

Implementation note: the plugin currently installs tables on activation and writes `lpt_db_version`. If this feature must ship to an already-active site, add a runtime upgrade path instead of relying only on reactivation.

### Domain

Add:

- `includes/Domain/Goal.php`

Expected properties:

- `id`
- `userId`
- `name`
- `description`
- `goalDate`
- `targetTime`
- `actualTime`
- `active`
- `createdAt`
- `updatedAt`

Keep the domain object simple and immutable, matching existing domain style.

### Repository

Add:

- `includes/Repositories/GoalRepository.php`

Required methods:

- `findById(int $id): ?Goal`
- `findByUser(int $user_id): array`
- `findActiveByUser(int $user_id): array`
- `findByUserAndDateRange(int $user_id, string $start_date, string $end_date): array`
- `create(...) : int`
- `update(...) : void`
- `deleteForUser(int $goal_id, int $user_id): void`
- `deleteByUser(int $user_id): void`
- `activeCountForUser(int $user_id, ?int $excluding_goal_id = null): int`
- `dateExistsForUser(int $user_id, string $goal_date, ?int $excluding_goal_id = null): bool`

Ordering:

- Return goals ordered by `goal_date ASC`, then `id ASC`.

### Validation

Add:

- `includes/Validation/GoalValidator.php`

Validation rules:

- Require non-empty `name`.
- Require valid `goal_date` in `Y-m-d`.
- Sanitize `description` as textarea.
- Sanitize `target_time` as text.
- Accept empty `actual_time`.
- Reject non-empty `actual_time` unless it matches exact `HH:MM:SS`.
- Normalize `active` to boolean.

Service-level validation should enforce:

- Max 3 active goals per user.
- No duplicate date per user across active and inactive goals.
- The target user exists.

### Service

Add:

- `includes/Services/GoalService.php`

Responsibilities:

- Authorize user-scoped create/update/delete operations.
- Enforce max active goals.
- Enforce duplicate-date rule.
- Detect whether `actual_time` changed from empty to filled, so the page can trigger the celebration animation.
- Keep controller/page classes thin.

Suggested result for save actions:

```php
[
	'goal_id' => $goal_id,
	'actual_time_completed' => $changed_from_empty_to_filled,
]
```

### Permissions

Either extend `SchemaAccess` or add a dedicated `GoalAccess`.

Required checks:

- Current user can manage goal user if `current_user_id === target_user_id`.
- Current user can manage goal user if `current_user_can('manage_training_schemas')`.
- Otherwise deny.

A dedicated `GoalAccess` is clearer because goals are not schema edits, even though coach capability reuses `manage_training_schemas`.

### Frontend Page

Add:

- `includes/Frontend/GoalsPage.php`
- `templates/frontend/goals-page.php`
- `assets/frontend/goals.css`
- `assets/frontend/goals.js`

Page actions:

- Register `admin_post_lpt_save_goal`.
- Register `admin_post_lpt_delete_goal`.
- Use a new nonce action, for example `Nonce::GOAL_ACTION`.

Routing:

- Extend `RewriteRoutes` to support both goals routes, or add a separate `GoalRoutes` class.
- Query vars:
  - `lpt_goals_page`
  - `lpt_goal_user_id`

Render behavior:

- `/training-goals/` renders current user's goals.
- `/training-goals/{user_id}/` renders that user's goals only when the current user has coach access.
- Invalid or inaccessible user IDs return 403 or 404 as appropriate.

Form behavior:

- Use one edit form that can also create new goals.
- Add per-goal edit controls.
- Use hard-delete forms with confirmation in JavaScript.
- Redirect back to the goals page after save/delete.
- Add a query flag after first actual-time completion, such as `?goal_completed=1`, and let `goals.js` run the celebration animation.

### Shortcode

Add a small shortcode class or register in `Plugin`.

Suggested shortcode:

- `[lpt_goals_link]`

Behavior:

- Logged-out visitors get no private goal data.
- Logged-in users see active goals only.
- Render a link to `/training-goals/`.
- If active goals exist, render compact list with name/date/target time.
- If no active goals exist, render invitation copy to set a goal.

### Admin User Overview

Update:

- `includes/Admin/UserOverviewPage.php`
- `templates/admin/user-overview.php`

Inject `GoalRepository` or `GoalService` into `UserOverviewPage`.

Render data:

- Build `active_goals_by_user`.
- Pass to template.
- Add a `Doelen` column.
- For each user, show active goals as `name, 09 sep 2026, target`.
- Use `Geen streeftijd` when target time is empty.
- Include a button/link to `/training-goals/{user_id}/`.

### Schedule Integration

Update:

- `includes/Admin/SchemaEditorPage.php`
- `templates/admin/schema-editor.php`
- `includes/Frontend/SchemaPage.php`
- `templates/frontend/schema-page.php`

Data loading:

- For the selected week, load all goals for the target user with dates from `week->startDate()` through `week->endDate()`.
- Group goals by `goal_date`.

Admin template:

- Render goal badge in the day/date cell.
- Because admin may show two training rows per day, render the badge only on the first visible row for that `dayIndex`.

Frontend template:

- Render goal badge inside every visible training row whose day matches the goal date.

Badge content:

- Goal name.
- Target time or `Geen streeftijd`.

## Implementation Sequence

1. Add `Goal` domain object and `GoalRepository`.
2. Add database table to installer and decide whether to add a runtime upgrade path.
3. Add `GoalValidator`, `GoalAccess`, and `GoalService`.
4. Add frontend goals routes, page class, template, nonce actions, and save/delete handlers.
5. Add goals page CSS and JS, including delete confirmation and completion celebration.
6. Add shortcode rendering active goals and link.
7. Inject goals into the admin user overview.
8. Inject week goals into admin and frontend schedule pages.
9. Add tests across repository, validation, permissions, rendering, routes, and service rules.

## Test Plan

### Unit tests

- `GoalValidatorTest`
  - accepts valid required fields
  - allows empty optional fields
  - rejects missing name
  - rejects missing date
  - rejects invalid date
  - accepts `01:42:15`
  - rejects `42:15`
- `GoalAccessTest`
  - own user allowed
  - coach capability allowed
  - other regular user denied

### Integration tests

- `GoalRepositoryTest`
  - creates and retrieves goals
  - orders by date
  - finds active goals
  - finds goals by week range
  - enforces unique user/date at repository or service layer
  - deletes by user
- `GoalServiceTest`
  - blocks fourth active goal
  - blocks duplicate date
  - allows duplicate date for different users
  - detects actual-time empty-to-filled transition
  - does not trigger completion when editing existing actual time
- `GoalsPageRenderTest`
  - own goals page renders active and inactive sections
  - coach can render another user's page
  - regular user cannot render another user's page
- `UserOverviewPageRenderTest`
  - active goals appear in the overview
  - inactive goals do not appear in the overview
  - empty target time displays `Geen streeftijd`
- `SchemaPageRenderTest`
  - frontend schedule shows goal badge on matching date
  - inactive goals still appear in schedule
  - badge shows target time and not actual time
- `SchemaEditorPageRenderTest`
  - admin schedule shows goal badge once per day

### Acceptance tests

Add a goal-management feature:

- User creates a goal and sees it under active goals.
- User cannot create a fourth active goal.
- User cannot create two goals on the same date.
- User edits a goal.
- User deletes a goal after confirmation.
- User fills actual time and sees completion feedback.
- Coach opens another user's goals page and updates active state.
- Regular user cannot open another user's goals page.
- Goal appears in frontend and admin schedules.

## Open Implementation Notes

- The current `RewriteRoutes` constructor accepts only `SchemaPage`; adding goals may make a separate route class cleaner.
- The plugin currently has review notes about runtime database upgrades. Adding `lpt_goals` should address that if the feature targets an already-installed site.
- Schedule display should share date formatting and badge formatting through helper methods to avoid drift between frontend, admin, and overview output.
- Keep schedule integration read-only. Do not couple goals to `SchemaCreationService`.
