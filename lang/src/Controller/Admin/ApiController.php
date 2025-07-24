<?php

namespace App\Controller\Admin;

use App\Form\ApiAuthFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiController extends AbstractController
{
    public function index(Request $request): Response
    {
        $error = '';
        $form = $this->createForm(ApiAuthFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userEmail = $form->get('userEmail')->getData();
            $password = $form->get('plainPassword')->getData();
            try {
                $tokenData = json_decode(file_get_contents('http://localhost:'.$_ENV['APP_API_PORT'].'/login', false, stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'content' => json_encode(['email' => $userEmail, 'password' => $password], JSON_THROW_ON_ERROR),
                        'header' => "Content-Type: application/json\r\n"
                    ]
                ])), true, 512, JSON_THROW_ON_ERROR);

                $token = $tokenData['token'];
                $request->getSession()->set('api_token', $token);

                $opts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => "Authorization: Bearer $token\r\n"
                    ]
                ];
                $response = file_get_contents('http://localhost:'.$_ENV['APP_API_PORT'].'/users', false, stream_context_create($opts));
                $data = json_decode($response, true);
                return $this->render('admin/api/index.html.twig', [
                    'users' => $data,
                    'authForm' => null,
                    'error' => $error,
                ]);
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), '401')) {
                    $error .= 'Введён неверный пароль. Попробуйте ещё раз';
                } else {
                    $error .= 'Ошибка API: ' . $e->getMessage();
                }
            }
        }

        return $this->render('admin/api/index.html.twig', [
            'users' => null,
            'authForm' => $form->createView(),
            'error' => $error,
        ]);
    }

    public function userInfo(Request $request): Response
    {
        $error = '';
        $data = null;
        $userId = $request->query->getInt('id');
        if ($userId) {
            $token = $request->getSession()->get('api_token') ?? null;
            if ($token) {
                try {
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "Authorization: Bearer $token\r\n"
                        ]
                    ];
                    $response = file_get_contents('http://localhost:'.$_ENV['APP_API_PORT'].'/users/'.$userId, false, stream_context_create($opts));
                    $data = json_decode($response, true);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), '401')) {
                        $error .= 'Срок действия токена авторизации истёк. Требуется повторная авторизация';
                    } else {
                        $error .= 'Ошибка API: ' . $e->getMessage();
                    }
                }
            } else {
                $error .= 'Токен авторизации не найден. Повторите попытку';
            }
        }

        return $this->render('admin/api/user.html.twig', [
            'user' => $data ,
            'error' => $error,
        ]);
    }
}
