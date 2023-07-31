<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TysonSports {

  static $SPORTS_IDS = [
    "football" => 1,
    "basketball" => 2,
    "baseball" => 3,
    "hotckey" => 4,
    "tennis" => 5,
    "handball" => 6,
    "volleyball" => 7,
    "rugby" => 8,
    "cricket" => 9,
    "snooker" => 10,
    "beachfootball" => 11,
    "badminton" => 12,
    "pingpong" => 13,
    "golf" => 14,
    "Waterpolo" => 16,
    "3x3basketball" => 21
  ];

  private $uri = '';
  private $altSvcUri = '';
  private $user = '';
  private $code = '';
  private $secretKey = '';
  private $headers = [];
  private $IMG_DOMAIN = 'https://Img.tysondata.com';
  private $ANIMATE_BASE_URL = 'http://lmt.tysondata.com/sc/index.jsp';

  function __construct() {
    $this->uri = 'http://datafeed2.tysondata.com:8080/';
    $this->altSvcUri = 'http://107.151.150.20:9092/v1/';
    $this->code = 'bf97878e990b3b99b2db8f1a6a77ecf7';
    $this->user = 'mtty';
    $this->secretKey = 'b94b91496b2bb4c6be8b655c22b53fdb';
    $this->headers = ['Accept-Encoding' => 'gzip, deflate'];
  }

  function getAuthParams($asStr = false) {
    $timeStamp =  date("YmdH");
    $auth_token = md5(md5($this->user) . $this->secretKey . $this->code . $timeStamp);

    if(!empty($asStr))
        return "t={$timeStamp}&code={$this->code}&auth_token=$auth_token";

    return ["t"=>$timeStamp, "code"=>$this->code, "auth_token"=>$auth_token];
  }

  private function getFiltersParams($daysOffset = 0, $sportId = null, $leagueId = null) {
    $res = [];

    $res["date"] = date("Y-m-d", strtotime("$daysOffset day"));

    if(!empty($sportId))
        $res["sport_id"] = $sportId;
    if(!empty($sportId))
      $res["tournament_id"] = $leagueId;

    return $res;
  }

    function getMatches($sportId = null, $leagueId = null, $daysOffset = 0, $useAltSvc = false) : array {
        dd("TEST_1");
        try {
            if(true) {
                $queryParams = ["sportId" => $sportId, "hotLeague" => $leagueId, "daysOffset" => $daysOffset];
                $queryParams = array_filter($queryParams, function($param) {return $param != null;});
                $res = Http::withoutVerifying()->withHeaders($this->headers)->get (
                    $this->altSvcUri . 'live-matches',
                    $queryParams
                )->json()["data"];
            } else {
                $res = Http::withoutVerifying()->withHeaders($this->headers)->get(
                    $this->uri . 'datashare/matchList',
                    array_merge (
                        $this->getAuthParams(),
                        $this->getFiltersParams($daysOffset, $sportId, $leagueId)
                    )
                )->json();
            }

            return $res;
        } catch(\Throwable $e) { throw $e; }

        return [];
    }

  function getLeagues($sportId, $asIdsMap = false) : array {
    try {
      $leagues = [];

      if(empty($sportId))
          return $leagues;

      $res = Http::withoutVerifying()->withHeaders($this->headers)->get(
        $this->uri . '/datashare/tournamentList?',
        array_merge (
            $this->getAuthParams(),
            $this->getFiltersParams(0, $sportId)
        )
      )->json();

      if(!empty($asIdsMap)) {
        foreach ($res as $league) {
          $leagues[$league['id']] = $league;
        }
      } else {
        $leagues = $res;
      }

      return $leagues;

    } catch(\Throwable $e) {throw $e;}

    return [];
  }

  function getLeagueDetails($leagueId)
  {
    try {
      if (empty($leagueId))
          return null;

      $res = Http::withoutVerifying()->withHeaders($this->headers)->get(
        $this->uri . "/datashare/tournamentInfo",
        array_merge($this->getAuthParams(), ["id" => $leagueId])
      )->json();

      if (!empty($res["logo_url"]))
        $res["logo_url"] = str_replace("_file", $this->IMG_DOMAIN, $res["logo_url"]);

      return $res;
    } catch (\Throwable $e) {}

    return [];
  }

  function getTeamDetails($teamId) {
    try {
      if(empty($teamId))
          return null;


      $res = Http::withoutVerifying()->withHeaders($this->headers)->get (
        $this->uri . "/datashare/teamInfo",
          array_merge($this->getAuthParams(), ["id" => $teamId])
      )->json();

      if(!empty($res["logo_url"]))
        $res["logo_url"] = str_replace("_file", $this->IMG_DOMAIN, $res["logo_url"]);

      return $res;

    } catch(\Throwable $e) {}

    return [];
  }


  function getMatchDetails($matchId) {
      try {
          if(empty($matchId))
              return null;

          $res = Http::withoutVerifying()->withHeaders($this->headers)->get (
              $this->uri . "/datashare/matchInfo",
              array_merge($this->getAuthParams(), ["id" => $matchId])
          )->json();

          $res["animation_url"] = $this->getMatchAnimationUrl($res);
          return $res;

        } catch(\Throwable $e) {}

        return [];
  }

  function getPlayerDetails($playerId) {
    try {
        if(empty($playerId))
            return null;

        $res = Http::withoutVerifying()->withHeaders($this->headers)->get (
            $this->uri . "/datashare/playerInfo",
            array_merge($this->getAuthParams(), ["id" => $playerId])
        )->json();

        return $res;
    } catch(\Throwable $e) {}

    return [];
  }

  function getMatchAnimationUrl($match) {
      if(empty($match["lmt_mode"]))
          return null;
      $authParams = $this->getAuthParams(true);
      $matchId = $match["id"];
      return $this->ANIMATE_BASE_URL . "?matchId=$matchId&$authParams";
  }

}

