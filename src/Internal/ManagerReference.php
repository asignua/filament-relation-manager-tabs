<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs\Internal;

use Closure;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Component as LivewireComponent;

/**
 * Shared plumbing of the tab and the section: the manager class behind a reference, the
 * default key and the key check.
 *
 * @internal
 */
final class ManagerReference
{
    /**
     * Characters a key must not hold. A backslash starts a JS escape in the tab button
     * (`x-on:click="tab = '<key>'"`) but not in the JSON-encoded panel, so the tab opens
     * empty. The rest — `<`, `>`, quotes, backtick, control characters — are what Filament
     * (since 5.x key sanitising) silently strips from keys and ids, so the key in the DOM and
     * in the URL would not be the one you passed; on Filament versions before that sanitising
     * a quote broke the Alpine expression outright. An ampersand
     * is rejected too: the browser decodes character references (`a&lt;b`) in the button's
     * Alpine attribute, while the panel gets the JSON-encoded key, so the two diverge and the
     * tab opens empty. Everything else — spaces, `:`, non-ASCII letters — reaches both sides
     * as the same plain string.
     */
    private const FORBIDDEN_KEY_CHARACTERS = '/[\\\\<>&"\'`\x00-\x1F\x7F]/';

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

        if ($key === '' || preg_match(self::FORBIDDEN_KEY_CHARACTERS, $key) === 1) {
            throw new InvalidArgumentException(sprintf(
                'The key "%s" of the relation manager %s must be non-empty and contain no backslash, quote, backtick, ampersand, angle bracket or control character: Filament pastes it into an Alpine string, or strips such characters.',
                $key,
                $manager,
            ));
        }

        return $key;
    }

    /**
     * Wraps a per-record resolver (badge, colour, tooltip) so it runs once per owner record and
     * page class instead of on every read: `Tabs` reads each tab's badge up to three times per
     * render (nav, dropdown trigger, dropdown list), and `getBadge()` usually runs a query. The
     * memo lives as long as the returned closure, i.e. one request.
     *
     * @template TValue
     *
     * @param Closure(Model, string): TValue $resolve receives the owner record and the page class
     *
     * @return Closure(?Model, LivewireComponent): ?TValue
     */
    public static function memoized(Closure $resolve): Closure
    {
        $memo = [];

        return static function (?Model $record, LivewireComponent $livewire) use ($resolve, &$memo): mixed {
            if (!$record instanceof Model) {
                return null;
            }

            $memoKey = $record::class.'|'.$record->getKey().'|'.$livewire::class;

            if (!array_key_exists($memoKey, $memo)) {
                $memo[$memoKey] = $resolve($record, $livewire::class);
            }

            return $memo[$memoKey];
        };
    }
}
