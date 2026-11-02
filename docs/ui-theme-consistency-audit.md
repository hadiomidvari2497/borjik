# UI / CSS theme consistency audit

Date: 2026-10-19

## Findings and changes in this pass

### 1. Shared application stylesheet — `public/css/app.css`
- The membership/role-switch controls had multiple successive definitions for the same selectors. The older rules forced a full-width bordered block, while later rules converted it into inline pill controls; the cascade made the layout depend on source order and viewport.
- Consolidated the membership control rules into one canonical definition, kept the compact pill presentation, retained responsive wrapping, and removed the superseded `.tbl .act` declaration.
- Kept the shared color tokens (`--brand`, `--line`, `--panel`, `--text`) so the controls follow the active theme.

### 2. Phone book — `resources/views/phonebook/index.blade.php`
- The refreshed view still used fixed light-theme colors for active tabs, contact avatars, phone-number chips, empty states, and validation errors. Those could remain pale in dark mode and lose contrast against dark cards.
- Added explicit dark-theme and system-dark variants for those surfaces and focus states. The hero remains intentionally dark to match the common workspace header treatment.

### 3. Calendar — `resources/views/calendar/index.blade.php`
- The page defined its ink, muted text, and border colors as fixed light-theme values, bypassing the global theme tokens.
- Connected those base colors to the shared theme variables and added dark variants for weekday headers, today/holiday cells, event-type chips, active filters, and hover states. Holiday and event color categories remain distinguishable.

### 4. Admin operations and monitoring
- `resources/views/admin/operations/index.blade.php`, `resources/views/admin/monitoring/performance.blade.php`, `resources/views/admin/monitoring/host.blade.php`, `resources/views/admin/monitoring/cron.blade.php`, and `resources/views/admin/analytics/index.blade.php` used pale status badges or low-contrast green/red text that was tuned for a light background.
- Added dark-theme contrast variants for good/warning/failure states and live status labels.

### 5. Reviewed without functional changes
- `resources/views/tickets/index.blade.php` uses the shared theme tokens for the main tables, forms, cards, and filters; its fixed white hero action is intentional contrast against the dark hero.
- `resources/views/sms-templates/index.blade.php` uses theme variables for the main card/form/preview surfaces; the red disabled-state text is a semantic status color, so no broad override was added.
- `resources/views/layouts/app.blade.php` already centralizes the common workspace hero treatment. Its specialized hero selectors are deliberate to keep calendar, SMS, task, role, and phone-book headers visually aligned.

### 6. Additional high-contrast views checked in the follow-up pass
- `resources/views/polls/index.blade.php`: fixed light-only form fields, option states, count chips, result surfaces, empty state, and validation errors received dark-theme variants.
- `resources/views/staff-roles/index.blade.php`: role cards, permission checkboxes, editable role fields, empty states, and success/error alerts received dark-theme variants.
- `resources/views/requests/index.blade.php`: request status and priority chips now use brighter semantic colors in dark mode.
- `resources/views/notifications/index.blade.php` and `resources/views/sms-templates/index.blade.php`: warning/info and disabled-state labels now retain readable contrast in dark mode.

## Verification notes
- Changes are limited to presentation/CSS; routes, controllers, permissions, data handling, and JavaScript behavior were not intentionally changed.
- CSS brace balance and key selector presence should be checked in CI, and the rendered pages should still be checked at desktop/mobile widths and in both explicit light/dark modes. A source review alone cannot guarantee pixel-perfect rendering across browsers.
- This is a first cross-cutting audit of the shared shell and high-visibility views, not a claim that every individual page has been visually inspected in a live browser. The remaining pages should be checked in the next pass using the same theme-token and responsive-layout checklist.

## 2026-11-02 — Light-by-default and dark-theme polish

- **Default theme is now light.** The panels (building and admin) no longer follow the OS `prefers-color-scheme`; dark is only active when the user picks it (`data-theme="dark"`, stored in `localStorage` key `borjik-theme`). The toggle in `public/js/app.js` / `admin.js` only reads/writes `light`/`dark`, and both layouts set `data-theme` in `<head>` to avoid a flash.
- All former `@media (prefers-color-scheme: dark)` blocks in views and `notify.css` were converted to explicit `:root[data-theme="dark"]` rules (several pages previously had dark styling only under the OS media query, so an explicit dark choice on a light OS was partly unstyled). `tests/Unit/ThemeDefaultTest.php` guards this.
- **Dark palette refresh** (`public/css/app.css`): layered surfaces (background < panel < card < menus), subtle translucent borders instead of bright ones, softer text (`#e6f1ef`), calmer background glows, layered shadows with a top highlight, dedicated `--field`/`--elev` tokens for inputs and menus, and `--fill-a/--fill-b` for filled brand surfaces so white text keeps adequate contrast (light values unchanged). Semantic colours (danger/warn/ok/info) are brighter in dark.
- `--surface` / `--surface-2` were referenced by several pages (treasury, maintenance, …) but never defined; they are now defined **in dark only**, so light rendering is unchanged.
- Known intentional exception: the treasury Jalali year field stays white in dark mode (explicit earlier decision).
- Not verified in a real browser; please check both themes at desktop/mobile widths.
