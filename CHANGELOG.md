# Changelog

All notable changes to `asignua/filament-relation-manager-tabs` are documented here.

## v1.1.0 - 2026-10-02

- `RelationManagerSection::make()` puts a relation manager into a collapsible `Section` (collapsed by default, lazy by default; label, icon and badge from the manager; hidden on Create and by `canViewForRecord()`).
- `lazy:` option on `RelationManagerTab::make()` / `many()` and `RelationManagerSection::make()`: `null` follows the manager's `$isLazy`, `true` forces a lazy placeholder, `false` forces an eager mount. Backward compatible: tabs keep following the manager.
- `RelationManagerTab::livewire()` exposes the embedded manager component.

## v1.0.0 - 2026-10-02

- `RelationManagerTab::make()` renders a relation manager as a regular tab of the form's `Tabs`.
- `RelationManagerTab::many()` builds one tab per manager class.
- The tab carries the manager's badge, badge colour and badge tooltip, and works on Edit and View pages.
- Laravel Boost guidelines for coding agents.
