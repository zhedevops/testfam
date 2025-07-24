<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiAuthFormType extends AbstractType
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $builder
            ->add('userEmail', HiddenType::class, [
                'data' => $user instanceof User ? $user->getEmail() : null,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Пароль',
                'mapped' => false,
                'label_attr' => ['class' => 'text-field__label'],
                'attr' => ['autocomplete' => 'new-password', 'class' => 'text-field__input'],
            ]);
    }
}