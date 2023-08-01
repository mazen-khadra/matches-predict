<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MatchPredict as MatchPredictController;
use Illuminate\Support\Facades\Log;

class VerifyPredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify-predictions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifying Users Predictions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Start Verifying Predictions");
        (new MatchPredictController())->verifyPredictions();
        Log::info("Finish Verifying Predictions");
    }
}
