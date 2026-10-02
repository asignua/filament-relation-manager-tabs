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
- **Behaviour change:** a custom `key:` that is empty or contains a backslash, a quote (`'`, `"`), a backtick, `<`, `>` or a control character now throws `InvalidArgumentException` (at form build, so the page fails loudly instead of misbehaving). A backslash never worked (the tab opened empty, with no console error). The other characters either broke the Alpine expression (Filament releases before its key sanitising) or are silently stripped by Filament, so the key in the DOM and the URL differed from the one passed. Every other key accepted by v1.0.0 (spaces, `:`, non-ASCII letters…) is still accepted.

## v1.0.0 - 2026-10-02

- `RelationManagerTab::make()` renders a relation manager as a regular tab of the form's `Tabs`.
- `RelationManagerTab::many()` builds one tab per manager class.
- The tab carries the manager's badge, badge colour and badge tooltip, and works on Edit and View pages.
- Laravel Boost guidelines for coding agents.
