<?php

namespace ZF3Belcebur\DoctrineORMResources\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
use Zend\Filter\FilterChain;
use Zend\Filter\StringToLower;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Http\PhpEnvironment\Request;
use Zend\I18n\Filter\Alpha;
use Zend\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;
use Zend\Router\Http\TreeRouteStack;
use Zend\Router\RouteMatch;
use Zend\Router\RouteStackInterface;
use Zend\Router\SimpleRouteStack;
use ZF3Belcebur\DoctrineORMResources\Walker\GedmoTranslationWalker;
use function array_key_exists;
use function array_map;
use function explode;
use function get_class;
use function implode;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function method_exists;
use function str_replace;

class BaseEntityRepository extends EntityRepository
{

    /**
     * @var Request
     */
    protected $request;
    /**
     * @var TranslatorAwareTreeRouteStack|TreeRouteStack|SimpleRouteStack|RouteStackInterface
     */
    protected $router;

    public function __construct(EntityManager $em, ClassMetadata $class, ?Request $request, ?RouteStackInterface $router)
    {
        parent::__construct($em, $class);
        $this->request = $request;
        $this->router = $router;
        if ($this instanceof PostConstructInterface) {
            $this->postConstruct();
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string|null $locale
     * @param string|null $defaultLocale
     * @return Query
     */
    public function getQueryWithGedmoTranslation(QueryBuilder $qb, string $locale = null, string $defaultLocale = null): Query
    {
        $routeMatch = $this->getRouteMatch();
        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, GedmoTranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale ?? $routeMatch->getParam('locale', $defaultLocale));
        $query->setHint(TranslatableListener::HINT_FALLBACK, 1);

        return $query;
    }

    /**
     * @param array $defaultParams
     * @return RouteMatch
     */
    public function getRouteMatch(array $defaultParams = []): RouteMatch
    {
        $routeMatch = $this->router->match($this->request);
        if (!$routeMatch) {
            $routeMatch = new RouteMatch($defaultParams);
        }

        return $routeMatch;
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param int $limit
     * @param int $offset
     * @param string $alias
     * @return QueryBuilder
     * @example $criteria =
     * [
     * 'orX' => [
     * [
     * 'operator' => 'like',
     * 'value'    => '%espa%',
     * 'field'    => 'name',
     * ],
     * [
     * 'operator' => 'like',
     * 'value'    => '%espa%',
     * 'field'    => 'slugName',
     * ],
     * 'andX' => [
     * [
     * 'operator' => 'eq',
     * 'value'    => '%espa%',
     * 'field'    => 'name',
     * ],
     * [
     * 'operator' => 'isNull',
     * 'field'    => 'slugName',
     * ],
     * ],
     * ],
     * [
     * 'operator' => 'like',
     * 'value'    => '%cosas%',
     * 'field'    => 'slug',
     * ],
     * ];
     */
    public function findByQb(array $criteria, array $orderBy = [], int $limit = null, int $offset = null, string $alias = null): QueryBuilder
    {
        if (!$alias) {
            $alias = $this->getEntityAlias();
        }
        $qb = $this->createQueryBuilder($alias);

        $parameters = [];
        $andWheres = $this->filterArrayToQbExpression($criteria, $parameters, $alias);

        foreach ($andWheres as $andWhere) {
            $qb->andWhere($andWhere);
        }

        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy("{$alias}.$field", $order);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        foreach ($parameters as $parameter) {
//            if (is_scalar($parameter['value']) || $parameter['value'] instanceof DateTime) {
                $qb->setParameter($parameter['key'], $parameter['value']);
//            } else {
//                $qb->setParameter($parameter['key'], $parameter['value'], $parameter['type']);
//            }
        }

        return $qb;
    }

    /**
     * @return string
     */
    public function getEntityAlias(): string
    {
        $filterChain = new FilterChain();
        $filterChain
            ->attach(new Alpha())
            ->attach(new StringToLower())
            ->attach(new CamelCaseToDash());

        $name = str_replace($this->_class->namespace . '\\', '', $this->_entityName);
        return implode('', array_map(static function (string $name) {
            return $name[0];
        }, explode('-', $filterChain->filter($name))));
    }

    /**
     * @param array $criteria
     * @param array $parameters
     * @param string $alias
     * @return array[]
     */
    private function filterArrayToQbExpression(array $criteria, array &$parameters = [], string $alias = 'entity'): array
    {
        if (!$alias) {
            $alias = $this->getEntityAlias();
        }
        $exp = new Query\Expr();
        $values = [];
        foreach ($criteria as $key => $data) {
            if ((!is_array($data) || (is_array($data) && !array_key_exists('field', $data))) && !method_exists($exp, $key)) {

                if (is_array($data)) {
                    $data['operator'] = 'in';
                }
                
                $data = [
                    'value' => $data,
                    'field' => $key,
                ];

                $key = 0;
            }

            if (is_numeric($key)) {
                $fieldName = $alias . '.' . $data['field'];
                $operator = $data['operator'] ?? 'eq';
                if (method_exists($exp, $operator)) {
                    if (array_key_exists('value', $data)) {
                        $isParam = false;
                        if (is_object($data['value'])) {
                            $parameters[$fieldName] = [
                                'type' => get_class($data['value']),
                                'value' => $data['value'],
                                'key' => ":{$data['field']}",
                            ];
                            $data['value'] = ":{$data['field']}";
                            $isParam = true;
                        }
                        $value = [$data['value']];

                        if (!$isParam) {
                            $arrayValue = array_map(static function ($val) use ($exp) {
                                return is_string($val) ? $exp->literal($val) : $val;
                            }, $value);
                        } else {
                            $arrayValue = $value;
                        }

                        $values[] = $exp->$operator($fieldName, ...$arrayValue);
                    } else {
                        $values[] = $exp->$operator($fieldName);
                    }
                }
            } elseif (method_exists($exp, $key)) {
                $valExpressions = $this->filterArrayToQbExpression($data, $parameters, $alias);
                if (in_array($key, [
                    'andX',
                    'orX',
                ], true)) {
                    $values[] = $exp->$key()->addMultiple($valExpressions);
                }
            }
        }
        return $values;
    }
}
