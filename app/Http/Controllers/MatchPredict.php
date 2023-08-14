<?php

namespace App\Http\Controllers;

use App\Models\UserPredictInfo;
use App\Models\UserPrediction as UserPredictionModel;
use App\Services\TysonSport as SportAPI;
use Illuminate\Http\Request;

class MatchPredict extends Controller
{
    public function getUserStats($userId)
    {
        $allCnt = UserPredictionModel::where('user_id', $userId)->count();
        $successCnt = UserPredictionModel::where('user_id', $userId)
            ->where('is_success', true)->count() * 10;

        return ["allCnt" => $allCnt, "successCnt" => $successCnt, "coins" => $successCnt];
    }

    public function add(Request $req)
    {
        $req->validate(["match_id" => "required"]);
        $data = $req->all();
        $data["user_id"] = $req->user()->id;

        UserPredictionModel::updateOrCreate([
            "match_id" => $data["match_id"],
            "user_id" => $data["user_id"],
        ], $data);

        UserPredictInfo::updateOrCreate([
            "match_id" => $data["match_id"],
            "user_id" => $data["user_id"],
        ], $data);

        return ["message" => "success"];
    }

    public function verifyPredictionsByDev()
    {

        $unVerifiedPredictions = UserPredictionModel::whereNull('is_success')->select()->get();

        $matchesCach = [];
        foreach ($unVerifiedPredictions as &$pred) {
            $matchId = $pred['match_id'];

            $match = $matchesCach[$matchId] ?? (new SportAPI())->getMatchDetailsByDev($matchId);

            if (!empty($match)) {
                $matchesCach[$matchId] = $match;
            }

            $draw = "";
            $winner_team = "";
            $winner_score = "";
            $loser_score = "";
            $is_success = "";

            if (empty($pred['winner_team'])) {
                /**
                 * begin outer if
                 */

                if ($match['data']['home_score'] > $match['data']['away_score'] || $match['data']['home_score'] < $match['data']['away_score']) {
                    if ($match['data']['home_score'] > $match['data']['away_score']) {

                        $draw = 0;
                        $winner_team = "home_team";
                        $winner_score = $match['data']['home_score'];
                        $loser_score = $match['data']['away_score'];
                        $is_success = false;

                    } else if ($match['data']['home_score'] < $match['data']['away_score']) {
                        $draw = 0;
                        $winner_team = "away_team";
                        $winner_score = $match['data']['away_score'];
                        $loser_score = $match['data']['home_score'];
                        $is_success = false;

                    }
                } else {

                    $draw = 1;
                    $winner_team = "0";
                    $winner_score = $match['data']['home_score'];
                    $loser_score = $match['data']['away_score'];
                    $is_success = true;

                }
                /**
                 * end outer if
                 */
            } else if ($pred['winner_team'] == "home_team") {

                if ($match['data']['home_score'] > $match['data']['away_score']) {

                    $draw = 0;
                    $winner_team = "home_team";
                    $winner_score = $match['data']['home_score'];
                    $loser_score = $match['data']['away_score'];
                    $is_success = true;

                } else if ($match['data']['home_score'] < $match['data']['away_score']) {

                    $draw = 0;
                    $winner_team = "away_team";
                    $winner_score = $match['data']['away_score'];
                    $loser_score = $match['data']['home_score'];
                    $is_success = false;

                } else {

                    $draw = 1;
                    $winner_team = "0";
                    $winner_score = $match['data']['home_score'];
                    $loser_score = $match['data']['away_score'];
                    $is_success = false;
                }

            } else if ($pred['winner_team'] == "away_team") {

                if ($match['data']['home_score'] > $match['data']['away_score']) {

                    $draw = 0;
                    $winner_team = "home_team";
                    $winner_score = $match['data']['home_score'];
                    $loser_score = $match['data']['away_score'];
                    $is_success = false;

                } else if ($match['data']['home_score'] < $match['data']['away_score']) {

                    $draw = 0;
                    $winner_team = "away_team";
                    $winner_score = $match['data']['away_score'];
                    $loser_score = $match['data']['home_score'];
                    $is_success = true;
                } else {

                    $draw = 1;
                    $winner_team = "0";
                    $winner_score = $match['data']['home_score'];
                    $loser_score = $match['data']['away_score'];
                    $is_success = false;
                }

            }

            UserPredictionModel::whereKey($pred['id'])->update([
                'draw' => $draw,
                'winner_team' => $winner_team,
                'winner_score' => $winner_score,
                'loser_score' => $loser_score,
                'is_success' => $is_success,
            ]);

        }
    }

   
}
