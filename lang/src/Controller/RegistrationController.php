<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\VerificationCode;
use App\Form\RegistrationFormType;
use App\Form\RegisterCodeFormType;
use App\Service\ConfirmationCodeSender;
use App\Service\EventPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{
    public function index(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ConfirmationCodeSender $sender,
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $error = '';
        $email = $form->get('email')->getData();
        $isUser = $entityManager->getRepository(User::class)->findBy(['email' => $email, 'isVerified' => true]);
        if ($isUser) {
            $error = 'Пользователь с таким емейл уже зарегистрирован.';
        }
        if (!$error && $form->isSubmitted() && $form->isValid()) {
            $user->setEmail($email);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);

            $verificationCode = bin2hex(random_bytes(32));

            $code = new VerificationCode();
            $code->setEmail($email);
            $code->setCode($verificationCode);

            $entityManager->persist($code);
            $entityManager->flush();

            $sender->send(
                $email,
                $verificationCode
            );

            $request->getSession()->set('registering_user_id', $user->getId());
            $request->getSession()->set('registering_code_id', $code->getId());

            return $this->redirectToRoute('app_registration_code');
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => $error,
        ]);
    }

    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        EventPublisher $publisher
    )
    {

        $error = '';
        $userId = $request->getSession()->get('registering_user_id') ?? 0;
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            $error .= "Пользователь не найден<br>";
        }

        $form = $this->createForm(RegisterCodeFormType::class, $user);
        $form->handleRequest($request);

        $codeId = $request->getSession()->get('registering_code_id') ?? 0;
        $code = $entityManager->getRepository(VerificationCode::class)->find($codeId);

        if (!$code) {
            $error .= "Верификационный код не найден<br>";
        }

        $verificationCode = $form->get('verification_code')->getData();
        if ($code && $form->isSubmitted() && $verificationCode !== $code->getCode()) {
            $error .= "Верификационный код недействительный<br>";
        }

        if (!$error && $form->isSubmitted() && $form->isValid()) {
            $user->setVerificationCode($verificationCode);
            $user->setIsVerified(true);
            $code->setIsUsed(true);
            $code->setUsedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->persist($code);
            $entityManager->flush();

            $publisher->publish('registration', [
                'email' => $user->getEmail(),
                'time' => time()
            ]);

            return $this->redirectToRoute('app_login',
            ['success' => 'Вы успешно зарегистрировались на сайте. Теперь вы можете войти, введя емейл и пароль, указанные при регистрации']);
        }

        return $this->render('registration/register.html.twig', [
            'registrationFinishForm' => $form->createView(),
            'error' => $error,
        ]);
    }
}
