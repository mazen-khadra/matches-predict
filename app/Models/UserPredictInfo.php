<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserPredictInfo extends Model
{
    use HasFactory;
    protected $guarded = ["id"];
    protected $fillable = [
        "user_id", "match_id", "winner_team",
       "draw",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }
}
