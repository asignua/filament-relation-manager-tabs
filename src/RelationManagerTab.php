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
 *    holding a backslash, a quote, a backtick, `<`, `>` or a control character, is rejected.
 * 3. **The manager renders INSIDE the edit page `<form>`, and that is fine.** Filament draws
 *    the action modal only once the action is mounted, i.e. by a Livewire DOM patch, not in
 *    the initial HTML — so the nested `<form wire:submit="callMountedAction">` survives in
 *    the browser (an HTML parser would drop it only from raw markup). `hasFormWrapper(): false`
 *    on the Edit page is NOT needed.
 */
class RelationManagerTab
{
    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager a manager class, or
     *                                                                            `Manager::make([...])` to pass it properties
     * @param Htmlable|string|null                                       $label   the label; null = the manager's `getTitle()`
     * @param BackedEnum|string|null                                     $icon    the icon; null = the manager's `getIcon()`
     * @param string|null                                                $key     the tab key (no `\`, quotes, backtick, `<`, `>` or control characters);
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
            ->badge(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $class::getBadge($record, $livewire::class)
                : null)
            // `$isBadgeDeferred` on the manager: the page renders without the badge and fetches it
            // in a separate request, exactly as on a stock relation-manager tab.
            ->deferBadge(static fn (?Model $record, LivewireComponent $livewire): bool => $record
                && $class::isBadgeDeferred($record, $livewire::class))
            ->badgeColor(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $class::getBadgeColor($record, $livewire::class)
                : null)
            ->badgeTooltip(static fn (?Model $record, LivewireComponent $livewire): string|Htmlable|null => $record
                ? $class::getBadgeTooltip($record, $livewire::class)
                : null)
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
        )->key($class);
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
