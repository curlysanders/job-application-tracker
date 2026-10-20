<?php

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'analysis_requests', 'indexes' => [
        'IDX_ANALYSIS_REQUESTS_OWNER_EXPIRY' => ['columns' => ['user_id', 'expires_at']],
        'IDX_ANALYSIS_REQUESTS_EXPIRY' => ['columns' => ['expires_at']],
    ]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'kind', 'type' => 'string', 'length' => 30, 'enumType' => AnalysisKind::class]);
    $metadata->mapField(['fieldName' => 'inputCiphertext', 'columnName' => 'input_ciphertext', 'type' => 'text']);
    $metadata->mapField(['fieldName' => 'inputFingerprint', 'columnName' => 'input_fingerprint', 'type' => 'string', 'length' => 64]);
    $metadata->mapField(['fieldName' => 'provider', 'type' => 'string', 'length' => 30]);
    $metadata->mapField(['fieldName' => 'model', 'type' => 'string', 'length' => 120]);
    $metadata->mapField(['fieldName' => 'version', 'type' => 'integer']);
    $metadata->mapField(['fieldName' => 'state', 'type' => 'string', 'length' => 20, 'enumType' => AnalysisState::class]);
    $metadata->mapField(['fieldName' => 'failureCode', 'columnName' => 'failure_code', 'type' => 'string', 'length' => 50, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'expiresAt', 'columnName' => 'expires_at', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'createdAt', 'columnName' => 'created_at', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'updatedAt', 'columnName' => 'updated_at', 'type' => 'datetime_immutable']);
    $metadata->mapManyToOne(['fieldName' => 'user', 'targetEntity' => User::class, 'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']]]);
    $metadata->mapOneToOne(['fieldName' => 'result', 'targetEntity' => AnalysisResult::class, 'mappedBy' => 'request', 'cascade' => ['persist', 'remove'], 'orphanRemoval' => true]);
};
