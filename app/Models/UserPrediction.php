<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPrediction extends Model
{
    use HasFactory;

    protected $guarded = ["id"];
    protected  $fillable = [
        "user_id", "match_id", "winner_team_id",
        "winner_score", "loser_score", "draw"
    ];
}
