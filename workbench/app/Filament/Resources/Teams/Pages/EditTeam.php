<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams\Pages;

use Filament\Resources\Pages\EditRecord;
use Workbench\App\Filament\Resources\Teams\TeamResource;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;
}
