<?php

namespace ZF3Belcebur\DoctrineORMResources\Validator;

/**
 * Created by PhpStorm.
 * User: dgarcia
 * Date: 08/01/2016
 * Time: 9:28
 */
class UniqueObject extends \DoctrineModule\Validator\UniqueObject
{
    /**
     * @see https://github.com/doctrine/DoctrineModule/issues/252
     *
     * @param mixed $value
     * @param null $context
     *
     * @return bool
     */
    public function isValid($value, $context = null): bool
    {
        $this->useContext = true;
        $values = array_intersect_key($context, array_flip($this->fields));

        return parent::isValid($values, $context);
    }
}
