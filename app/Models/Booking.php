<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'service_id',
        'user_id',
        'contact_name',
        'contact_phone',
        'service_location',
        'status',
        'scheduled_at',
        'unique_id',
    ];

    /**
     * A booking belongs to a service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * A booking belongs to a user (customer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
