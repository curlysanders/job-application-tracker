<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Contact;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'direct_contacts')]
#[ORM\Index(name: 'IDX_DIRECT_CONTACT_COMPANY', columns: ['company_id'])]
#[ORM\Index(name: 'IDX_DIRECT_CONTACT_RECRUITER', columns: ['recruiter_id'])]
#[ORM\Index(name: 'IDX_DIRECT_CONTACT_NAME', fields: ['name'])]
final class DirectContact
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'directContacts')]
    #[ORM\JoinColumn(name: 'company_id', nullable: true, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'directContacts')]
    #[ORM\JoinColumn(name: 'recruiter_id', nullable: true, onDelete: 'CASCADE')]
    private ?Recruiter $recruiter = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url]
    private ?string $linkedinUrl;

    public function __construct(string $name, ?string $email, ?string $phone, ?string $linkedinUrl)
    {
        $this->name = self::required($name, 'A contact name is required.');
        $this->email = self::optional($email);
        $this->phone = self::optional($phone);
        $this->linkedinUrl = self::optional($linkedinUrl);
    }

    public function assignToCompany(Company $company): void
    {
        if (null !== $this->recruiter) {
            throw new \LogicException('A direct contact cannot belong to both a company and a recruiter.');
        }

        $this->company = $company;
    }

    public function assignToRecruiter(Recruiter $recruiter): void
    {
        if (null !== $this->company) {
            throw new \LogicException('A direct contact cannot belong to both a company and a recruiter.');
        }

        $this->recruiter = $recruiter;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }
}
