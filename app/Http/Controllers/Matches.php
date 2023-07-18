<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TysonSports as SportAPI;

class Matches extends Controller
{
    public function index(Request $req, $sport = null, $leagueId = null, $daysOffset = null) {
      $sport = $sport ?? $req->query('sport');
      $leagueId = $leagueId ?? $req->query('leagueId');
      $daysOffset = $daysOffset ?? $req->query('daysOffset');
      $sportId = SportAPI::$SPORTS_IDS[$sport];

      $data = (new SportAPI())->getMatches($sportId, $leagueId, $daysOffset);
      return $data;
    }

    public function details($matchId) {
      $data = (new SportAPI())->getMatchDetails($matchId);
      return $data;
    }
}
