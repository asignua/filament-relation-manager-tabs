<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\Pages;

use Filament\Resources\Pages\ListRecords;
use Workbench\App\Filament\Resources\Teams\TeamResource;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;
}
