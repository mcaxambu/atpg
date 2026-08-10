<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberExperience extends Model
{
    protected $fillable = ['member_id', 'title', 'description', 'position'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
