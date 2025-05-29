<?php

namespace App\Service;

use App\Message\ConfirmationCodeMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ConfirmationCodeSender
{
    public function __construct(
        private MessageBusInterface $bus
    ) {}

    public function send(string $email, string $code): void
    {
        $this->bus->dispatch(new ConfirmationCodeMessage(
            email: $email,
            code: $code,
        ));
    }
}