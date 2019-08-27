<?php

namespace ZF3Belcebur\DoctrineORMResources;

use Traversable;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\DependencyIndicatorInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

class Module implements ConfigProviderInterface, DependencyIndicatorInterface
{

    public const CONFIG_KEY = __NAMESPACE__;

    public function init(ModuleManager $moduleManager)
    {
        $events = $moduleManager->getEventManager();

        // Registering a listener at default priority, 1, which will trigger
        // after the ConfigListener merges config.
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onMergeConfig'));
    }

    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        if ($configListener) {
            $config = $configListener->getMergedConfig(false);
            \define('GEDMO_CUSTOM_TRANSLATION_CLASSES', $config[__NAMESPACE__]['gedmo']['custom_translation_classes']);
        }
    }

    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|Traversable
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Expected to return an array of modules on which the current one depends on
     *
     * @return array
     */
    public function getModuleDependencies(): array
    {
        return [
            'DoctrineModule',
            'DoctrineORMModule',
            'Zend\Filter'
        ];
    }
}
