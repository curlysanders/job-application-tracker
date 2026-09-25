<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRateProvider;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ApplicationSource;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyData;
use CurlySanders\JobApplicationTracker\UI\Form\TechStack\TechStackTagsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<VacancyData> */
final class VacancyType extends AbstractType
{
    public function __construct(private readonly CompanyRepository $companies, private readonly RecruiterRepository $recruiters, private readonly ExchangeRateProvider $exchangeRates)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Job title'])
            ->add('companyId', ChoiceType::class, ['required' => false, 'placeholder' => 'No company selected', 'label' => 'Company', 'choices' => $this->companyChoices()])
            ->add('recruiterId', ChoiceType::class, ['required' => false, 'placeholder' => 'No recruiter selected', 'label' => 'Recruiter', 'choices' => $this->recruiterChoices()])
            ->add('location', TextType::class, ['required' => false, 'label' => 'Location'])
            ->add('applicationSource', EnumType::class, ['required' => false, 'placeholder' => 'Select source', 'label' => 'Application source', 'class' => ApplicationSource::class, 'choice_label' => static fn (ApplicationSource $source): string => str_replace('_', ' ', $source->value)
                    |> strtolower(...)
                    |> ucwords(...)])
            ->add('sourceUrls', CollectionType::class, ['entry_type' => UrlType::class, 'entry_options' => ['required' => false, 'label' => 'Source URL'], 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'required' => false, 'label' => 'Source URLs'])
            ->add('howToApply', TextareaType::class, ['required' => false, 'label' => 'How to apply'])
            ->add('contractType', EnumType::class, ['required' => false, 'placeholder' => 'Select contract type', 'label' => 'Contract type', 'class' => ContractType::class, 'choice_label' => static fn (ContractType $type): string => str_replace('_', ' ', $type->value)
                    |> strtolower(...)
                    |> ucwords(...)])
            ->add('datePosted', DateType::class, ['required' => false, 'widget' => 'single_text', 'input' => 'datetime_immutable', 'label' => 'Date posted'])
            ->add('deadline', DateType::class, ['required' => false, 'widget' => 'single_text', 'input' => 'datetime_immutable', 'label' => 'Application deadline'])
            ->add('dateApplied', DateType::class, ['required' => false, 'widget' => 'single_text', 'input' => 'datetime_immutable', 'label' => 'Date applied'])
            ->add('fullText', TextareaType::class, ['required' => false, 'label' => 'Full vacancy text'])
            ->add('requirements', TextareaType::class, ['required' => false, 'label' => 'Requirements'])
            ->add('responsibilities', TextareaType::class, ['required' => false, 'label' => 'Responsibilities'])
            ->add('preferredQualifications', TextareaType::class, ['required' => false, 'label' => 'Preferred qualifications'])
            ->add('aboutJob', TextareaType::class, ['required' => false, 'label' => 'About the job'])
            ->add('aboutCompany', TextareaType::class, ['required' => false, 'label' => 'About the company'])
            ->add('compensationBenefits', TextareaType::class, ['required' => false, 'label' => 'Compensation and benefits'])
            ->add('minimumSalary', TextType::class, ['required' => false, 'label' => 'Minimum gross monthly salary', 'attr' => ['inputmode' => 'decimal', 'placeholder' => '4500.00', 'data-vacancy-authoring-target' => 'minimumSalary', 'data-action' => 'input->vacancy-authoring#update']])
            ->add('maximumSalary', TextType::class, ['required' => false, 'label' => 'Maximum gross monthly salary', 'attr' => ['inputmode' => 'decimal', 'placeholder' => '6000.00', 'data-vacancy-authoring-target' => 'maximumSalary', 'data-action' => 'input->vacancy-authoring#update']])
            ->add('currencyCode', ChoiceType::class, ['label' => 'Currency', 'choices' => $this->currencyChoices($options['data']), 'attr' => ['data-vacancy-authoring-target' => 'currency', 'data-action' => 'change->vacancy-authoring#update']])
            ->add('workMode', EnumType::class, ['required' => false, 'placeholder' => 'Select work mode', 'label' => 'Work mode', 'class' => WorkMode::class, 'choice_label' => static fn (WorkMode $mode): string => ucfirst(strtolower($mode->value)), 'attr' => ['data-vacancy-authoring-target' => 'workMode', 'data-action' => 'change->vacancy-authoring#update']])
            ->add('hybridDetails', TextareaType::class, ['required' => false, 'label' => 'Hybrid schedule', 'attr' => ['data-vacancy-authoring-target' => 'hybridDetails']])
            ->add('techStacks', TechStackTagsType::class, ['label' => false])
            ->add('excitement', ChoiceType::class, ['required' => false, 'expanded' => true, 'label' => 'Excitement rating', 'choices' => [0, 1, 2, 3, 4, 5]]);
    }

    /** @return array<string, int> */
    private function companyChoices(): array
    {
        $choices = [];
        foreach ($this->companies->search('') as $company) {
            $id = $company->getId();
            if (null !== $id) {
                $choices[$company->getName()] = $id;
            }
        }

        return $choices;
    }

    /** @return array<string, int> */
    private function recruiterChoices(): array
    {
        $choices = [];
        foreach ($this->recruiters->search('') as $recruiter) {
            $id = $recruiter->getId();
            if (null !== $id) {
                $choices[$recruiter->getAgencyName()] = $id;
            }
        }

        return $choices;
    }

    /** @return array<string, string> */
    private function currencyChoices(mixed $data): array
    {
        $choices = [];
        $supported = array_flip($this->exchangeRates->supportedCurrencyCodes());
        foreach (CurrentIsoCurrencies::all() as $currency) {
            if (isset($supported[$currency->code])) {
                $choices[sprintf('%s — %s', $currency->code, $currency->name)] = $currency->code;
            }
        }
        if ($data instanceof VacancyData && !in_array($data->currencyCode, $choices, true)) {
            $legacyCurrency = CurrentIsoCurrencies::find($data->currencyCode);
            if (null !== $legacyCurrency) {
                $choices[sprintf('%s — unavailable for conversion', $legacyCurrency->code)] = $legacyCurrency->code;
            }
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VacancyData::class]);
    }
}
