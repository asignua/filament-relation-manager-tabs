<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams;

use Asignua\FilamentRelationManagerTabs\RelationManagerTab;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Teams\Pages\CreateTeam;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
use Workbench\App\Filament\Resources\Teams\Pages\ListTeams;
use Workbench\App\Filament\Resources\Teams\Pages\ViewTeam;
use Workbench\App\Filament\Resources\Teams\RelationManagers\HiddenPostsRelationManager;
use Workbench\App\Filament\Resources\Teams\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Team;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')->tabs([
                Tabs\Tab::make('Details')->schema([
                    TextInput::make('name')->required(),
                ]),
                RelationManagerTab::make(PostsRelationManager::class),
                RelationManagerTab::make(HiddenPostsRelationManager::class),
            ])->columnSpanFull(),
        ]);
    }

    /**
     * The View page shows this infolist: the same tab works inside its `Tabs`.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')->tabs([
                Tabs\Tab::make('Overview')->schema([
                    TextEntry::make('name'),
                ]),
                RelationManagerTab::make(PostsRelationManager::class),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }

    /**
     * Empty on purpose: the managers live in the form's tabs.
     */
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'view' => ViewTeam::route('/{record}'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
