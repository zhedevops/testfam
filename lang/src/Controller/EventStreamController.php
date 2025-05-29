<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mercure\HubInterface;

class EventStreamController extends AbstractController
{
    public function mercureStream(HubInterface $hub): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($hub) {
            while (true) {
                $update = $hub->getFactory()->create(
                    '/events',
                    json_encode(['type' => 'ping', 'data' => ['time' => time()]])
                );

                echo "data: " . json_encode($update) . "\n\n";
                ob_flush();
                flush();

                if (connection_aborted()) {
                    break;
                }

                sleep(15); // Keep-alive каждые 15 секунд
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
