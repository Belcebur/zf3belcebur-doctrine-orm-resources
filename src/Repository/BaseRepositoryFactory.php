<?php

namespace ZF3Belcebur\DoctrineORMResources\Repository;

use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Router\RouteStackInterface;
use Zend\ServiceManager\Factory\FactoryInterface;


class BaseRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return BaseRepository|DefaultRepositoryFactory
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $request = $container->get('Request');
        if ($request instanceof Request) {
            $router = $container->get(RouteStackInterface::class);
            $routeMatch = $router->match($request);
            return new BaseRepository($request, $router, $routeMatch);
        }
        return new DefaultRepositoryFactory();
    }
}
