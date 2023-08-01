<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User as UserModel;

class UserPrediction extends Model
{
    use HasFactory;

    protected $guarded = ["id"];
    protected  $fillable = [
        "user_id", "match_id", "winner_team_id",
        "winner_score", "loser_score", "draw"
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(UserModel::class);
    }
}
