<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
 *    `persistTabInQueryString()` it ends up in the URL) as an argument.
 * 3. **The manager renders INSIDE the edit page `<form>`, and that is fine.** Filament draws
 *    the action modal only once the action is mounted, i.e. by a Livewire DOM patch, not in
 *    the initial HTML — so the nested `<form wire:submit="callMountedAction">` survives in
 *    the browser (an HTML parser would drop it only from raw markup). `hasFormWrapper(): false`
 *    on the Edit page is NOT needed.
 */
class RelationManagerTab
{
    /**
     * @param class-string<RelationManager> $manager
     * @param Htmlable|string|null          $label   the label; null = the manager's `getTitle()`
     * @param BackedEnum|string|null        $icon    the icon; null = the manager's `getIcon()`
     * @param string|null                   $key     the tab key; null = slug of the class name
     * @param bool|null                     $lazy    null = follow the manager's `$isLazy` (Filament's
     *                                               default is lazy); true = force lazy; false = force eager
     */
    public static function make(
        string $manager,
        string|Htmlable|null $label = null,
        string|BackedEnum|null $icon = null,
        ?string $key = null,
        ?bool $lazy = null,
    ): Tabs\Tab {
        return Tabs\Tab::make()
            ->key($key ?? 'relation-manager-'.Str::slug(class_basename($manager)))
            ->label($label ?? static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $manager::getTitle($record, $livewire::class)
                : null)
            ->icon($icon ?? static fn (?Model $record, LivewireComponent $livewire): mixed => $record
                ? $manager::getIcon($record, $livewire::class)
                : null)
            ->badge(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $manager::getBadge($record, $livewire::class)
                : null)
            ->badgeColor(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $manager::getBadgeColor($record, $livewire::class)
                : null)
            ->badgeTooltip(static fn (?Model $record, LivewireComponent $livewire): string|Htmlable|null => $record
                ? $manager::getBadgeTooltip($record, $livewire::class)
                : null)
            ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => $record instanceof Model
                && $record->exists
                && $manager::canViewForRecord($record, $livewire::class))
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
     * @param class-string<RelationManager> $manager
     */
    public static function livewire(string $manager, ?bool $lazy = null): Livewire
    {
        return Livewire::make(
            $manager,
            static function (Model $record, LivewireComponent $livewire) use ($manager, $lazy): array {
                $properties = [
                    'ownerRecord' => $record,
                    'pageClass' => $livewire::class,
                    ...$manager::getDefaultProperties(),
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
        )->key($manager);
    }

    /**
     * One tab per manager class, with default labels, icons and keys.
     *
     * @param array<int, class-string<RelationManager>> $managers
     *
     * @return array<int, Tabs\Tab>
     */
    public static function many(array $managers, ?bool $lazy = null): array
    {
        return array_map(
            static fn (string $manager): Tabs\Tab => self::make($manager, lazy: $lazy),
            array_values($managers),
        );
    }
}
