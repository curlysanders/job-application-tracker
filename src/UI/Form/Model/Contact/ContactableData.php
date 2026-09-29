<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Contact;

use Symfony\Component\Validator\Constraints as Assert;

abstract class ContactableData
{
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    public ?string $website = null;

    /** @var list<DirectContactData> */
    #[Assert\Valid]
    public array $directContacts = [];
}
