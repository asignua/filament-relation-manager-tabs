<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A manager with a deferred badge and the icon after the label.
 */
class DeferredBadgePostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Deferred posts';

    protected static bool $isBadgeDeferred = true;

    protected static IconPosition $iconPosition = IconPosition::After;

    public static int $badgeCalls = 0;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        self::$badgeCalls++;

        return 'late#'.$ownerRecord->posts()->count();
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
