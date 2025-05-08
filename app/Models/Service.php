<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'description',
        'discount_id',
    ];

    /**
     * A service belongs to a category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * A service has many bookings.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }


    /**
     * Get the discount associated with the service.
     *
     * This method defines the BelongsTo relationship between the Service model and the Discount model.
     *
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * A service can have multiple employees (users).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_employee');
    }
}
