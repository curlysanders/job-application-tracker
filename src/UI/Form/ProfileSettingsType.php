<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRateProvider;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\UI\Form\Model\ProfileSettingsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<ProfileSettingsData> */
final class ProfileSettingsType extends AbstractType
{
    public function __construct(private readonly ExchangeRateProvider $exchangeRates)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('minimumPreferredSalary', TextType::class, [
                'required' => false,
                'label' => 'Minimum preferred gross monthly salary',
                'attr' => ['inputmode' => 'decimal', 'placeholder' => '4500.00'],
            ])
            ->add('minimumPreferredSalaryCurrency', ChoiceType::class, ['label' => 'Preferred salary currency', 'choices' => $this->currencyChoices()])
            ->add('maximumCommuteMinutes', IntegerType::class, [
                'required' => false,
                'label' => 'Maximum one-way commute (minutes)',
                'attr' => ['min' => 1],
            ])
            ->add('preferredTransportMode', EnumType::class, [
                'required' => false,
                'placeholder' => 'No preference set',
                'label' => 'Preferred transport mode',
                'class' => PreferredTransportMode::class,
                'choice_label' => static fn (PreferredTransportMode $mode): string => $mode->label(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProfileSettingsData::class]);
    }

    /** @return array<string, string> */
    private function currencyChoices(): array
    {
        $supported = array_flip($this->exchangeRates->supportedCurrencyCodes());
        $choices = [];
        foreach (CurrentIsoCurrencies::all() as $currency) {
            if (isset($supported[$currency->code])) {
                $choices[sprintf('%s — %s', $currency->code, $currency->name)] = $currency->code;
            }
        }

        return $choices;
    }
}
