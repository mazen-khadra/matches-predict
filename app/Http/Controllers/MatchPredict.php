<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPrediction as UserPredictionModel;

class MatchPredict extends Controller
{
    public function index(Request $req, $onlyOwn = null, $matchId = null) {
        $userId = $onlyOwn ? $req->user()->id : null;
        $matchId = $req->query("matchId") ?? $matchId;

        $data = UserPredictionModel::query()->select();

        if(!empty($userId))
            $data = $data->where('user_id', $userId);
        if(!empty($matchId))
            $data = $data->where('match_id', $matchId);

        return $data->get();
    }

    public function add(Request $req) {
        $req->validate([ "match_id" => "required" ]);
        $data = $req->all();
        $data["user_id"] = $req->user()->id;

        UserPredictionModel::updateOrCreate([
         "match_id" => $data["match_id"],
         "user_id" => $data["user_id"]
        ], $data);
    }

    public function getMatchStats($matchId) {
        $allCnt = UserPredictionModel::where('match_id', $matchId)->count();
        $winStats = UserPredictionModel::where('match_id', $matchId)->groupBy('winner_team_id')
            ->selectRaw('winner_team_id, COUNT(*) cnt')->get();
        $drawCnt = UserPredictionModel::where('match_id', $matchId)->where('draw', true)->count();

        return ["allCnt" => $allCnt, "winCnt" => $winStats, "drawCnt" => $drawCnt];
    }
}
