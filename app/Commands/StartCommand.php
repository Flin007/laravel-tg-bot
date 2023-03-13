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

        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use the same parameters - except chat_id does NOT need to be included in the array.
        $this->replyWithMessage(['text' => 'Привет, вот список доступных команд:']);

        // This will update the chat status to typing...
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        // This will prepare a list of available commands and send the user.
        // First, Get an array of all registered commands
        // They'll be in 'command-name' => 'Command Handler Class' format.
        $commands = $this->getTelegram()->getCommands();

        // Build the list
        $response = '';
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        $reply_markup = Keyboard::remove(); //Deleted keyboard
        // Reply with the commands list
        $this->replyWithMessage([
            'text' => $response,
            'reply_markup' => $reply_markup,
        ]);

        // Trigger another command dynamically from within this command
        // When you want to chain multiple commands within one or process the request further.
        // The method supports second parameter arguments which you can optionally pass, By default
        // it'll pass the same arguments that are received for this command originally.
        //$this->triggerCommand('subscribe');
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
