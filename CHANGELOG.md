# Changelog

All notable changes to `asignua/filament-relation-manager-tabs` are documented here.

## v1.1.1 - 2026-10-08

- **Fixed:** the lazy placeholder of a tab or section no longer carries the owner record as a `record="{…json…}"` attribute. Filament's `Livewire` component always injects `'record'`; the manager has no such property, so a Livewire that forwards unknown parameters as HTML attributes printed the model JSON, and a `"` in it broke the attribute and leaked raw text into the hidden panel. `RelationManagerTab::livewire()` now sets `record` to `null` (a caller's `->data(['record' => …])` still overrides it) (#1, thanks @bernhardh).
- **Requires Filament 5.6+** (was `^5.0`): the tab calls `deferBadge()` (5.3+) and `isBadgeDeferred()` (5.6+), so older Filament 5 crashed every Edit/View page with a tab.
- Enter in the embedded manager's search, filter or inline-edit input no longer submits the Edit page form (it saved the half-edited record). The embedded component carries a `keydown.enter` guard limited to inputs owned by the outer form.
- The tab resolves badge, badge colour and tooltip once per record and page instead of up to three times per render (`Tabs` reads them in the nav and both dropdown loops). Shared with the section via `ManagerReference::memoized()`.
- **Behaviour change:** a custom `key:` containing `&` now throws `InvalidArgumentException`: the browser decodes character references (`a&lt;b`) in the tab button's Alpine attribute, so the tab opened empty.

- Laravel Boost guideline: the custom `key:` rule now matches the code and the README (empty, backslash, quote, backtick, angle bracket or control character throws; everything else is accepted). It still described an earlier, stricter rule (`[A-Za-z0-9_.-]` only) that the code never shipped.

## v1.1.0 - 2026-10-03

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
