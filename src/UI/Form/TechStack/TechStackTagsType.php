<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\TechStack;

use CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack\TechStackTagsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** @extends AbstractType<TechStackTagsData> */
final class TechStackTagsType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('existingTags', TextType::class, [
                'label' => 'Existing technologies',
                'help' => 'Search and select one or more known technologies.',
                'required' => false,
                'autocomplete' => true,
                'autocomplete_url' => $this->urls->generate('app_tech_stack_autocomplete'),
                'tom_select_options' => [
                    'create' => false,
                    'delimiter' => ',',
                    'maxItems' => null,
                    'maxOptions' => null,
                ],
            ])
            ->add('newTags', CollectionType::class, [
                'entry_type' => NewTechStackType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => 'New technologies',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TechStackTagsData::class]);
    }
}
