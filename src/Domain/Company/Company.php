<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Company;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\WebAddress;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final class Company implements RecordsEvents
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    private Identifier $id;

    private string $name;

    private ?string $website;

    private ?string $industry;

    /** @var list<DirectContact>|(\ArrayAccess<int, DirectContact>&\Traversable<int, DirectContact>) */
    private array|(\ArrayAccess&\Traversable) $directContacts;

    public function __construct(
        private readonly User $user,
        string $name,
        ?string $website,
        ?string $industry,
    ) {
        $this->id = Identifier::v7();
        $this->directContacts = [];
        $this->update($name, $website, $industry);
    }

    public function update(string $name, ?string $website, ?string $industry): void
    {
        $this->name = self::required($name, 'A company name is required.');
        $this->website = WebAddress::optionalHttpUrl($website, 'A company website');
        $this->industry = self::optional($industry);
    }

    public function replaceDirectContacts(DirectContact ...$contacts): void
    {
        if (is_array($this->directContacts)) {
            $this->directContacts = [];
            foreach ($contacts as $contact) {
                $contact->assignToCompany($this);
                $this->directContacts[] = $contact;
            }

            return;
        }

        foreach ($this->directContacts as $key => $contact) {
            unset($this->directContacts[$key]);
        }
        foreach ($contacts as $contact) {
            $contact->assignToCompany($this);
            $this->directContacts[] = $contact;
        }
    }

    public function getId(): Identifier
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

    /** @return iterable<int, DirectContact> */
    public function getDirectContacts(): iterable
    {
        return $this->directContacts;
    }
}
