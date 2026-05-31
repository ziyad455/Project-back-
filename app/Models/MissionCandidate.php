<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionCandidate extends Model
{
    protected $fillable = ['mission_id', 'talent_id', 'status'];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'mission_id');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'talent_id');
    }
}
