<?php

namespace ZF3Belcebur\DoctrineORMResources\EntityTrait;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Zend\Form\Annotation as FormAnnotation;

/**
 *
 * @author David Garcia
 * @ORM\MappedSuperclass()
 *
 */
trait GedmoEntityTranslatable
{

    /**
     * @Gedmo\Language()
     * @FormAnnotation\Exclude()
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    protected $language;

    /**
     * @return mixed
     */
    public function getTranslatableLocale()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setTranslatableLocale($language): void
    {
        $this->language = $language;
    }


}
