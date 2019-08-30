<?php

namespace ZF3Belcebur\DoctrineORMResources\RepositoryTrait;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Sortable\SortableListener;
use InvalidArgumentException;

/**
 * Trait SortableListenerAdapterTrait
 * @package ZF3Belcebur\DoctrineORMResources\RepositoryTrait
 * @property ClassMetadata meta
 * @property ClassMetadata _class
 * @property EntityManager _em
 * @property string _entityName
 */
trait SortableListenerAdapterTrait
{
    /**
     * Sortable listener on event manager
     *
     * @var SortableListener
     */
    protected $sortableListener;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $groupValues
     * @return mixed
     */
    public function getBySortableGroups(array $groupValues = array())
    {
        $query = $this->getBySortableGroupsQuery($groupValues);
        return $query->getResult();
    }

    /**
     * @param array $groupValues
     * @return Query
     */
    public function getBySortableGroupsQuery(array $groupValues = array()): Query
    {
        return $this->getBySortableGroupsQueryBuilder($groupValues)->getQuery();
    }

    /**
     * @param array $groupValues
     * @return QueryBuilder
     */
    public function getBySortableGroupsQueryBuilder(array $groupValues = array()): QueryBuilder
    {
        if (!$this->sortableListener) {
            $this->initListenerConfig();
        }

        $groups = isset($this->config['groups']) ? array_combine(array_values($this->config['groups']), array_keys($this->config['groups'])) : array();
        foreach ($groupValues as $name => $value) {
            if (!in_array($name, $this->config['groups'], true)) {
                throw new InvalidArgumentException('Sortable group "' . $name . '" is not defined in Entity ' . $this->meta->name);
            }
            unset($groups[$name]);
        }
        if (count($groups) > 0) {
            throw new InvalidArgumentException(
                'You need to specify values for the following groups to select by sortable groups: ' . implode(', ', array_keys($groups)));
        }
        /** @var QueryBuilder $qb */
        $qb = $this->_em->createQueryBuilder()->select('n')->from($this->_entityName, 'n');
        $qb->orderBy('n.' . $this->config['position']);
        $i = 1;
        foreach ($groupValues as $group => $value) {
            $qb
                ->andWhere('n.' . $group . ' = :group' . $i)
                ->setParameter('group' . $i, $value);
            $i++;
        }
        return $qb;
    }

    protected function initListenerConfig(): void
    {
        $sortableListener = null;
        foreach ($this->_em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof SortableListener) {
                    $sortableListener = $listener;
                    break;
                }
            }
            if ($sortableListener) {
                break;
            }
        }
        if ($sortableListener === null) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository can be attached only to ORM sortable listener');
        }
        $this->sortableListener = $sortableListener;
        $this->config = $sortableListener->getConfiguration($this->_em, $this->meta->name);
    }
}
