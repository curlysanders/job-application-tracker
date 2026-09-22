<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;

final class DirectContactInputFactory
{
    /**
     * @param list<DirectContactData> $contacts
     *
     * @return list<DirectContactInput>
     */
    public static function fromForm(array $contacts): array
    {
        return array_map(
            static fn (DirectContactData $contact): DirectContactInput => new DirectContactInput($contact->name ?? '', $contact->email, $contact->phone, $contact->linkedinUrl),
            $contacts,
        );
    }
}
