<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Company;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'companies')]
#[ORM\Index(name: 'IDX_COMPANIES_NAME', fields: ['name'])]
final class Company
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255)]
    private string $name;
    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $website;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $industry;
    /** @var Collection<int, DirectContact> */
    #[ORM\OneToMany(targetEntity: DirectContact::class, mappedBy: 'company', cascade: ['persist'], orphanRemoval: true)]
    private Collection $directContacts;

    public function __construct(string $name, ?string $website, ?string $industry)
    {
        $this->directContacts = new ArrayCollection();
        $this->update($name, $website, $industry);
    }

    public function update(string $name, ?string $website, ?string $industry): void
    {
        $this->name = self::required($name, 'A company name is required.');
        $this->website = self::optional($website);
        $this->industry = self::optional($industry);
    }

    public function replaceDirectContacts(DirectContact ...$contacts): void
    {
        $this->directContacts->clear();
        foreach ($contacts as $contact) {
            $contact->assignToCompany($this);
            $this->directContacts->add($contact);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    /** @return Collection<int, DirectContact> */
    public function getDirectContacts(): Collection
    {
        return $this->directContacts;
    }
}
