<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\BotsManager;

class WebhookController extends Controller
{
    public function __construct(BotsManager $botsManager)
    {
        $this->botsManager = $botsManager;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $webhook = $this->botsManager->bot()->commandsHandler(true);

        return response(null, 200);
    }
}
