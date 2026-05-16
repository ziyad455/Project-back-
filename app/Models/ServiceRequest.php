<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    protected $hidden = [
        'guest_email',
        'guest_whatsapp_number',
    ];

    protected $fillable = [
        'client_id',           // CANONICAL — the client who owns this request
        'user_id',             // LEGACY alias for client_id (kept for backward compat)
        'client_name',
        'client_email',
        'client_phone',
        'title',
        'service_category_id', // CANONICAL — the service category
        'category_id',         // LEGACY alias for service_category_id (kept for backward compat)
        'guest_name',
        'guest_email',
        'guest_whatsapp_number',
        'description',
        'budget',              // CANONICAL — the price/budget for the request
        'proposed_price',      // LEGACY alias for budget (kept for backward compat)
        'city',
        'status',
        'selected_provider_id',
        'deadline',
    ];

    /**
     * Sync legacy duplicate fields on model creation.
     * When one field in a duplicate pair is set, the other is automatically populated.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->syncDuplicateFields();
        });

        static::updating(function (self $model): void {
            $model->syncDuplicateFields();
        });
    }

    /**
     * Keep legacy fields in sync with canonical fields.
     *  - client_id ↔ user_id  (client_id is canonical)
     *  - service_category_id ↔ category_id  (service_category_id is canonical)
     *  - budget ↔ proposed_price  (budget is canonical)
     */
    public function syncDuplicateFields(): void
    {
        // client_id ↔ user_id
        if ($this->isDirty('client_id') && ! $this->isDirty('user_id')) {
            $this->user_id = $this->client_id;
        } elseif ($this->isDirty('user_id') && ! $this->isDirty('client_id')) {
            $this->client_id = $this->user_id;
        }

        // service_category_id ↔ category_id
        if ($this->isDirty('service_category_id') && ! $this->isDirty('category_id')) {
            $this->category_id = $this->service_category_id;
        } elseif ($this->isDirty('category_id') && ! $this->isDirty('service_category_id')) {
            $this->service_category_id = $this->category_id;
        }

        // budget ↔ proposed_price
        if ($this->isDirty('budget') && ! $this->isDirty('proposed_price')) {
            $this->proposed_price = $this->budget;
        } elseif ($this->isDirty('proposed_price') && ! $this->isDirty('budget')) {
            $this->budget = $this->proposed_price;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

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
