<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Lingoda\DomainEventsBundle\Domain\Model\DomainEventAware;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'recruiters')]
#[ORM\Index(name: 'IDX_RECRUITERS_USER_AGENCY_NAME', columns: ['user_id', 'agency_name'])]
final class Recruiter implements DomainEventAware
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $agencyName;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $website;

    /** @var Collection<int, DirectContact> */
    #[ORM\OneToMany(targetEntity: DirectContact::class, mappedBy: 'recruiter', cascade: ['persist'], orphanRemoval: true)]
    private Collection $directContacts;

    public function __construct(#[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user, string $agencyName, ?string $website)
    {
        $this->id = Uuid::v7();
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

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function recordCreated(): void
    {
        $this->recordEvent(new RecruiterCreated($this->id, $this->properties()));
    }

    public function recordDetailsUpdated(): void
    {
        $this->recordEvent(new RecruiterDetailsUpdated($this->id, $this->properties()));
    }

    /** @return array<string, mixed> */
    private function properties(): array
    {
        return ['agencyName' => $this->agencyName, 'website' => $this->website];
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
