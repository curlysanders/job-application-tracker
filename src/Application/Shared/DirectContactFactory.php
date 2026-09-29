<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Shared;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;

final class DirectContactFactory
{
    /**
     * @param list<DirectContactInput> $inputs
     *
     * @return list<DirectContact>
     */
    public static function fromInputs(array $inputs): array
    {
        return array_map(
            static fn (DirectContactInput $input): DirectContact => new DirectContact(
                $input->name,
                $input->email,
                $input->phone,
                $input->linkedinUrl,
            ),
            $inputs,
        );
    }
}
