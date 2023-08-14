<?php

namespace App\Http\Controllers;

use App\Models\UserPredictInfo;
use App\Services\TysonSport as SportAPI;
use Illuminate\Http\Request;

class Matches extends Controller
{
    public function getMatchesListByDev(Request $request)
    {
        $sportType = $request->query('sportType');
        $lang = $request->query('lang');
        $timeZone = $request->query('timezone');
        $date = $request->query('date');

        $matchList = new SportAPI();
        $response = $matchList->getMatchListByDev($sportType, $lang, $timeZone, $date);

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
    public function getMatchesDetailsByDev($matchId)
    {

        $matchList = new SportAPI();
        $data = [];
        $winStats = [];
        $response = $matchList->getMatchDetailsByDev($matchId);
        $data[] = $response['data'];
        foreach ($data as &$match) {

            if (UserPredictInfo::where('match_id', $matchId)->exists()) {
                $allCnt = UserPredictInfo::where('match_id', $matchId)->count();
                $winStats['home_team_cnt'] = UserPredictInfo::where(['match_id' => $matchId, 'winner_team' => 'home_team'])->get()->count();
                $winStats['away_team_cnt'] = UserPredictInfo::where(['match_id' => $matchId, 'winner_team' => 'away_team'])->get()->count();
                $drawCnt = UserPredictInfo::where('match_id', $matchId)->where(['match_id' => $matchId, 'draw' => true])->count();
                $match['pred_stats'] = ["allCnt" => $allCnt, "winStats" => $winStats, "drawCnt" => $drawCnt];

            }
            return $data;
        }
    }
}
