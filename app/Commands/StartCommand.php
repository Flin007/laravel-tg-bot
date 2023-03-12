<?php

namespace App\Commands;

use App\Models\TelegramUser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\User;

class StartCommand extends Command
{
    protected $name = 'start';
    protected $description = 'Start command';

    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $user = $userData instanceof User ? $this->firstOrCreateTelegramUser($userData) : null;

        if ($user){
            $this->replyWithMessage([
                'text' =>
                    'Спасибо, я записал тебя в бд😈'
                    . PHP_EOL . 'Твой тг ID - ' . $user->user_id . ','
                    . PHP_EOL . 'Имя - ' . $user->first_name . ','
                    . PHP_EOL . 'Фамилия - ' . $user->last_name . ','
                    . PHP_EOL . 'Ник - ' . $user->username . ','
                    . PHP_EOL . 'Язык - ' . $user->language_code . ','
                    . PHP_EOL . 'Есть премиум? - ' . $user->is_premium . ','
                    . PHP_EOL . 'Ты бот? - ' . $user->is_bot
            ]);
        }else{
            $this->replyWithMessage([
                'text' => 'Привет мир! Я не смог стырить твою инфу(('
            ]);
        }
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
}
