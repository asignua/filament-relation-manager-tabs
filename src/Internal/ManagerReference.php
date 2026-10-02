<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs\Internal;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Shared plumbing of the tab and the section: the manager class behind a reference, the
 * default key and the key check.
 *
 * @internal
 */
final class ManagerReference
{
    /**
     * Filament pastes the key raw into an Alpine string (`x-on:click="tab = '<key>'"`) and
     * double-escaped into the panel, so anything beyond these characters either opens an
     * empty tab (a backslash) or breaks the expression (a quote).
     */
    private const KEY_PATTERN = '/^[A-Za-z0-9_.\-]+$/';

    /**
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     *
     * @return class-string<RelationManager>
     */
    public static function className(string|RelationManagerConfiguration $manager): string
    {
        return $manager instanceof RelationManagerConfiguration ? $manager->relationManager : $manager;
    }

    /**
     * Properties the reference adds on top of the manager's defaults (a `Manager::make([...])`
     * configuration), exactly as stock `getRelations()` merges them.
     *
     * @param class-string<RelationManager>|RelationManagerConfiguration $manager
     *
     * @return array<string, mixed>
     */
    public static function properties(string|RelationManagerConfiguration $manager): array
    {
        return $manager instanceof RelationManagerConfiguration ? $manager->getProperties() : [];
    }

    /**
     * The given key, checked, or the default one: a slug of the class basename.
     *
     * @param class-string<RelationManager> $manager
     */
    public static function key(string $manager, ?string $key): string
    {
        if ($key === null) {
            return 'relation-manager-'.Str::slug(class_basename($manager));
        }

        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The key "%s" of the relation manager %s may contain only letters, digits, "-", "_" and ".": Filament pastes it into an Alpine expression as is.',
                $key,
                $manager,
            ));
        }

        return $key;
    }
}
