<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
class Team extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
