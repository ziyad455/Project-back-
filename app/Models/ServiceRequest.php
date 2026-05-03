<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    protected $fillable = [
        'client_id',
        'city',
        'service_category_id',
        'description',
        'proposed_price',
        'status',
        'selected_provider_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function selectedProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_provider_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(RequestOffer::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
