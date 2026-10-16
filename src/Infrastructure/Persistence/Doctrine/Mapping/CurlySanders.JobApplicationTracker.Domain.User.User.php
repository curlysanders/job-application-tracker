<?php

use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationStatus;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'users']);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'email', 'type' => 'string', 'length' => 180, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'roles', 'type' => 'json']);
    $metadata->mapField(['fieldName' => 'password', 'type' => 'string']);
    $metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'minimumPreferredSalary', 'columnName' => 'min_preferred_salary', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'minimumPreferredSalaryCurrency', 'columnName' => 'min_preferred_salary_currency', 'type' => 'string', 'length' => 3]);
    $metadata->mapField(['fieldName' => 'maximumCommuteMinutes', 'columnName' => 'max_commute_minutes', 'type' => 'integer', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'preferredTransportMode', 'type' => 'string', 'length' => 20, 'nullable' => true, 'enumType' => PreferredTransportMode::class]);
    $metadata->mapField(['fieldName' => 'resumeStoragePath', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'resumeOriginalFilename', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'resumeMimeType', 'type' => 'string', 'length' => 100, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'resumeUploadedAt', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'pendingResumeStoragePath', 'columnName' => 'pending_resume_storage_path', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'pendingResumeOriginalFilename', 'columnName' => 'pending_resume_original_filename', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'pendingResumeMimeType', 'columnName' => 'pending_resume_mime_type', 'type' => 'string', 'length' => 100, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'pendingResumeUploadedAt', 'columnName' => 'pending_resume_uploaded_at', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'resumeValidationId', 'columnName' => 'resume_validation_id', 'type' => 'identifier', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'resumeValidationStatus', 'columnName' => 'resume_validation_status', 'type' => 'string', 'length' => 20, 'nullable' => true, 'enumType' => ResumeValidationStatus::class]);
    $metadata->mapField(['fieldName' => 'resumeValidationFailure', 'columnName' => 'resume_validation_failure', 'type' => 'string', 'length' => 255, 'nullable' => true]);
};
