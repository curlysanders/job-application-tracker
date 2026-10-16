<?php

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'direct_contacts', 'indexes' => ['IDX_DIRECT_CONTACT_COMPANY' => ['columns' => ['company_id']], 'IDX_DIRECT_CONTACT_RECRUITER' => ['columns' => ['recruiter_id']], 'IDX_DIRECT_CONTACT_NAME' => ['columns' => ['name']]]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true, 'generator' => ['strategy' => 'AUTO']]);
    $metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 255]);
    $metadata->mapField(['fieldName' => 'email', 'type' => 'string', 'length' => 180, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'phone', 'type' => 'string', 'length' => 50, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'linkedinUrl', 'type' => 'string', 'length' => 2048, 'nullable' => true]);
    $metadata->mapManyToOne(['fieldName' => 'company', 'targetEntity' => Company::class, 'inversedBy' => 'directContacts', 'joinColumns' => [['name' => 'company_id', 'referencedColumnName' => 'id', 'nullable' => true, 'onDelete' => 'CASCADE']]]);
    $metadata->mapManyToOne(['fieldName' => 'recruiter', 'targetEntity' => Recruiter::class, 'inversedBy' => 'directContacts', 'joinColumns' => [['name' => 'recruiter_id', 'referencedColumnName' => 'id', 'nullable' => true, 'onDelete' => 'CASCADE']]]);
    $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
};
