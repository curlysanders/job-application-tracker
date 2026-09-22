<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Contact;

use Symfony\Component\Validator\Constraints as Assert;

final class DirectContactData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;
    #[Assert\Length(max: 50)]
    public ?string $phone = null;
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    public ?string $linkedinUrl = null;
}
