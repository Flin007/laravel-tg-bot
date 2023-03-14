<?php

namespace App\Commands;

use App\Models\TelegramUser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\User;

class StartCommand extends Command
{
    protected $name = 'start';
    protected $description = 'Запуск / Перезапуск бота';

    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $user = $userData instanceof User ? $this->firstOrCreateTelegramUser($userData) : null;

        //Remove another Keyboards
        $reply_markup = Keyboard::remove();
        $this->replyWithMessage([
            'text' => 'Welcome!',
            'reply_markup' => $reply_markup,
        ]);

        //Main answer
        $reply_markup = Keyboard::make([
            'inline_keyboard' => [
                [
                    [
                        'text' => '🇷🇺Русский',
                        'callback_data' => 'Start_setLanguage_ru',
                    ],
                    [
                        'text' => '🇬🇧English',
                        'callback_data' => 'Start_setLanguage_eng',
                    ],
                ],
            ],
            'resize_keyboard' => true,
        ]);

        $welcomeText = 'First, select an available language.';
        $welcomeText .= PHP_EOL . 'Для начала выбери доступный язык.';

        $this->replyWithMessage([
            'text' => $welcomeText,
            'reply_markup' =>$reply_markup
        ]);
    }

    public function firstOrCreateTelegramUser(User $userData)
    {
        return TelegramUser::firstOrCreate([
           'user_id' => $userData->id,
        ],[
            'username' => $userData->username,
            'first_name' => $userData->first_name,
            'last_name' => $userData->last_name,
            'language_code' => $userData->language_code,
            'is_premium' => $userData->is_premium,
            'is_bot' => $userData->is_bot,
        ]);
    }

    public function setLanguage(int $userId, int $messageId, string $value, $botsManager): void
    {
        $response = [
          'ru' => '🇷🇺Русский язык установлен по умолчанию.',
          'en' => '🇬🇧English language is set by default.'
        ];
        $weatherText = [
            'ru' => 'Показать погоду в твоем регионе?',
            'en' => '🇬🇧English language is set by default.'
        ];

        if (isset($response[$value])){
            //Update selected_language in DB for user
            $telegramUser = TelegramUser::where('user_id', $userId)->first();
            $telegramUser->update([
                'selected_language' =>  $value,
            ]);
            $telegramUser->save();

            $reply_markup = Keyboard::make([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $weatherText[$value],
                            'callback_data' => '/weather',
                        ],
                    ],
                ],
                'resize_keyboard' => true,
            ]);

            //Send response with change message
            $bot = $botsManager->bot();
            $bot->editMessageText([
                'chat_id'                  => $userId,
                'message_id'               => $messageId,
                'text'                     => $response[$value],
                'reply_markup'             => $reply_markup
            ]);
        }else{
            $response = 'StartCommand:setLanguage - Передан неверный язык';
            $response .= PHP_EOL . '$userId = ' . $userId;
            $response .= PHP_EOL . '$messageId = ' . $messageId;
            $response .= PHP_EOL . '$value = ' . $value;
            $bot = $botsManager->bot('ErrorsBot');
            $bot->sendMessage([
                'chat_id' => env('ERRORS_CHAT_ID'),
                'text' => $response,
            ]);
        }
    }
}
