<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\WebAddress;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final class Recruiter implements RecordsEvents
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    private Identifier $id;

    private string $agencyName;

    private ?string $website;

    /** @var list<DirectContact>|(\ArrayAccess<int, DirectContact>&\Traversable<int, DirectContact>) */
    private array|(\ArrayAccess&\Traversable) $directContacts;

    public function __construct(
        private readonly User $user,
        string $agencyName,
        ?string $website,
    ) {
        $this->id = Identifier::v7();
        $this->directContacts = [];
        $this->update($agencyName, $website);
    }

    public function update(string $agencyName, ?string $website): void
    {
        $this->agencyName = self::required($agencyName, 'An agency name is required.');
        $this->website = WebAddress::optionalHttpUrl($website, 'A recruiter website');
    }

    public function replaceDirectContacts(DirectContact ...$contacts): void
    {
        if (is_array($this->directContacts)) {
            $this->directContacts = [];
            foreach ($contacts as $contact) {
                $contact->assignToRecruiter($this);
                $this->directContacts[] = $contact;
            }

            return;
        }

        foreach ($this->directContacts as $key => $contact) {
            unset($this->directContacts[$key]);
        }
        foreach ($contacts as $contact) {
            $contact->assignToRecruiter($this);
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

    /** @return iterable<int, DirectContact> */
    public function getDirectContacts(): iterable
    {
        return $this->directContacts;
    }
}
