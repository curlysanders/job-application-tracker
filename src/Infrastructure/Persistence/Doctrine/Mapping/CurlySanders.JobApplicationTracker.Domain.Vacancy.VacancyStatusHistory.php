<?php

use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'vacancy_status_history', 'uniqueConstraints' => ['UNIQ_VACANCY_STATUS_HISTORY_OUTBOX_RECORD' => ['columns' => ['outbox_record_id']]], 'indexes' => ['IDX_VACANCY_STATUS_HISTORY_VACANCY_AT' => ['columns' => ['vacancy_id', 'transitioned_at']]]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true, 'generator' => ['strategy' => 'AUTO']]);
    $metadata->mapField(['fieldName' => 'notes', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'transitionedAt', 'columnName' => 'transitioned_at', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'fromStatus', 'columnName' => 'from_status', 'type' => 'string', 'length' => 20, 'enumType' => VacancyStatus::class]);
    $metadata->mapField(['fieldName' => 'toStatus', 'columnName' => 'to_status', 'type' => 'string', 'length' => 20, 'enumType' => VacancyStatus::class]);
    $metadata->mapField(['fieldName' => 'outboxRecordId', 'columnName' => 'outbox_record_id', 'type' => 'integer', 'nullable' => true]);
    $metadata->mapManyToOne(['fieldName' => 'vacancy', 'targetEntity' => Vacancy::class, 'joinColumns' => [['name' => 'vacancy_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE', 'foreignKeyName' => 'FK_VACANCY_STATUS_HISTORY_VACANCY']]]);
    $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
};
