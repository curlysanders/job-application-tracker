<?php

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'analysis_results', 'uniqueConstraints' => [
        'UNIQ_ANALYSIS_RESULTS_REQUEST' => ['columns' => ['request_id']],
    ]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'ciphertext', 'type' => 'text']);
    $metadata->mapField(['fieldName' => 'fingerprint', 'type' => 'string', 'length' => 64]);
    $metadata->mapField(['fieldName' => 'createdAt', 'columnName' => 'created_at', 'type' => 'datetime_immutable']);
    $metadata->mapOneToOne(['fieldName' => 'request', 'targetEntity' => AnalysisRequest::class, 'inversedBy' => 'result', 'joinColumns' => [['name' => 'request_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']]]);
};
