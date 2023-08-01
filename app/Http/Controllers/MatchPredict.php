<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPrediction as UserPredictionModel;
use App\Services\TysonSport as SportAPI;

class MatchPredict extends Controller
{
    public function index(Request $req, $onlyOwn = null, $matchId = null) {
        $userId = $onlyOwn ? $req->user()->id : null;
        $matchId = $req->query("matchId") ?? $matchId;

        $data = UserPredictionModel::query()->select()->with('user');

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

        return ["message" => "success"];
    }

    public function getMatchStats($matchId) {
        $allCnt = UserPredictionModel::where('match_id', $matchId)->count();
        $winStats = UserPredictionModel::where('match_id', $matchId)->groupBy('winner_team_id')
            ->selectRaw('winner_team_id as team_id, COUNT(*) cnt')->get();
        $drawCnt = UserPredictionModel::where('match_id', $matchId)->where('draw', true)->count();

        return ["allCnt" => $allCnt, "winStats" => $winStats, "drawCnt" => $drawCnt];
    }

    public function getUserStats($userId) {
        $allCnt = UserPredictionModel::where('user_id', $userId)->count();
        $successCnt = UserPredictionModel::where('user_id', $userId)
            ->where('is_success', true)->count();

        return ["allCnt" => $allCnt, "successCnt" => $successCnt];
    }

    public function verifyPredictions() {
        $unVerifiedPredictions =  UserPredictionModel::whereNull('is_success')->select()->get();
        $matchesCach = [];
        foreach($unVerifiedPredictions as $pred) {
            $isSuccess = false;
            $matchId = $pred['match_id'];
            $match = $matchesCach[$matchId] ?? (new SportAPI())->getMatchDetails($matchId);
            if(!empty($match))
                $matchesCach[$matchId] = $match;

            if($match['status'] != 3 && $match['status_code'] != 100)
                continue;

            if($match['winner'] == "1" && $pred['winner_team_id'] == $match['home_team_id'])
                    $isSuccess = true;
            else if ($match['winner'] == "2" && $pred['winner_team_id'] == $match['away_team_id'])
                $isSuccess = true;
            else if($match['winner'] == "3" && $pred['draw'])
                $isSuccess = true;

            UserPredictionModel::whereKey($pred['id'])->update(['is_success' => $isSuccess]);
        }
    }

}
