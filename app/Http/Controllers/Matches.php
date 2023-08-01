<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TysonSport as SportAPI;
use App\Http\Controllers\MatchPredict as MatchPredictController;

class Matches extends Controller
{
    public function index (
        Request $req, $sport = null, $leagueId = null,
        $daysOffset = null, $useAltSvc = null
    ) {
      $sport = $sport ?? $req->query('sport');
      $leagueId = $leagueId ?? $req->query('leagueId');
      $daysOffset = $daysOffset ?? $req->query('daysOffset');
      $useAltSvc = $useAltSvc ?? $req->query('useAltSvc') ?? true;
      $sportId = SportAPI::$SPORTS_IDS[$sport];

      $data = (new SportAPI())->getMatches (
        $sportId, $leagueId, $daysOffset, $useAltSvc
      );

      $matchPredCont = new MatchPredictController();

      foreach($data as &$match) {
          $match['pred_stats'] = $matchPredCont->getMatchStats($match['id']);
      }

      return $data;
    }

    public function details(Request $req, $matchId) {
      $user = $req->user('sanctum');
      $userId = !empty($user) ? $user->id : null;
      $data = (new SportAPI())->getMatchDetails($matchId);
      $predStats = (new MatchPredictController())->index($req, false, $matchId);
      $finalPredStats = ["home" => [], "away" => [], "draws" => []];


      foreach ($predStats as $pred) {
          if($pred["user_id"] == $userId)
              $finalPredStats['current_user'] = $pred;
          else if($pred["draw"])
              $finalPredStats["draws"][] = $pred->user;
          else if($pred["winner_team_id"] == $data["home_team_id"])
              $finalPredStats["home"][] = $pred->user;
          else if($pred["winner_team_id"] == $data["away_team_id"])
              $finalPredStats["away"][] = $pred->user;
      }
      $data["pred_stats"] = $finalPredStats;
      return $data;
    }
}
