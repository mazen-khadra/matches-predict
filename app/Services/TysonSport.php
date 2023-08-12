<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TysonSport
{
    function getMatchListByDev($sportType, $lang, $timeZone, $date)
    {
        try {
            $response = Http::withoutVerifying()->send('GET', 'https://app.8com.cloud/api/v1/sportscore/data/match.php', [
                'body' => json_encode([
                    "lang" => $lang,
                    "date" => $date,
                    "sport" => $sportType,
                    "timezone" => $timeZone,
                ]),
            ])->json();

            return $response;

        } catch (\Throwable $e) {}

        return [];
    }

    function getMatchDetailsByDev($matchId)
    {
        try {
            $response = Http::withoutVerifying()->send('GET', 'https://app.8com.cloud/api/v1/sportscore/data/match.php', [
                'body' => json_encode([
                    "lang" => "en",
                    "slug" => $matchId,
                    "sport" => "football",
                    "timezone" => "+08:00",
                ]),
            ])->json();

            return $response;

        } catch (\Throwable $e) {}

        return [];
    }

}
