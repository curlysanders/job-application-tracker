<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ProfileSettingsData
{
    public ?string $minimumPreferredSalary = null;

    public ?int $maximumCommuteMinutes = null;

    public ?PreferredTransportMode $preferredTransportMode = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null !== $this->minimumPreferredSalary && '' !== trim($this->minimumPreferredSalary)
            && 1 !== preg_match('/^(?!0+(?:\.0+)?$)\d+(?:\.\d{1,2})?$/', $this->minimumPreferredSalary)) {
            $context->buildViolation('Enter a positive amount with at most two decimal places.')
                ->atPath('minimumPreferredSalary')
                ->addViolation();
        }

        if (null !== $this->maximumCommuteMinutes && $this->maximumCommuteMinutes <= 0) {
            $context->buildViolation('The commute time must be positive.')
                ->atPath('maximumCommuteMinutes')
                ->addViolation();
        }
    }
}
