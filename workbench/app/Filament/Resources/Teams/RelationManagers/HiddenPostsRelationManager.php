<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HiddenPostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Secret posts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
