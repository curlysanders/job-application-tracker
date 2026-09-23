<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\TechStack;

use CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack\NewTechStackData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** @extends AbstractType<NewTechStackData> */
final class NewTechStackType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Technology name'])
            ->add('category', TextType::class, [
                'label' => 'Category',
                'help' => 'Choose an existing category or enter a new one.',
                'autocomplete' => true,
                'autocomplete_url' => $this->urls->generate('app_tech_stack_category_autocomplete'),
                'tom_select_options' => ['create' => true, 'maxOptions' => null],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => NewTechStackData::class]);
    }
}
