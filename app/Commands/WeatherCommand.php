<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class WeatherCommand extends Command
{
    protected $name = 'weather';
    protected $description = 'Информация о погоде';

    public function handle()
    {
        $keyboard = new Keyboard();

        $button = Keyboard::button([
            'text' => 'Отправить координаты',
            'request_location' => true,
        ]);

        $keyboard->setResizeKeyboard(true);
        $keyboard->setOneTimeKeyboard(true);

        $keyboard->row($button);

        $this->replyWithMessage([
            'text' => 'Для отображения погоды, нажмите на кнопку `Отправить коордианты`',
            'reply_markup' => $keyboard,
        ]);
    }
}
