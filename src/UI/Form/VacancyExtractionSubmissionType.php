<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyExtractionSubmissionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<VacancyExtractionSubmissionData> */
final class VacancyExtractionSubmissionType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('vacancyText', TextareaType::class, [
            'label' => 'Vacancy text',
            'help' => 'Paste the vacancy text you want to review. It is encrypted before asynchronous processing.',
            'attr' => ['rows' => 16],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VacancyExtractionSubmissionData::class]);
    }
}
