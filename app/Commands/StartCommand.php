<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected $name = 'start';
    protected $description = 'Start command';

    public function handle()
    {
        $this->replyWithMessage([
           'text' => 'Привет мир'
        ]);
    }
}
