<?php

namespace App\MessageHandler;

use App\Message\ConfirmationCodeMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TelegramBot\Api\BotApi;

#[AsMessageHandler]
class ConfirmationCodeHandler
{
    public function __construct(
        private BotApi $telegram
    ) {}

    public function __invoke(ConfirmationCodeMessage $message)
    {
        $text = sprintf(
            "Новый код подтверждения:\nEmail: %s\nКод: %s",
            $message->email,
            $message->code
        );

        $this->telegram->sendMessage(
            $_ENV['TELEGRAM_CHAT_ID'],
            $text
        );
    }
}