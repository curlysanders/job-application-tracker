<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recruiters')]
#[ORM\Index(name: 'IDX_RECRUITERS_AGENCY_NAME', fields: ['agencyName'])]
final class Recruiter
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255)] private string $agencyName;
    #[ORM\Column(length: 2048, nullable: true)] private ?string $website;
    /** @var Collection<int, DirectContact> */
    #[ORM\OneToMany(targetEntity: DirectContact::class, mappedBy: 'recruiter', cascade: ['persist'], orphanRemoval: true)]
    private Collection $directContacts;

    public function __construct(string $agencyName, ?string $website)
    {
        $this->directContacts = new ArrayCollection();
        $this->update($agencyName, $website);
    }

    public function update(string $agencyName, ?string $website): void
    {
        $this->agencyName = self::required($agencyName, 'An agency name is required.');
        $this->website = self::optional($website);
    }

    public function replaceDirectContacts(DirectContact ...$contacts): void
    {
        $this->directContacts->clear();
        foreach ($contacts as $contact) {
            $contact->assignToRecruiter($this);
            $this->directContacts->add($contact);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgencyName(): string
    {
        return $this->agencyName;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /** @return Collection<int, DirectContact> */
    public function getDirectContacts(): Collection
    {
        return $this->directContacts;
    }
}
