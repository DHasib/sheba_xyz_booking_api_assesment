<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'start_date',
        'end_date',
    ];

    /**
     * A discount belongs to a service.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
