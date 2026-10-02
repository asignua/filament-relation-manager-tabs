# Filament Relation Manager Tabs

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-relation-manager-tabs)
[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-relation-manager-tabs/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/asignua/filament-relation-manager-tabs/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-relation-manager-tabs)
[![License](https://img.shields.io/packagist/l/asignua/filament-relation-manager-tabs.svg?style=flat-square)](https://github.com/asignua/filament-relation-manager-tabs/blob/main/LICENSE.md)

<img class="filament-hidden" src="https://raw.githubusercontent.com/asignua/filament-relation-manager-tabs/v1.0.0/art/cover.jpg" alt="Filament Relation Manager Tabs">

Render a [Filament](https://filamentphp.com) relation manager as an ordinary tab of the record
form. The edit page then has exactly **one row of tabs**: the form's own tabs and your relation
managers side by side.

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Why not `hasCombinedRelationManagerTabsWithContent()`](#why-not-hascombinedrelationmanagertabswithcontent)
- [Gotchas](#gotchas)
- [AI agents](#ai-agents)
- [Testing](#testing)

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5

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
manager, exactly as on a stock relation-manager tab: the badge (`getBadge()`, `getBadgeColor()`,
`getBadgeTooltip()`) and the visibility (`canViewForRecord()`).

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
  when you use `persistTabInQueryString()`, because the key ends up in the URL.
- **Do not also return the manager from `getRelations()`.** It would be rendered twice: as a tab
  and as a block under the form.
- **Nesting inside the edit page `<form>` is fine.** Filament draws the action modal only after
  the action is mounted (a Livewire DOM patch), so the nested form survives. You do not need
  `hasFormWrapper(): false`.

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
