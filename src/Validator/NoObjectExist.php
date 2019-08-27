<?php

namespace ZF3Belcebur\DoctrineORMResources\Validator;

use DoctrineModule\Validator\NoObjectExists;

/**
 * Created by PhpStorm.
 * User: pgarcia
 * Date: 30/07/2018
 */
class NoObjectExist extends NoObjectExists
{

    public function isValid($value, $context = null): bool
    {
        $values = array_intersect_key($context, array_flip($this->fields));

        return parent::isValid($values);
    }
}
