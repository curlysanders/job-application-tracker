<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Recruiter;

use CurlySanders\JobApplicationTracker\UI\Form\Contact\AbstractContactableType;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter\RecruiterData;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractContactableType<RecruiterData|null> */
final class RecruiterType extends AbstractContactableType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('agencyName', TextType::class, ['label' => 'Agency name']);
        $this->addWebsite($builder);
        $this->addDirectContacts($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecruiterData::class]);
    }
}
