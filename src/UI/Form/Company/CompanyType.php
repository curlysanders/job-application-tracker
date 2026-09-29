<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Company;

use CurlySanders\JobApplicationTracker\UI\Form\Contact\AbstractContactableType;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractContactableType<CompanyData|null> */
final class CompanyType extends AbstractContactableType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, ['label' => 'Company name']);
        $this->addWebsite($builder);
        $builder->add('industry', TextType::class, ['required' => false, 'label' => 'Industry']);
        $this->addDirectContacts($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CompanyData::class]);
    }
}
