<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\Pages;

use Filament\Resources\Pages\CreateRecord;
use Workbench\App\Filament\Resources\Teams\TeamResource;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;
}
