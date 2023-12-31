<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MatchPredict as MatchPredictController;
use App\Services\TysonSport as SportAPI;
use Illuminate\Http\Request;

class Matches extends Controller
{
    public function index(
        Request $req, $sport = null, $leagueId = null,
        $daysOffset = null, $useAltSvc = null
    ) {
        $sport = $sport ?? $req->query('sport');
        $leagueId = $leagueId ?? $req->query('leagueId');
        $daysOffset = $daysOffset ?? $req->query('daysOffset');
        $useAltSvc = $useAltSvc ?? $req->query('useAltSvc') ?? true;
        $sportId = SportAPI::$SPORTS_IDS[$sport];

        $data = (new SportAPI())->getMatches(
            $sportId, $leagueId, $daysOffset, $useAltSvc
        );

        $matchPredCont = new MatchPredictController();

        foreach ($data as &$match) {
            $predStats = $matchPredCont->getMatchStats($match['id']);
            $homeTeamCnt = 0;
            $awayTeamCnt = 0;
            $match["pred_stats"] = $predStats;
            foreach ($predStats["winStats"] as $stat) {
                if ($stat["team_id"] == $match["home_team_id"]) {
                    $homeTeamCnt = $stat["cnt"];
                } elseif ($stat["team_id"] == $match["away_team_id"]) {
                    $awayTeamCnt = $stat["cnt"];
                }

            }

            $match["pred_stats"]["winStats"] = ["home_team_cnt" => $homeTeamCnt, "away_team_cnt" => $awayTeamCnt];
        }

        return $data;
    }

    public function details(Request $req, $matchId)
    {
        $user = $req->user('sanctum');
        $userId = !empty($user) ? $user->id : null;
        $data = (new SportAPI())->getMatchDetails($matchId);
        $predStats = (new MatchPredictController())->index($req, false, $matchId);
        $finalPredStats = ["home" => [], "away" => [], "draws" => []];

        foreach ($predStats as $pred) {
            $userInfo = [
                "name" => $pred->user["name"],
                "img_url" => $pred->user["img_url"],
                "pred_stats" => $pred->user["pred_stats"],
            ];
            if ($pred["user_id"] == $userId) {
                $finalPredStats['current_user'] = [
                    "for_home" => $pred["winner_team_id"] == $data["home_team_id"],
                    "for_away" => $pred["winner_team_id"] == $data["away_team_id"],
                    "draw" => boolval($pred["draw"]),
                    "is_success" => boolval($pred["is_success"]),
                ];
            } else if ($pred["draw"]) {
                $finalPredStats["draws"][] = $userInfo;
            } else if ($pred["winner_team_id"] == $data["home_team_id"]) {
                $finalPredStats["home"][] = $userInfo;
            } else if ($pred["winner_team_id"] == $data["away_team_id"]) {
                $finalPredStats["away"][] = $userInfo;
            }

        }
        $data["pred_stats"] = $finalPredStats;
        return $data;
    }
}
