<?php


namespace App\Message;


class ConfirmationCodeMessage
{
    public function __construct(
        public string $email,
        public string $code,
    ) {}
}