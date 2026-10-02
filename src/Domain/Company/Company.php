<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Company;

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
#[ORM\Table(name: 'companies')]
#[ORM\Index(name: 'IDX_COMPANIES_USER_NAME', columns: ['user_id', 'name'])]
final class Company implements DomainEventAware
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $website;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $industry;

    /** @var Collection<int, DirectContact> */
    #[ORM\OneToMany(targetEntity: DirectContact::class, mappedBy: 'company', cascade: ['persist'], orphanRemoval: true)]
    private Collection $directContacts;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        string $name,
        ?string $website,
        ?string $industry,
    ) {
        $this->id = Uuid::v7();
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
        $this->recordEvent(new CompanyCreated($this->id, $this->properties()));
    }

    public function recordDetailsUpdated(): void
    {
        $this->recordEvent(new CompanyDetailsUpdated($this->id, $this->properties()));
    }

    /** @return array<string, mixed> */
    private function properties(): array
    {
        return ['name' => $this->name, 'website' => $this->website, 'industry' => $this->industry];
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
