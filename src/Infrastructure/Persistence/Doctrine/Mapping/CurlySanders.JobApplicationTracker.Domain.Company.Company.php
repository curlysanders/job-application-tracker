<?php

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'companies', 'indexes' => ['IDX_COMPANIES_USER_NAME' => ['columns' => ['user_id', 'name']]]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 255]);
    $metadata->mapField(['fieldName' => 'website', 'type' => 'string', 'length' => 2048, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'industry', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapManyToOne(['fieldName' => 'user', 'targetEntity' => User::class, 'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']]]);
    $metadata->mapOneToMany(['fieldName' => 'directContacts', 'targetEntity' => DirectContact::class, 'mappedBy' => 'company', 'cascade' => ['persist'], 'orphanRemoval' => true]);
};
