<?php

namespace App\Exceptions;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Telegram\Bot\BotsManager;

class Handler extends ExceptionHandler
{
    public function __construct(Container $container, BotsManager $botsManager)
    {
        parent::__construct($container);
        $this->botsManager = $botsManager;
    }

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function report(\Throwable $e)
    {
        $response = 'Error from Handler';
        $response .= PHP_EOL . 'Error text: ' . $e->getMessage();
        $response .= PHP_EOL . 'Code: ' . $e->getCode();
        $response .= PHP_EOL . 'File: ' . $e->getFile();
        $response .= PHP_EOL . 'line: ' . $e->getLine();

        $bot = $this->botsManager->bot('ErrorsBot');
        $bot->sendMessage([
            'chat_id' => env('ERRORS_CHAT_ID'),
            'text' => $response,
        ]);
    }
}
