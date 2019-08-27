<?php


namespace ZF3Belcebur\DoctrineORMResources\Query\Functions\Mysql;

use CrEOF\Spatial\ORM\Query\AST\Functions\AbstractSpatialDQLFunction;

/**
 * STDistanceSphere DQL function
 *
 */
class STDistanceSphere extends AbstractSpatialDQLFunction
{
    public const FUNCTION_NAME = 'ST_Distance_Sphere';

    protected $platforms = ['mysql'];
    protected $functionName = self::FUNCTION_NAME;
    protected $minGeomExpr = 2;
    protected $maxGeomExpr = 3;

}
