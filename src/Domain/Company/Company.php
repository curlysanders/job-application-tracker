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

    private(set) Identifier $id;

    private(set) string $name;

    private(set) ?string $website;

    private(set) ?string $industry;

    /** @var list<DirectContact>|(\ArrayAccess<int, DirectContact>&\Traversable<int, DirectContact>) */
    private(set) array|(\ArrayAccess&\Traversable) $directContacts;

    public function __construct(
        public readonly User $user,
        string $name,
        ?string $website,
        ?string $industry,
    ) {
        $this->id = Identifier::v7();
        $this->directContacts = [];
        $this->update($name, $website, $industry);
    }

    public function update(
        string $name,
        ?string $website,
        ?string $industry,
    ): void {
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
}
