<?php

use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata): void {
    $metadata->setPrimaryTable(['name' => 'tech_stacks', 'uniqueConstraints' => ['UNIQ_TECH_STACKS_SLUG' => ['columns' => ['slug']]], 'indexes' => ['IDX_TECH_STACKS_CATEGORY' => ['columns' => ['category']]]]);
    $metadata->mapField(['fieldName' => 'id', 'type' => 'identifier', 'id' => true, 'unique' => true]);
    $metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 255]);
    $metadata->mapField(['fieldName' => 'slug', 'type' => 'string', 'length' => 255]);
    $metadata->mapField(['fieldName' => 'category', 'type' => 'string', 'length' => 255]);
};
