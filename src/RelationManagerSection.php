<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
 * from the manager exactly as on a tab; the section is hidden on the Create page and when
 * `canViewForRecord()` says no. The resource must return `[]` from `getRelations()`.
 */
class RelationManagerSection
{
    /**
     * @param class-string<RelationManager> $manager
     * @param Htmlable|string|null          $label     null = the manager's `getTitle()`
     * @param BackedEnum|string|null        $icon      null = the manager's `getIcon()`
     * @param bool                          $collapsed start folded
     * @param bool|null                     $lazy      true = mount the manager on first expand; false = eager; null = follow the manager
     * @param string|null                   $key       the section id; null = slug of the class name
     */
    public static function make(
        string $manager,
        string|Htmlable|null $label = null,
        string|BackedEnum|null $icon = null,
        bool $collapsed = true,
        ?bool $lazy = true,
        ?string $key = null,
    ): Section {
        $key ??= 'relation-manager-'.Str::slug(class_basename($manager));

        return Section::make(
            $label ?? static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                ? $manager::getTitle($record, $livewire::class)
                : null,
        )
            ->key($key)
            ->id($key)
            ->icon($icon ?? static fn (?Model $record, LivewireComponent $livewire): mixed => $record
                ? $manager::getIcon($record, $livewire::class)
                : null)
            ->collapsible()
            ->collapsed($collapsed)
            ->afterHeader([
                Text::make(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                    ? $manager::getBadge($record, $livewire::class)
                    : null)
                    ->badge()
                    ->color(static fn (?Model $record, LivewireComponent $livewire): ?string => $record
                        ? $manager::getBadgeColor($record, $livewire::class)
                        : null)
                    ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => $record instanceof Model
                        && filled($manager::getBadge($record, $livewire::class))),
            ])
            ->visible(static fn (?Model $record, LivewireComponent $livewire): bool => $record instanceof Model
                && $record->exists
                && $manager::canViewForRecord($record, $livewire::class))
            ->schema([
                RelationManagerTab::livewire($manager, $lazy),
            ]);
    }
}
