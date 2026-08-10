<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCertification extends Model
{
    protected $fillable = ['member_id', 'title', 'issuer', 'year', 'position'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
