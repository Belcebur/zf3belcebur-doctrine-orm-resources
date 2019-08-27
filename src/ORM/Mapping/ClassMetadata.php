<?php

namespace ZF3Belcebur\DoctrineORMResources\ORM\Mapping;


use Doctrine\ORM\Mapping\NamingStrategy;
use Gedmo\Translatable\Entity\Translation;

/**
 * Class ClassMetadata
 * @package ZF3Belcebur\DoctrineORMResources\ORM\Mapping
 * @see https://github.com/Atlantic18/DoctrineExtensions/pull/1432
 */
class ClassMetadata extends \Doctrine\ORM\Mapping\ClassMetadata
{

    /** @var array */
    private $translatableClasses;

    public function __construct(string $entityName, NamingStrategy $namingStrategy = null)
    {
        $customTranslationClasses = defined('GEDMO_CUSTOM_TRANSLATION_CLASSES') ? (array)GEDMO_CUSTOM_TRANSLATION_CLASSES : [];
        $this->translatableClasses = \array_merge($customTranslationClasses, [Translation::class]);
        parent::__construct($entityName, $namingStrategy);
    }

    /**
     * @inheritdoc
     */
    public function mapField(array $mapping): void
    {
        // Fix performance issue with column types mismatch and lack of indexes optimization
        if ('foreignKey' === $mapping['fieldName'] && \in_array($this->name, $this->translatableClasses, true)) {
            $mapping['type'] = 'integer';
            unset($mapping['length']);
        }
        parent::mapField($mapping);
    }
}
