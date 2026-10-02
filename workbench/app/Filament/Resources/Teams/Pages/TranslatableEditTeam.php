<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\Pages;

/**
 * Stands in for a page with spatie-translatable's locale switcher (`Translatable` concern).
 */
class TranslatableEditTeam extends EditTeam
{
    public ?string $activeLocale = 'uk';
}
