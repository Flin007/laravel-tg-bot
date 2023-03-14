<?php

namespace App\Http\Controllers;

use App\Commands\StartCommand;
use App\Models\TelegramUser;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

class WebhookController extends Controller
{
    public function __construct(
        BotsManager $botsManager,
        Client $httpClient,
        StartCommand $startCommand
    ) {
        $this->botsManager = $botsManager;
        $this->httpClient = $httpClient;
        $this->startCommand = $startCommand;
    }

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request): Response
    {
        $webhook = $this->botsManager->bot()->commandsHandler(true);

        //Если это просто обновление статуса и нет сообщений, дальше не обрабатываем
        $isJustUpdate = $this->checkMemberStatus($webhook);
        if ($isJustUpdate){
            return response(null, 200);
        }

        //Обрабатываем callback
        if ($webhook->callbackQuery instanceof CallbackQuery){
            $this->processCallback($webhook);
        }

        $message = $webhook->getMessage();
        //Обрабатываем сообщение, неизвестный инстанс в ошибки
        if ($message instanceof Message){
            $this->processMessage($message);
        }else{
            $this->SendErrorMessageToChannel('Неизвестный $message instanceof', $webhook);
        }

        return response(null, 200);
    }

    /**
     * Проверяем не изменился ли статус пользователя (member, left, kicked)
     * @see https://core.telegram.org/bots/api#chatmembermember
     * @param $webhook
     * @return boolean
     */
    private function checkMemberStatus($webhook): bool
    {
        if ($webhook->my_chat_member) {
            $userId = $webhook->my_chat_member->chat->id;
            $telegramUser = TelegramUser::where('user_id', $userId)->first();
            if ($telegramUser){
                $telegramUser->update([
                   'status' =>  $webhook->my_chat_member->new_chat_member->status,
                ]);
                $telegramUser->save();
            }else{
                $this->SendErrorMessageToChannel('Изменился статус юзера, но его нет в базе', $webhook);
            }
            return !(count($webhook->getMessage()) > 0);
        }
        return false;
    }

    /**
     * Обрабатываем callback для динамических inline кнопок.
     * @param Update $webhook
     * @return void
     */
    private function processCallback(Update $webhook): void
    {
        $userId = $webhook->getChat()->id;
        $messageId = $webhook->getMessage()->messageId;
        //Разделяем строку ответа на массив по разделителю '_'
        //[0] -> Command name
        //[1] -> Command method
        //[2] -> Value for method
        $callbackData = explode('_', $webhook->callbackQuery->data);

        if (count($callbackData) === 3) {
            $className = "App\Commands\\".$callbackData[0]."Command";
            $class = new $className;
            $method = $callbackData[1];
            $value = $callbackData[2];
            $class->$method($userId, $messageId, $value, $this->botsManager);
        }else{
            $this->SendErrorMessageToChannel('Пытались обработать callback, но пришли неверные параметры', $webhook);
        }
    }

    /**
     * Обрабатывает сообщение из вебхука.
     * @param Message $message
     * @return void
     */
    private function processMessage(Message $message): void
    {
        //Если юзет отправил свою локацию
        if ($message->isType('location')){
            $this->weatherInformation($message);
        }
    }

    /**
     * Получаем из геолокации юзера по api погоду и отправляем ему.
     * @see https://openweathermap.org/current
     * @param $message
     * @return void
     */
    private function weatherInformation($message): void
    {
        $location = $message->location;
        $chat = $message->chat;

        $apiToken = env('OPEN_WEATHER_MAP_TOKEN');
        $requestUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$location->latitude}&lon={$location->longitude}&units=metric&lang=ru&appid={$apiToken}";

        $response = $this->httpClient->get($requestUrl);

        $data = json_decode($response->getBody(), false,512, JSON_THROW_ON_ERROR);

        $weatherInfo = 'Позиция: ' . $data->name . PHP_EOL;
        $weatherInfo .= PHP_EOL . 'Температура: ' . $data->main->temp;
        $weatherInfo .= PHP_EOL . 'Атмосферное давление: ' . $data->main->pressure;
        $weatherInfo .= PHP_EOL . 'Влажность: ' . $data->main->humidity;
        $weatherInfo .= PHP_EOL . 'Восход: ' . Carbon::parse($data->sys->sunrise+$data->timezone)->toTimeString();
        $weatherInfo .= PHP_EOL . 'Закат: ' . Carbon::parse($data->sys->sunset+$data->timezone)->toTimeString();

        $bot = $this->botsManager->bot();
        $bot->sendMessage([
            'chat_id' => $chat->id,
            'text' => $weatherInfo,
        ]);
    }

    /**
     * Отправка кастомных ошибок / уведомление в тг канал.
     * @param string $title
     * @param Update $webhook
     * @return void
     */
    private function SendErrorMessageToChannel(string $title, Update $webhook)
    {
        $bot = $this->botsManager->bot('ErrorsBot');
        $response = $title;
        $response .= PHP_EOL . json_encode($webhook);
        $bot->sendMessage([
            'chat_id' => env('ERRORS_CHAT_ID'),
            'text' => $response,
        ]);
    }
}
