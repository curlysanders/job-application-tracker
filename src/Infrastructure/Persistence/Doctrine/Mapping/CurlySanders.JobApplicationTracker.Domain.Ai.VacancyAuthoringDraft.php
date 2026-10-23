<?php

use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'vacancy_authoring_drafts', 'indexes' => [
        'IDX_VACANCY_AUTHORING_DRAFTS_OWNER_EXPIRY' => ['columns' => ['user_id', 'expires_at']],
        'IDX_VACANCY_AUTHORING_DRAFTS_EXPIRY' => ['columns' => ['expires_at']],
    ]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'ciphertext', 'type' => 'text']);
    $metadata->mapField(['fieldName' => 'expiresAt', 'columnName' => 'expires_at', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'createdAt', 'columnName' => 'created_at', 'type' => 'datetime_immutable']);
    $metadata->mapManyToOne(['fieldName' => 'user', 'targetEntity' => User::class, 'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']]]);
};
