<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs;

use Asignua\FilamentRelationManagerTabs\Internal\ManagerReference;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Livewire\Component as LivewireComponent;

/**
 * A relation manager inside a COLLAPSIBLE section — for pages where tabs are the wrong
 * layout (a long form with related lists below it, each folded away until needed).
 *
 * ```php
 * $schema->components([
 *     Section::make('Details')->schema([...]),
 *     RelationManagerSection::make(PostsRelationManager::class),            // collapsed + lazy
 *     RelationManagerSection::make(MembersRelationManager::class, collapsed: false, lazy: false),
 * ]);
 * ```
 *
 * By default the section starts collapsed and is LAZY: the manager is mounted (and its table
 * queried) only after the section is expanded for the first time. Label, icon and badge come
 * (with the badge colour and tooltip) from the manager as on a tab — a `Section` has no
 * icon position, so the manager's `getIconPosition()` does not apply; the section is hidden on the Create page and when
 * `canViewForRecord()` says no. The resource must return `[]` from `getRelations()`.
 *
 * A section has no deferred-badge request (that is a `Tabs` feature), so a manager's
 * `$isBadgeDeferred` does not apply here: the badge is computed with the page, once.
 */
class RelationManagerSection
{
    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager   a manager class, or `Manager::make([...])`
     * @param Htmlable|string|null                                       $label     null = the manager's `getTitle()`
     * @param BackedEnum|string|null                                     $icon      null = the manager's `getIcon()`
     * @param bool                                                       $collapsed start folded
     * @param bool|null                                                  $lazy      true = mount the manager on first expand; false = eager; null = follow the manager
     * @param string|null                                                $key       the section id (no `\`, quotes, backtick, `&`, `<`, `>` or control characters); null = slug of the class name
     *
     * @throws InvalidArgumentException when `$key` is empty or holds one of those characters
     */
    public static function make(
        string|RelationManagerConfiguration $manager,
        string|Htmlable|null $label = null,
        string|BackedEnum|null $icon = null,
        bool $collapsed = true,
        ?bool $lazy = true,
        ?string $key = null,
    ): Section {
        $class = ManagerReference::className($manager);
        $key = ManagerReference::key($class, $key);

        // The badge is needed twice per render (whether to show it, and its text); `getBadge()`
        // often runs a query, so it is computed once per owner record and page class. The memo
        // lives as long as this Section object, i.e. one request: a badge read before a page
        // action in the same request is served unchanged after it, which is accepted.
        $badge = ManagerReference::memoized(static fn (Model $record, string $page): ?string => $class::getBadge($record, $page));

        return Section::make(
            $label ?? static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $class::getTitle($record, $livewire::class)
                : null,
        )
            ->key($key)
            ->id($key)
            ->icon($icon ?? static fn (?Model $record, LivewireComponent $livewire): mixed => $record
                ? $class::getIcon($record, $livewire::class)
                : null)
            ->collapsible()
            ->collapsed($collapsed)
            ->afterHeader([
                Text::make($badge)
                    ->badge()
                    ->color(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                        ? $class::getBadgeColor($record, $livewire::class)
                        : null)
                    ->tooltip(static fn (?Model $record, LivewireComponent $livewire): string|Htmlable|null => $record
                        ? $class::getBadgeTooltip($record, $livewire::class)
                        : null)
                    ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => filled($badge($record, $livewire))),
            ])
            ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => $record instanceof Model
                && $record->exists
                && $class::canViewForRecord($record, $livewire::class))
            ->schema([
                RelationManagerTab::livewire($manager, $lazy),
            ]);
    }
}
