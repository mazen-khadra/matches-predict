<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TysonSport
{
    public function getPreviousAndNextMatches()
    {

        $response = Http::withoutVerifying()->send('GET', 'https://app.8com.cloud/api/v1/sportscore/data/match.php', [
            'body' => json_encode([
                "lang" => "en",
                "date" => "2023-08-09",
                "sport" => "football",
                "timezone" => "+04:00",
            ]),
        ])->json();

        return $response;
    }

    function getMatchDetails($matchId)
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
