<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs;

use Asignua\FilamentRelationManagerTabs\Internal\ManagerReference;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Livewire\Component as LivewireComponent;

/**
 * A relation manager as an ORDINARY tab of the form, so a record page shows exactly ONE
 * row of tabs.
 *
 * Stock Filament gives two levels: the form with its own `Tabs` on top and the related
 * records in a separate block UNDER it (with several managers, a second row of tabs under
 * the form). The built-in `hasCombinedRelationManagerTabsWithContent()` does not cure this:
 * it wraps the WHOLE form into a single tab, so the form's tabs just sink one level down.
 * Hang the manager into the same `Tabs` as the rest of the form instead:
 *
 * ```php
 * Tabs::make('Tabs')->tabs([
 *     Tabs\Tab::make('Profile')->schema([...]),
 *     RelationManagerTab::make(PostsRelationManager::class),
 * ]);
 * ```
 *
 * The resource must NOT also return the manager from `getRelations()` — it would be
 * rendered twice: as a tab and as a block under the form.
 *
 * Three things worth knowing:
 *
 * 1. **There is no tab on the Create page.** A relation manager needs an `ownerRecord`, and
 *    on `CreateRecord` the record does not exist yet — so `visible()` hides the tab while
 *    `$record` is empty (the same condition Filament uses to hide managers on create).
 * 2. **The tab key is a SLUG, and that is not cosmetic.** Filament pastes the key into
 *    Alpine as a bare string (`x-on:click="tab = '<key>'"`) but into the panel DOUBLE-escaped.
 *    A key holding an FQCN therefore diverges: in the button `\F`/`\E`/`\U` are JS escape
 *    sequences, so `tab` becomes `…AppFilamentEegnith…` while the panel waits for
 *    `…App\Filament\Eegnith…`. The tab opens EMPTY, with no console error. So the default
 *    key is the slug of the class basename; pass your own (shorter — with
 *    `persistTabInQueryString()` it ends up in the URL) as an argument; an empty key, or one
 *    holding a backslash, a quote, a backtick, `&`, `<`, `>` or a control character, is rejected.
 * 3. **The manager renders INSIDE the edit page `<form>`.** Modals are fine: Filament draws the
 *    action modal only once the action is mounted, i.e. by a Livewire DOM patch, so the nested
 *    `<form wire:submit="callMountedAction">` survives. Implicit submission is not: Enter in the
 *    manager's search field, a filter input or an inline-editable column would submit the OUTER
 *    form and save the record, so the embedded component is wrapped in a `keydown.enter` guard
 *    that stops Enter for inputs owned by the outer form only (the manager's own modal forms
 *    and the search's `keyup` refresh keep working). `hasFormWrapper(): false` remains an
 *    alternative.
 */
class RelationManagerTab
{
    private const ENTER_GUARD = "if (\$event.target.form && \$event.target.form === \$el.closest('form') && \$event.target.matches('input:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button])')) \$event.preventDefault()";

    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager a manager class, or
     *                                                                            `Manager::make([...])` to pass it properties
     * @param Htmlable|string|null                                       $label   the label; null = the manager's `getTitle()`
     * @param BackedEnum|string|null                                     $icon    the icon; null = the manager's `getIcon()`
     * @param string|null                                                $key     the tab key (no `\`, quotes, backtick, `&`, `<`, `>` or control characters);
     *                                                                            null = slug of the class name
     * @param bool|null                                                  $lazy    null = follow the manager's `$isLazy` (Filament's
     *                                                                            default is lazy); true = force lazy; false = force eager
     *
     * @throws InvalidArgumentException when `$key` holds a character Alpine cannot take as is
     */
    public static function make(
        string|RelationManagerConfiguration $manager,
        string|Htmlable|null $label = null,
        string|BackedEnum|null $icon = null,
        ?string $key = null,
        ?bool $lazy = null,
    ): Tabs\Tab {
        $class = ManagerReference::className($manager);

        return Tabs\Tab::make()
            ->key(ManagerReference::key($class, $key))
            ->label($label ?? static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $class::getTitle($record, $livewire::class)
                : null)
            ->icon($icon ?? static fn (?Model $record, LivewireComponent $livewire): mixed => $record
                ? $class::getIcon($record, $livewire::class)
                : null)
            ->iconPosition(static fn (?Model $record, LivewireComponent $livewire): IconPosition => $record
                ? $class::getIconPosition($record, $livewire::class)
                : IconPosition::Before)
            // `Tabs` reads the badge, colour and tooltip up to three times per render; each is
            // resolved once per record and page (a manager's `getBadge()` usually counts rows).
            ->badge(ManagerReference::memoized(static fn (Model $record, string $page): ?string => $class::getBadge($record, $page)))
            // `$isBadgeDeferred` on the manager: the page renders without the badge and fetches it
            // in a separate request, exactly as on a stock relation-manager tab.
            ->deferBadge(static fn (?Model $record, LivewireComponent $livewire): bool => $record
                && $class::isBadgeDeferred($record, $livewire::class))
            ->badgeColor(ManagerReference::memoized(static fn (Model $record, string $page): ?string => $class::getBadgeColor($record, $page)))
            ->badgeTooltip(ManagerReference::memoized(static fn (Model $record, string $page): string|Htmlable|null => $class::getBadgeTooltip($record, $page)))
            ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => $record instanceof Model
                && $record->exists
                && $class::canViewForRecord($record, $livewire::class))
            ->schema([
                self::livewire($manager, $lazy),
            ]);
    }

    /**
     * The embedded manager.
     *
     * Filament relation managers are already lazy by default (`RelationManager::$isLazy = true`
     * puts `lazy` into `getDefaultProperties()`): Livewire renders a placeholder and mounts the
     * component when it enters the viewport. A hidden tab panel or a collapsed section
     * (`display: none`) never intersects, so nothing is mounted or queried before it is opened.
     * `$lazy` overrides the manager: `true` forces the placeholder even for a manager with
     * `$isLazy = false`, `false` forces an eager mount, `null` keeps the manager's own setting.
     *
     * The data mirrors stock Filament: `ownerRecord`, `pageClass`, the page's `activeLocale`
     * (spatie-translatable's locale switcher) when it has one, the manager's default properties
     * and the properties of a `Manager::make([...])` configuration.
     *
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     */
    public static function livewire(string|RelationManagerConfiguration $manager, ?bool $lazy = null): Livewire
    {
        $class = ManagerReference::className($manager);
        $configured = ManagerReference::properties($manager);

        return Livewire::make(
            $class,
            static function (Model $record, LivewireComponent $livewire) use ($class, $configured, $lazy): array {
                $properties = [
                    // Filament's `Livewire::getComponentProperties()` injects `'record' => $this->getRecord()`
                    // before the caller's data. A manager has no `$record`, so the model would land in
                    // the component's HTML attribute bag and the lazy placeholder would print it as
                    // `record="{…json…}"`; a quote in that JSON breaks the attribute and leaks text into
                    // the hidden panel. `null` renders nothing; a `->data(['record' => …])` still wins.
                    'record' => null,
                    'ownerRecord' => $record,
                    'pageClass' => $livewire::class,
                ];

                $activeLocale = property_exists($livewire, 'activeLocale') ? $livewire->activeLocale : null;

                if (filled($activeLocale)) {
                    $properties['activeLocale'] = $activeLocale;
                }

                $properties = [
                    ...$properties,
                    ...$class::getDefaultProperties(),
                    ...$configured,
                ];

                if ($lazy === null) {
                    return $properties;
                }

                if ($lazy) {
                    $properties['lazy'] = true;
                } else {
                    unset($properties['lazy']);
                }

                return $properties;
            },
        )
            ->key($class)
            // The manager sits inside the Edit page's `<form wire:submit="save">`, and its search
            // field, filter inputs and inline-editable columns are bare inputs owned by that
            // form: Enter would submit it and save the half-edited record. Swallow Enter only for
            // inputs owned by the OUTER form; the manager's own modal forms are separate `<form>`s.
            ->extraAttributes([
                'x-on:keydown.enter' => self::ENTER_GUARD,
            ]);
    }

    /**
     * One tab per manager, with default labels, icons and keys.
     *
     * @param array<int, class-string<RelationManager>|RelationManagerConfiguration> $managers
     *
     * @return array<int, Tabs\Tab>
     */
    public static function many(array $managers, ?bool $lazy = null): array
    {
        return array_map(
            static fn (string|RelationManagerConfiguration $manager): Tabs\Tab => self::make($manager, lazy: $lazy),
            array_values($managers),
        );
    }
}
