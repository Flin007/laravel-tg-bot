<?php

namespace App\Commands;

use App\Models\TelegramUser;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\User;

class StartCommand extends Command
{
    protected $name = 'start';
    protected $description = 'Стартовая команда';

    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $user = $userData instanceof User ? $this->firstOrCreateTelegramUser($userData) : null;
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
