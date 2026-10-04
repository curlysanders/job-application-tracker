<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileSettingsTest extends WebTestCase
{
    public function testAnonymousUsersAreRedirectedToLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/profile');

        self::assertResponseRedirects('/login');
    }

    public function testUserCanUpdateTheirProfileSettings(): void
    {
        $client = self::createClient();
        $user = $this->createUser('sander@example.com');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile');
        $client->submit($crawler->selectButton('Save settings')->form([
            'profile_settings[minimumPreferredSalary]' => '4500.00',
            'profile_settings[minimumPreferredSalaryCurrency]' => 'USD',
            'profile_settings[maximumCommuteMinutes]' => 45,
            'profile_settings[preferredTransportMode]' => PreferredTransportMode::PublicTransport->value,
        ]));

        self::assertResponseRedirects('/profile');
        $this->entityManager()->clear();
        $savedUser = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $savedUser);
        self::assertSame('4500.00', $savedUser->getPreferredSalary()->getMinimumDecimal());
        self::assertSame('USD', $savedUser->getPreferredSalary()->getCurrencyCode());
        self::assertSame(45, $savedUser->getMaximumCommuteMinutes());
        self::assertSame(PreferredTransportMode::PublicTransport, $savedUser->getPreferredTransportMode());
    }

    public function testProfileRejectsNonPositivePreferences(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('sander@example.com'));
        $crawler = $client->request('GET', '/profile');
        $client->submit($crawler->selectButton('Save settings')->form([
            'profile_settings[minimumPreferredSalary]' => '0.00',
            'profile_settings[maximumCommuteMinutes]' => 0,
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-panel', 'Enter a positive amount with at most two decimal places.');
        self::assertSelectorTextContains('.form-panel', 'The commute time must be positive.');
    }

    public function testUserCanStageAndReplaceAPendingResume(): void
    {
        $client = self::createClient();
        $user = $this->createUser('sander@example.com');
        $client->loginUser($user);

        $firstFile = $this->createPdf('First resume');
        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Upload resume')->form();
        $this->attachResume($form, $firstFile);
        $client->submit($form);

        self::assertResponseRedirects('/profile');
        $this->entityManager()->clear();
        $savedUser = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $savedUser);
        $firstPath = $savedUser->getPendingResume()?->storagePath;
        self::assertNotNull($firstPath);
        self::assertStringStartsWith(sprintf('resumes/%s/pending/', $user->getId()->toRfc4122()), $firstPath);
        self::assertTrue($this->storage()->fileExists($firstPath));
        self::assertNull($savedUser->getResumeStoragePath());

        $secondFile = $this->createDocx('Second resume');
        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Upload resume')->form();
        $this->attachResume($form, $secondFile);
        $client->submit($form);

        self::assertResponseRedirects('/profile');
        $this->entityManager()->clear();
        $savedUser = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $savedUser);
        $pending = $savedUser->getPendingResume();
        self::assertNotNull($pending);
        self::assertSame(basename($secondFile), $pending->originalFilename);
        self::assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $pending->mimeType);
        self::assertNotSame($firstPath, $pending->storagePath);
        self::assertFalse($this->storage()->fileExists($firstPath));
        self::assertNull($savedUser->getResumeStoragePath());
    }

    public function testProfileRejectsInvalidResumeWithoutReplacingActiveResume(): void
    {
        $client = self::createClient();
        $user = $this->createUser('sander@example.com');
        $activeResumePath = sprintf('resumes/%s/active.pdf', $user->getId()->toRfc4122());
        $user->replaceResume($activeResumePath, 'active.pdf', 'application/pdf', new \DateTimeImmutable());
        $this->entityManager()->flush();

        $invalidFile = tempnam(sys_get_temp_dir(), 'resume-invalid-');
        self::assertNotFalse($invalidFile);
        file_put_contents($invalidFile, 'not a resume');
        $client->loginUser($user);
        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Upload resume')->form();
        $this->attachResume($form, $invalidFile);
        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[aria-labelledby="resume-upload-heading"]', 'Upload a PDF or DOCX resume.');
        $this->entityManager()->clear();
        $savedUser = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $savedUser);
        self::assertSame($activeResumePath, $savedUser->getResumeStoragePath());
    }

    public function testAnonymousUsersCannotDownloadAResume(): void
    {
        $client = self::createClient();
        $client->request('GET', '/profile/resume');

        self::assertResponseRedirects('/login');
    }

    private function createUser(string $email): User
    {
        $user = new User()->setEmail($email);
        $user->setPassword($this->passwordHasher()->hashPassword($user, 'SecurePassword1!'));
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    private function passwordHasher(): UserPasswordHasherInterface
    {
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);

        return $passwordHasher;
    }

    private function storage(): FilesystemOperator
    {
        $storage = self::getContainer()->get('default.storage');
        self::assertInstanceOf(FilesystemOperator::class, $storage);

        return $storage;
    }

    private function createPdf(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'resume-pdf-');
        self::assertNotFalse($path);
        file_put_contents($path, "%PDF-1.4\n".$contents);

        return $path;
    }

    private function createDocx(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'resume-docx-');
        self::assertNotFalse($path);
        unlink($path);

        $archive = new \ZipArchive();
        self::assertTrue($archive->open($path, \ZipArchive::CREATE));
        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $archive->addFromString('word/document.xml', sprintf('<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>%s</w:t></w:r></w:p></w:body></w:document>', htmlspecialchars($contents, ENT_XML1)));
        self::assertTrue($archive->close());

        return $path;
    }

    private function attachResume(Form $form, string $path): void
    {
        $field = $form->get('resume_upload[resume]');
        self::assertInstanceOf(FileFormField::class, $field);
        $field->upload($path);
    }
}
