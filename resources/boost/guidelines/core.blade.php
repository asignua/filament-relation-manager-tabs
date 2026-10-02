## Filament Relation Manager Tabs (asignua/filament-relation-manager-tabs)

- `Asignua\FilamentRelationManagerTabs\RelationManagerTab::make(XRelationManager::class)` returns a `Tabs\Tab` to put into the form's (or the View page's) own `Tabs`, so the record page has ONE row of tabs. `RelationManagerTab::many([...])` makes one tab per manager.
- Remove the manager from the resource's `getRelations()` — otherwise it renders twice (tab + block under the form).
- Never pass a class name as `key:` — the tab must be a slug (a key with backslashes opens an EMPTY tab, no console error). Default key: `relation-manager-<classbasename slug>`.
- The tab is hidden on Create (no owner record yet) and when `canViewForRecord()` is false; label, icon and badge come from the manager's `getTitle()`, `getIcon()`, `getBadge()`/`getBadgeColor()`/`getBadgeTooltip()`.
