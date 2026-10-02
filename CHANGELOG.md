# Changelog

All notable changes to `asignua/filament-relation-manager-tabs` are documented here.

## v1.1.0 - 2026-10-02

- `RelationManagerSection::make()` puts a relation manager into a collapsible `Section` (collapsed by default, lazy by default; label, icon and badge from the manager; hidden on Create and by `canViewForRecord()`).
- `lazy:` option on `RelationManagerTab::make()` / `many()` and `RelationManagerSection::make()`: `null` follows the manager's `$isLazy`, `true` forces a lazy placeholder, `false` forces an eager mount. Backward compatible: tabs keep following the manager.
- `RelationManagerTab::livewire()` exposes the embedded manager component.
- The tab follows the manager's `$isBadgeDeferred` (the badge is fetched after the page renders) and `getIconPosition()`, as a stock relation-manager tab does.
- The embedded manager receives the page's `activeLocale` (spatie-translatable locale switcher), as in the stock block.
- `make()`, `many()`, `livewire()` and `RelationManagerSection::make()` also accept a `Manager::make([...])` configuration; its properties are passed to the manager.
- The section computes the manager's badge once per render instead of twice.
- A custom `key:` with characters other than letters, digits, `-`, `_` and `.` now throws `InvalidArgumentException`. Such keys never worked: Filament pastes the key into an Alpine expression, so a backslash opened an empty tab and a quote broke the expression.

## v1.0.0 - 2026-10-02

- `RelationManagerTab::make()` renders a relation manager as a regular tab of the form's `Tabs`.
- `RelationManagerTab::many()` builds one tab per manager class.
- The tab carries the manager's badge, badge colour and badge tooltip, and works on Edit and View pages.
- Laravel Boost guidelines for coding agents.
