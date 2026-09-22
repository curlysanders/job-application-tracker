<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Company;

use CurlySanders\JobApplicationTracker\UI\Form\Contact\DirectContactType;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Company\CompanyData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<CompanyData> */
final class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, ['label' => 'Company name'])->add('website', UrlType::class, ['required' => false, 'label' => 'Website'])->add('industry', TextType::class, ['required' => false, 'label' => 'Industry'])->add('directContacts', CollectionType::class, ['entry_type' => DirectContactType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'required' => false, 'label' => 'Direct contacts']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CompanyData::class]);
    }
}
