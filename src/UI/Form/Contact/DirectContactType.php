<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Contact;

use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<DirectContactData> */
final class DirectContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, ['label' => 'Name'])->add('email', EmailType::class, ['required' => false, 'label' => 'Email'])->add('phone', TelType::class, ['required' => false, 'label' => 'Phone'])->add('linkedinUrl', UrlType::class, ['required' => false, 'label' => 'LinkedIn URL']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DirectContactData::class]);
    }
}
