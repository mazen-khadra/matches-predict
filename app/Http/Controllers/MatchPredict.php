<?php

namespace App\Http\Controllers;

use App\Models\UserPrediction as UserPredictionModel;
use App\Services\TysonSport as SportAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MatchPredict extends Controller
{
    public function index(Request $req, $onlyOwn = null, $matchId = null)
    {

        $userId = $onlyOwn ? $req->user()->id : null;
        $matchId = $req->query("matchId") ?? $matchId;

        $data = UserPredictionModel::query()->select()->with('user');

        if (!empty($userId)) {
            $data = $data->where('user_id', $userId);
        }

        if (!empty($matchId)) {
            $data = $data->where('match_id', $matchId);
        }

        return $data->get();
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

        return ["message" => "success"];
    }

    public function getUserStats($userId)
    {
        $allCnt = UserPredictionModel::where('user_id', $userId)->count();
        $successCnt = UserPredictionModel::where('user_id', $userId)
            ->where('is_success', true)->count();
        $coins = $successCnt * 10;

        return ["allCnt" => $allCnt, "successCnt" => $successCnt, "coins" => $coins];
    }

    public function getMatchStats($matchId)
    {
        $allCnt = UserPredictionModel::where('match_id', $matchId)->count();
        $winStats = UserPredictionModel::where('match_id', $matchId)->groupBy('winner_team')
            ->selectRaw('winner_team as team_id, COUNT(*) cnt')->get();
        $drawCnt = UserPredictionModel::where('match_id', $matchId)->where('draw', true)->count();

        return ["allCnt" => $allCnt, "winStats" => $winStats, "drawCnt" => $drawCnt];
    }

    public function verifyPredictions()
    {
        $unVerifiedPredictions = UserPredictionModel::whereNull('is_success')->select()->get();
        $matchesCach = [];
        foreach ($unVerifiedPredictions as $pred) {
            $isSuccess = false;
            $matchId = $pred['match_id'];
            $match = $matchesCach[$matchId] ?? (new SportAPI())->getMatchDetails($matchId);
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

            if (empty($pred['winner_team'])) {
                /**
                 * begin outer if
                 */
                if ($match['data']['home_score'] > $match['data']['away_score'] || $match['data']['home_score'] < $match['data']['away_score']) {
                    if ($match['data']['home_score'] > $match['data']['away_score']) {
                        UserPredictionModel::whereKey($pred['id'])->update([
                            'draw' => 0,
                            'winner_team' => "home_team",
                            'winner_score' => $match['data']['home_score'],
                            'loser_score' => $match['data']['away_score'],
                            'is_success' => false,
                        ]);

                    } else if ($match['data']['home_score'] < $match['data']['away_score']) {
                        UserPredictionModel::whereKey($pred['id'])->update([
                            'draw' => 0,
                            'winner_team' => "away_team",
                            'winner_score' => $match['data']['away_score'],
                            'loser_score' => $match['data']['home_score'],
                            'is_success' => false,
                        ]);

                    }
                } else {

                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 1,
                        'winner_team' => "0",
                        'winner_score' => $match['data']['home_score'],
                        'loser_score' => $match['data']['away_score'],
                        'is_success' => true,
                    ]);

                }
                /**
                 * end outer if
                 */
            } else if ($pred['winner_team'] == "home_team") {

                if ($match['data']['home_score'] > $match['data']['away_score']) {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 0,
                        'winner_team' => "home_team",
                        'winner_score' => $match['data']['home_score'],
                        'loser_score' => $match['data']['away_score'],
                        'is_success' => true,
                    ]);

                } else if ($match['data']['home_score'] < $match['data']['away_score']) {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 0,
                        'winner_team' => "away_team",
                        'winner_score' => $match['data']['away_score'],
                        'loser_score' => $match['data']['home_score'],
                        'is_success' => false,
                    ]);

                } else {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 1,
                        'winner_team' => "0",
                        'winner_score' => $match['data']['home_score'],
                        'loser_score' => $match['data']['away_score'],
                        'is_success' => false,
                    ]);
                }

            } else if ($pred['winner_team'] == "away_team") {

                if ($match['data']['home_score'] > $match['data']['away_score']) {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 0,
                        'winner_team' => "home_team",
                        'winner_score' => $match['data']['home_score'],
                        'loser_score' => $match['data']['away_score'],
                        'is_success' => false,
                    ]);

                } else if ($match['data']['home_score'] < $match['data']['away_score']) {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 0,
                        'winner_team' => "away_team",
                        'winner_score' => $match['data']['away_score'],
                        'loser_score' => $match['data']['home_score'],
                        'is_success' => true,
                    ]);
                } else {
                    UserPredictionModel::whereKey($pred['id'])->update([
                        'draw' => 1,
                        'winner_team' => "0",
                        'winner_score' => $match['data']['home_score'],
                        'loser_score' => $match['data']['away_score'],
                        'is_success' => false,
                    ]);
                }
            }
        }
    }
    public function getMatchesByDev(Request $request)
    {
        $sportType = $request->query('sportType');
        $lang = $request->query('lang');
        $timeZone = $request->query('timezone');
        $date = $request->query('date');

        $response = Http::withoutVerifying()->send('GET', 'https://app.8com.cloud/api/v1/sportscore/data/match.php', [
            'body' => json_encode([
                "lang" => $lang,
                "date" => $date,
                "sport" => $sportType,
                "timezone" => $timeZone,
            ]),
        ])->json();

        return $response;
    }

}
