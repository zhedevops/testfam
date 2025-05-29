<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class EventPublisher
{
    public function __construct(
        private HubInterface $hub
    ) {}

    public function publish(string $type, array $data): void
    {
        $update = new Update(
            topics: ['/events'],
            data: json_encode(['type' => $type, 'data' => $data]),
            private: false
        );

        try {
            $this->hub->publish($update);
        } catch (\Exception $e) {
            error_log('Mercure publish failed: ' . $e->getMessage());
            throw $e;
        }
    }
}