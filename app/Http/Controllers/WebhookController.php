<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Telegram\Bot\BotsManager;

class WebhookController extends Controller
{
    public function __construct(
        BotsManager $botsManager,
        Client $httpClient
    ) {
        $this->botsManager = $botsManager;
        $this->httpClient = $httpClient;
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
        $message = $webhook->getMessage();

        $bot = $this->botsManager->bot();

        if ($message->isType('location')){
            $location = $message->location;
            $chat = $message->chat;

            $weatherInfo = $this->weatherInformation($location->latitude, $location->longitude);

            $bot->sendMessage([
                'chat_id' => $chat->id,
                'text' => $weatherInfo,
            ]);
        }

        return response(null, 200);
    }

    private function weatherInformation($latitude, $longitude): string
    {
        $apiToken = env('OPEN_WEATHER_MAP_TOKEN');
        $requestUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&units=metric&lang=ru&appid={$apiToken}";

        $response = $this->httpClient->get($requestUrl);

        $data = json_decode($response->getBody(), false,512, JSON_THROW_ON_ERROR);

        $weatherInfo = 'Позиция: ' . $data->name . PHP_EOL;
        $weatherInfo .= PHP_EOL . 'Температура: ' . $data->main->temp;
        $weatherInfo .= PHP_EOL . 'Атмосферное давление: ' . $data->main->pressure;
        $weatherInfo .= PHP_EOL . 'Влажность: ' . $data->main->humidity;
        $weatherInfo .= PHP_EOL . 'Восход: ' . Carbon::parse($data->sys->sunrise+$data->timezone)->toTimeString();
        $weatherInfo .= PHP_EOL . 'Закат: ' . Carbon::parse($data->sys->sunset+$data->timezone)->toTimeString();

        return $weatherInfo;
    }
}
