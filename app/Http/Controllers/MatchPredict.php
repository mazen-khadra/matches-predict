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
            ->where('is_success', true)->count();
        $coins = $successCnt * 10;

        return ["allCnt" => $allCnt, "successCnt" => $successCnt, "coins" => $coins];
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

    public function verifyPredictions()
    {
        $unVerifiedPredictions = UserPredictionModel::whereNull('is_success')->select()->get();
        $matchesCach = [];
        foreach ($unVerifiedPredictions as $pred) {
            $isSuccess = false;
            $matchId = $pred['match_id'];
            $match = $matchesCach[$matchId] ?? (new SportAPI())->getMatchDetailsByDev($matchId);
            if (!empty($match)) {
                $matchesCach[$matchId] = $match;
            }

            if ($match['status'] != 3 && $match['status_code'] != 100) {
                continue;
            }

            if ($match['winner'] == "1" && $pred['winner_team_id'] == $match['home_team_id']) {
                $isSuccess = true;
            } else if ($match['winner'] == "2" && $pred['winner_team_id'] == $match['away_team_id']) {
                $isSuccess = true;
            } else if ($match['winner'] == "3" && $pred['draw']) {
                $isSuccess = true;
            }

            UserPredictionModel::whereKey($pred['id'])->update(['is_success' => $isSuccess]);
        }
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
    public function getMatchesByDev(Request $request)
    {
        $sportType = $request->query('sportType');
        $lang = $request->query('lang');
        $timeZone = $request->query('timezone');
        $date = $request->query('date');

        $abcd = new SportAPI();
        $response = $abcd->getMatchListByDev($sportType, $lang, $timeZone, $date);

        $winStats = [];
        foreach ($response['data'] as &$data) {
            foreach ($data['matches'] as &$matchId) {
                if (UserPredictInfo::where('match_id', $matchId['slug'])->exists()) {
                    $allCnt = UserPredictInfo::where('match_id', $matchId['slug'])->count();
                    $winStats['home_team_cnt'] = UserPredictInfo::where(['match_id' => $matchId['slug'], 'winner_team' => 'home_team'])->get()->count();
                    $winStats['away_team_cnt'] = UserPredictInfo::where(['match_id' => $matchId['slug'], 'winner_team' => 'away_team'])->get()->count();
                    $drawCnt = UserPredictInfo::where('match_id', $matchId['slug'])->where(['match_id' => $matchId['slug'], 'draw' => true])->count();
                    $matchId['pred_stats'] = ["allCnt" => $allCnt, "winStats" => $winStats, "drawCnt" => $drawCnt];
                }
            }
            return $response['data'];
        }

    }
}
