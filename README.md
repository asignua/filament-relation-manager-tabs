# Filament Relation Manager Tabs

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-relation-manager-tabs)
[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-relation-manager-tabs/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/asignua/filament-relation-manager-tabs/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-relation-manager-tabs)
[![License](https://img.shields.io/packagist/l/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://github.com/asignua/filament-relation-manager-tabs/blob/main/LICENSE.md)
[![Plumb score](https://plumbphp.dev/badges/asignua/filament-relation-manager-tabs/composite.svg)](https://plumbphp.dev/asignua/filament-relation-manager-tabs)

<img class="filament-hidden" src="https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/cover.jpg" alt="Filament Relation Manager Tabs">

Render a [Filament](https://filamentphp.com) relation manager as an ordinary tab of the record
form. The edit page then has exactly **one row of tabs**: the form's own tabs and your relation
managers side by side.

- [Screenshots](#screenshots)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Lazy loading](#lazy-loading)
- [Collapsible sections](#collapsible-sections)
- [Why not `hasCombinedRelationManagerTabsWithContent()`](#why-not-hascombinedrelationmanagertabswithcontent)
- [Gotchas](#gotchas)
- [AI agents](#ai-agents)
- [Testing](#testing)

## Screenshots

One row of tabs — the form's own tabs and the relation managers side by side, with their badges:

![Relation managers as form tabs](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/tasks-tab.jpg)

The same page in dark mode:

![Dark mode](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/tasks-tab-dark.jpg)

The form tabs work as usual:

![The Details tab](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/details-tab.jpg)

For comparison, stock Filament — the managers get a second row of tabs under the form:

![Stock Filament: a second row of tabs under the form](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/stock-filament.jpg)

A `RelationManagerSection` starts collapsed - nothing is loaded:

![A collapsed relation-manager section](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.1.0/art/section-collapsed.jpg)

Expanding it mounts the manager on demand:

![The expanded section](https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.1.0/art/section-expanded.jpg)

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5.6+ (the tab uses `deferBadge()` and `isBadgeDeferred()`)

## Installation

```bash
composer require asignua/filament-relation-manager-tabs
```

There is no service provider and nothing to register: it is a schema helper, not a panel plugin.

## Usage

Put `RelationManagerTab::make()` into the same `Tabs` component as the rest of the form, and
return an empty array from `getRelations()`:

```php
use Asignua\FilamentRelationManagerTabs\RelationManagerTab;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class TeamResource extends Resource
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')
                ->persistTabInQueryString()
                ->tabs([
                    Tabs\Tab::make('Details')->schema([
                        TextInput::make('name')->required(),
                    ]),
                    RelationManagerTab::make(PostsRelationManager::class),
                    RelationManagerTab::make(
                        MembersRelationManager::class,
                        label: 'People',
                        icon: 'heroicon-o-users',
                        key: 'people',
                    ),
                ])
                ->columnSpanFull(),
        ]);
    }

    // The managers live in the form's tabs — never return them here as well.
    public static function getRelations(): array
    {
        return [];
    }
}
```

`make()` takes the manager class, an optional label (default: the manager's `getTitle()`), an
optional icon (default: its `getIcon()`) and an optional tab key. Everything else comes from the
manager, exactly as on a stock relation-manager tab: the icon position (`getIconPosition()`), the
badge (`getBadge()`, `getBadgeColor()`, `getBadgeTooltip()`, and `$isBadgeDeferred`) and the
visibility (`canViewForRecord()`). The manager receives the same data as in the stock block,
including the page's `activeLocale` when the page uses a locale switcher (spatie-translatable).

To pass properties to the manager, give `make()` a configuration instead of a class name, as in
stock `getRelations()`:

```php
RelationManagerTab::make(PostsRelationManager::make(['status' => 'draft']));
```

`RelationGroup` (several managers under one stock tab) is not supported: give each manager its
own tab. A manager's overridden `getTabComponent()` is not used either; the tab is built from the
static getters listed above.

It works the same way on **View** pages: put the tab into the `Tabs` of the infolist (or of the
form the View page shows).

Several managers at once, with default labels, icons and keys:

```php
Tabs::make('Tabs')->tabs([
    Tabs\Tab::make('Details')->schema([/* ... */]),
    ...RelationManagerTab::many([
        PostsRelationManager::class,
        MembersRelationManager::class,
    ]),
]);
```

## Lazy loading

Filament relation managers are already lazy by default (`RelationManager::$isLazy = true`):
Livewire renders a placeholder and mounts the manager when it scrolls into view. A tab panel
that is not active and a section that is collapsed are `display: none`, so they never intersect —
**nothing is mounted or queried until the tab is opened / the section expanded.** (Older Filament
discussions asking for this predate that default.) The `lazy:` argument lets you override the
manager per placement:

```php
RelationManagerTab::make(PostsRelationManager::class);               // follow the manager (default)
RelationManagerTab::make(PostsRelationManager::class, lazy: true);   // force lazy, even if $isLazy = false
RelationManagerTab::make(PostsRelationManager::class, lazy: false);  // mount eagerly with the page
RelationManagerTab::many([...], lazy: true);
```

A lazy manager mounts when its placeholder enters the viewport, so a section expanded *below the fold* loads when you scroll to it, not at the click.

Note that the tab **badge** is computed by the parent page, so a `getBadge()` that runs a query
still runs on the initial render. Set `$isBadgeDeferred = true` on the manager if that matters: the
tab then renders without the badge and fetches it in a separate request, as a stock tab does. A
section has no such request, so in a section the badge is always computed with the page (once).

## Collapsible sections

When tabs are the wrong layout, put the manager in a collapsible `Section`:

```php
use Asignua\FilamentRelationManagerTabs\RelationManagerSection;

$schema->components([
    Section::make('Details')->schema([/* ... */]),
    RelationManagerSection::make(PostsRelationManager::class),              // collapsed + lazy
    RelationManagerSection::make(
        MembersRelationManager::class,
        label: 'People',
        icon: 'heroicon-o-users',
        collapsed: false,
        lazy: false,
        key: 'people',
    ),
]);
```

The heading, icon, badge, badge colour and badge tooltip come from the manager like on a tab (a
`Section` has no icon position, so `getIconPosition()` does not apply); the section is hidden on Create
and when `canViewForRecord()` is false. By default it starts **collapsed** and **lazy** (the
manager is mounted on first expand). It returns a regular `Filament\Schemas\Components\Section`,
so `->columnSpanFull()`, `->description()`, `->persistCollapsed()` and so on still work. The
same slug-key and "empty `getRelations()`" rules apply.

## Why not `hasCombinedRelationManagerTabsWithContent()`

Stock Filament draws the form with its own `Tabs`, and the related records in a separate block
**under** it — with two or more managers, a second row of tabs. Filament's built-in
`hasCombinedRelationManagerTabsWithContent()` does not remove that second level: it wraps the
**whole form** into one tab, so your form tabs just sink one level down. This helper hangs the
manager into the form's own `Tabs`, which is the only way to get a single row.

## Gotchas

- **No tab on the Create page.** A relation manager needs an owner record, and on a create page
  the record does not exist yet. The tab is hidden until it does (the same condition Filament uses
  to hide managers on create).
- **The tab key is a slug, and it must stay one.** Filament pastes the key into Alpine as a bare
  string in the tab button (`x-on:click="tab = '<key>'"`) but double-escaped in the panel. A key
  holding a class name (backslashes) makes the two diverge: `\F`, `\E`, `\U` become JS escape
  sequences in the button, and the tab opens **empty, with no console error**. The default key is
  the slug of the class basename (`relation-manager-postsrelationmanager`). Pass your own `key:`
  when you use `persistTabInQueryString()`, because the key ends up in the URL. A custom key must
  not be empty and must not contain a backslash, a quote (`'`, `"`), a backtick, `&`, `<`, `>` or a
  control character; such a key throws `InvalidArgumentException`. A backslash or an HTML
  character reference (`a&lt;b`) opens an empty tab, and Filament silently strips the rest from
  keys, so the key in the DOM and the URL would not be yours. Spaces, `:`, non-ASCII letters and
  the like are fine.
- **Two managers with the same class basename** (`Blog\PostsRelationManager` and
  `Shop\PostsRelationManager`) get the same default key. On one page, pass `key:` to at least one
  of them, or the tabs (or section ids) collide.
- **Do not also return the manager from `getRelations()`** (stock Filament renders whatever it
  returns as a block under the form). A manager left registered there is rendered twice: once as
  your tab or section and once as the stock block.
- **The manager sits inside the edit page `<form>`.** Filament draws the action modal only after
  the action is mounted (a Livewire DOM patch), so the nested modal form survives. Implicit
  submission is the catch: Enter in the manager's search field, a filter input or an inline-editable
  column would submit the outer form and save the record. The plugin wraps the embedded manager in
  a `keydown.enter` guard that stops Enter for inputs owned by the outer form only; the manager's
  own modal forms keep working. `hasFormWrapper(): false` on the Edit page remains an alternative.

## AI agents

The package ships [Laravel Boost](https://github.com/laravel/boost) guidelines
(`resources/boost/guidelines/core.blade.php`): with Boost installed, `php artisan boost:install`
picks them up, so your coding agent knows the rules above.

## Testing

```bash
composer test      # PHPUnit (Orchestra Testbench)
composer analyse   # Larastan
composer format    # Pint
```

## Changelog

See [CHANGELOG.md](https://github.com/asignua/filament-relation-manager-tabs/blob/main/CHANGELOG.md).

## License

MIT. See [LICENSE.md](https://github.com/asignua/filament-relation-manager-tabs/blob/main/LICENSE.md).
