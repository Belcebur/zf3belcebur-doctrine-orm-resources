<?php


namespace ZF3Belcebur\DoctrineORMResources\ORM\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Workaround for private EntityManager field in \Doctrine\ORM\Mapping\ClassMetadataFactory::newClassMetadataInstance()
     * @inheritdoc
     */
    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
        parent::setEntityManager($em);
    }

    /**
     * @inheritDoc
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
    }
}
