<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEmployee extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = 'service_employee';

    protected $fillable = [
        'service_id',
        'user_id',
    ];

    /**
     * Pivot belongs to a service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Pivot belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
