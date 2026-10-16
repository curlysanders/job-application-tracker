<?php

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ApplicationSource;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'vacancies', 'indexes' => [
        'IDX_VACANCIES_DASHBOARD' => ['columns' => ['user_id', 'archived', 'date_added']],
        'IDX_VACANCIES_DASHBOARD_STATUS' => ['columns' => ['user_id', 'archived', 'status', 'date_added']],
        'IDX_VACANCIES_REMINDERS' => ['columns' => ['user_id', 'archived', 'next_action_at']],
        'IDX_VACANCIES_COMPANY' => ['columns' => ['company_id']],
        'IDX_VACANCIES_RECRUITER' => ['columns' => ['recruiter_id']],
    ]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'title', 'type' => 'string', 'length' => 255]);
    $metadata->mapField(['fieldName' => 'fullText', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'requirements', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'responsibilities', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'preferredQualifications', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'aboutJob', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'aboutCompany', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'compensationBenefits', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'sourceUrls', 'type' => 'json']);
    $metadata->mapField(['fieldName' => 'howToApply', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'location', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'minimumSalary', 'columnName' => 'min_salary', 'type' => 'decimal', 'precision' => 12, 'scale' => 2, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'maximumSalary', 'columnName' => 'max_salary', 'type' => 'decimal', 'precision' => 12, 'scale' => 2, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'currencyCode', 'type' => 'string', 'length' => 3]);
    $metadata->mapField(['fieldName' => 'workMode', 'type' => 'string', 'length' => 20, 'nullable' => true, 'enumType' => WorkMode::class]);
    $metadata->mapField(['fieldName' => 'hybridDetails', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'dateAdded', 'type' => 'datetime_immutable']);
    $metadata->mapField(['fieldName' => 'datePosted', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'deadline', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'dateApplied', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'nextActionAt', 'type' => 'datetime_immutable', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'nextActionTitle', 'type' => 'string', 'length' => 255, 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'length' => 20, 'enumType' => VacancyStatus::class]);
    $metadata->mapField(['fieldName' => 'archived', 'type' => 'boolean']);
    $metadata->mapField(['fieldName' => 'excitement', 'type' => 'integer', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'contractType', 'type' => 'string', 'length' => 20, 'nullable' => true, 'enumType' => ContractType::class]);
    $metadata->mapField(['fieldName' => 'applicationSource', 'type' => 'string', 'length' => 20, 'nullable' => true, 'enumType' => ApplicationSource::class]);
    $metadata->mapField(['fieldName' => 'terminalReason', 'type' => 'text', 'nullable' => true]);
    $metadata->mapField(['fieldName' => 'scratchpadNotes', 'type' => 'text', 'nullable' => true]);
    $metadata->mapManyToOne(['fieldName' => 'user', 'targetEntity' => User::class, 'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']]]);
    $metadata->mapManyToOne(['fieldName' => 'company', 'targetEntity' => Company::class, 'joinColumns' => [['name' => 'company_id', 'referencedColumnName' => 'id', 'nullable' => true, 'onDelete' => 'SET NULL']]]);
    $metadata->mapManyToOne(['fieldName' => 'recruiter', 'targetEntity' => Recruiter::class, 'joinColumns' => [['name' => 'recruiter_id', 'referencedColumnName' => 'id', 'nullable' => true, 'onDelete' => 'SET NULL']]]);
    $metadata->mapManyToMany(['fieldName' => 'techStacks', 'targetEntity' => TechStack::class, 'joinTable' => ['name' => 'vacancy_tech_stacks', 'foreignKeyName' => 'FK_VACANCY_TECH_STACKS_VACANCY', 'inverseForeignKeyName' => 'FK_VACANCY_TECH_STACKS_TECH_STACK', 'joinColumns' => [['name' => 'vacancy_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']], 'inverseJoinColumns' => [['name' => 'tech_stack_id', 'referencedColumnName' => 'id', 'onDelete' => 'CASCADE']]]]);
};
