<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/** @extends AbstractType<User> */
final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
                'label' => 'Email address',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'Password',
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'Confirm password',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 12, minMessage: 'Your password must be at least {{ limit }} characters long.'),
                    new Regex(pattern: '/[A-Z]/', message: 'Your password must contain an uppercase letter.'),
                    new Regex(pattern: '/[a-z]/', message: 'Your password must contain a lowercase letter.'),
                    new Regex(pattern: '/[0-9]/', message: 'Your password must contain a base-10 digit.'),
                    new Regex(pattern: '/[^A-Za-z0-9]/', message: 'Your password must contain a symbol.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
