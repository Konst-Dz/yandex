<?php

namespace App\Modules\Organizations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARSING = 'parsing';
    public const STATUS_READY = 'ready';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'url',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(OrganizationSnapshot::class);
    }
}
