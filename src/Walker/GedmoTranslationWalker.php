<?php
/**
 * Created by PhpStorm.
 * User: dgarcia
 * Date: 07/02/2018
 * Time: 15:31
 */

namespace ZF3Belcebur\DoctrineORMResources\Walker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\PartialObjectExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\SqlWalker;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Hydrator\ORM\SimpleObjectHydrator;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableEventAdapter;
use Gedmo\Translatable\TranslatableListener;
use function count;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

class GedmoTranslationWalker extends SqlWalker
{
    /**
     * Name for translation fallback hint
     *
     * @internal
     */
    public const HINT_TRANSLATION_FALLBACKS = '__gedmo.translatable.stored.fallbacks';

    /**
     * Customized object hydrator name
     *
     * @internal
     */
    public const HYDRATE_OBJECT_TRANSLATION = '__gedmo.translatable.object.hydrator';

    /**
     * Customized object hydrator name
     *
     * @internal
     */
    public const HYDRATE_SIMPLE_OBJECT_TRANSLATION = '__gedmo.translatable.simple_object.hydrator';

    /**
     * Stores all component references from select clause
     *
     * @var array
     */
    private $translatedComponents = [];

    /**
     * DBAL database platform
     *
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * DBAL database connection
     *
     * @var Connection
     */
    private $conn;

    /**
     * List of aliases to replace with translation
     * content reference
     *
     * @var array
     */
    private $replacements = [];

    /**
     * List of joins for translated components in query
     *
     * @var array
     */
    private $components = [];

    /**
     * @var array
     */
    private $filterFields = [];

    /**
     * @var array
     */
    private $pathExpressions = [];

    private $listener;

    /**
     * GedmoTranslationWalker constructor.
     * @param $query
     * @param $parserResult
     * @param array $queryComponents
     * @throws DBALException
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->listener = $this->getTranslatableListener();
        $this->extractTranslatedComponents($queryComponents);
        $this->initFilterFields();
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @return TranslatableListener
     * @throws RuntimeException - if listener is not found
     *
     */
    private function getTranslatableListener(): TranslatableListener
    {
        $em = $this->getEntityManager();
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ((array)$listeners as $hash => $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }

        throw new RuntimeException('The translation listener could not be found');
    }

    /**
     * Search for translated components in the select clause
     *
     * @param array $queryComponents
     */
    private function extractTranslatedComponents(array $queryComponents): void
    {
        $em = $this->getEntityManager();
        foreach ($queryComponents as $alias => $comp) {
            if (!isset($comp['metadata'])) {
                continue;
            }
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            if ($config && isset($config['fields'])) {
                $this->translatedComponents[$alias] = $comp;
            }
        }
    }

    private function initFilterFields(): void
    {
        /**
         * @var PathExpression $pathExpression
         */
        $this->extractPathExpressions($this->getQuery()->getAST()->selectClause->selectExpressions);
        foreach ($this->pathExpressions as $pathExpression) {
            $this->filterFields[$pathExpression->identificationVariable][$pathExpression->field] = $pathExpression->field;
        }
    }

    private function extractPathExpressions($iterable): void
    {
        /**
         * @var SelectExpression $selectExpression
         */

        if (is_array($iterable) || is_object($iterable)) {
            foreach ($iterable as $item) {
                if ($item instanceof PathExpression) {
                    $this->pathExpressions[] = $item;
                } elseif (is_object($item)) {
                    if ($item->expression instanceof PartialObjectExpression) {
                        $partial = $item->expression;
                        foreach ($partial->partialFieldSet as $fieldName) {
                            $this->filterFields[$partial->identificationVariable][$fieldName] = $fieldName;
                        }
                    } elseif (is_string($item->expression)) {
                        $component = $this->getQueryComponent($item->expression);
                        $meta = $component['metadata'] ?? null;
                        if ($meta instanceof ClassMetadata) {
                            foreach ($meta->getFieldNames() as $columnName => $fieldName) {
                                $this->filterFields[$item->expression][$fieldName] = $fieldName;
                            }
                        }
                    } else {
                        $this->extractPathExpressions($item);
                    }
                } else {
                    $this->extractPathExpressions($item);
                }
            }
        }
    }

    /**
     * @param $AST
     * @return Query\Exec\AbstractSqlExecutor|Query\Exec\MultiTableDeleteExecutor|Query\Exec\MultiTableUpdateExecutor|SingleSelectExecutor|Query\Exec\SingleTableDeleteUpdateExecutor
     * @throws DBALException
     * @throws MappingException
     */
    public function getExecutor($AST)
    {
        if (!$AST instanceof SelectStatement) {
            throw new UnexpectedValueException('Translation walker should be used only on select statement');
        }
        $this->prepareTranslatedComponents();

        return new SingleSelectExecutor($AST, $this);
    }

    /**
     * @throws DBALException
     * @throws MappingException
     */
    private function prepareTranslatedComponents(): void
    {
        $q = $this->getQuery();
        $locale = $q->getHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE);
        if (!$locale) {
            // use from listener
            $locale = $this->listener->getListenerLocale();
        }
        $defaultLocale = $this->listener->getDefaultLocale();
        if ($locale === $defaultLocale && !$this->listener->getPersistDefaultLocaleTranslation()) {
            // Skip preparation as there's no need to translate anything
            return;
        }
        $em = $this->getEntityManager();
        $ea = new TranslatableEventAdapter();
        $ea->setEntityManager($em);
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $joinStrategy = $q->getHint(TranslatableListener::HINT_INNER_JOIN) ? 'INNER' : 'LEFT';

        foreach ($this->translatedComponents as $dqlAlias => $comp) {
            /** @var ClassMetadata $meta */
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            $transClass = $this->listener->getTranslationClass($ea, $meta->name);
            $transMeta = $em->getClassMetadata($transClass);
            $transTable = $quoteStrategy->getTableName($transMeta, $this->platform);
            foreach ($this->getFilterFieldsByDqlAlias($dqlAlias, $config['fields']) as $field) {
                $compTblAlias = $this->walkIdentificationVariable($dqlAlias, $field);
                $tblAlias = $this->getSQLTableAlias('trans' . $compTblAlias . $field);
                $sql = " {$joinStrategy} JOIN " . $transTable . ' ' . $tblAlias;
                $sql .= ' ON ' . $tblAlias . '.' . $quoteStrategy->getColumnName('locale', $transMeta, $this->platform)
                    . ' = ' . $this->conn->quote($locale);
                $sql .= ' AND ' . $tblAlias . '.' . $quoteStrategy->getColumnName('field', $transMeta, $this->platform)
                    . ' = ' . $this->conn->quote($field);
                $identifier = $meta->getSingleIdentifierFieldName();
                $idColName = $quoteStrategy->getColumnName($identifier, $meta, $this->platform);
                if ($ea->usesPersonalTranslation($transClass)) {
                    $sql .= ' AND ' . $tblAlias . '.' . $transMeta->getSingleAssociationJoinColumnName('object')
                        . ' = ' . $compTblAlias . '.' . $idColName;
                } else {
                    $sql .= ' AND ' . $tblAlias . '.' . $quoteStrategy->getColumnName('objectClass', $transMeta, $this->platform)
                        . ' = ' . $this->conn->quote($config['useObjectClass']);

                    $mappingFK = $transMeta->getFieldMapping('foreignKey');
                    $mappingPK = $meta->getFieldMapping($identifier);
                    $fkColName = $this->getCastedForeignKey($compTblAlias . '.' . $idColName, $mappingFK['type'], $mappingPK['type']);
                    $sql .= ' AND ' . $tblAlias . '.' . $quoteStrategy->getColumnName('foreignKey', $transMeta, $this->platform)
                        . ' = ' . $fkColName;
                }
                isset($this->components[$dqlAlias]) ? $this->components[$dqlAlias] .= $sql : $this->components[$dqlAlias] = $sql;

                $originalField = $compTblAlias . '.' . $quoteStrategy->getColumnName($field, $meta, $this->platform);
                $substituteField = $tblAlias . '.' . $quoteStrategy->getColumnName('content', $transMeta, $this->platform);
                // Treat translation as original field type
                $fieldMapping = $meta->getFieldMapping($field);
                if ((($this->platform instanceof MySqlPlatform)
                        && 'decimal' === $fieldMapping['type'])
                    || (!($this->platform instanceof MySqlPlatform)
                        &&
                        !in_array($fieldMapping['type'], [
                            'datetime',
                            'datetimetz',
                            'date',
                            'time',
                        ], true))
                ) {
                    $type = Type::getType($fieldMapping['type']);
                    $substituteField = 'CAST(' . $substituteField . ' AS ' . $type->getSQLDeclaration($fieldMapping, $this->platform) . ')';
                }

                // Fallback to original if was asked for
                if (($this->needsFallback() && (!isset($config['fallback'][$field]) || $config['fallback'][$field]))
                    || (!$this->needsFallback() && isset($config['fallback'][$field]) && $config['fallback'][$field])
                ) {
                    $substituteField = 'COALESCE(' . $substituteField . ', ' . $originalField . ')';
                }

                $this->replacements[$originalField] = $substituteField;
            }
        }
    }

    private function getFilterFieldsByDqlAlias(string $dqlAlias, array $fields): array
    {
        $return = [];
        foreach ($fields as $field) {
            $existField = $this->filterFields[$dqlAlias][$field] ?? false;
            if ($existField) {
                $return[] = $field;
            }
        }

        return $return;

    }

    /**
     * Casts a foreign key if needed
     *
     * @NOTE: personal translations manages that for themselves.
     *
     * @param $component - a column with an alias to cast
     * @param $typeFK - translation table foreign key type
     * @param $typePK - primary key type which references translation table
     *
     * @return string - modified $component if needed
     */
    private function getCastedForeignKey($component, $typeFK, $typePK): string
    {
        // the keys are of same type
        if ($typeFK === $typePK) {
            return $component;
        }

        // try to look at postgres casting
        if ($this->platform instanceof PostgreSqlPlatform) {
            switch ($typeFK) {
                case 'string':
                case 'guid':
                    // need to cast to VARCHAR
                    $component .= '::VARCHAR';
                    break;
            }
        }

        // @TODO may add the same thing for MySQL for performance to match index

        return $component;
    }

    /**
     * Checks if translation fallbacks are needed
     *
     * @return boolean
     */
    private function needsFallback(): bool
    {
        $q = $this->getQuery();
        $fallback = $q->getHint(TranslatableListener::HINT_FALLBACK);
        if (false === $fallback) {
            // non overrided
            $fallback = $this->listener->getTranslationFallback();
        }

        // applies fallbacks to scalar hydration as well
        return (bool)$fallback;
    }

    /**
     * @param SelectStatement $AST
     * @return string
     * @throws Query\QueryException
     * @throws OptimisticLockException
     */
    public function walkSelectStatement(SelectStatement $AST): string
    {
        $result = parent::walkSelectStatement($AST);
        if (!count($this->translatedComponents)) {
            return $result;
        }

        $hydrationMode = $this->getQuery()->getHydrationMode();
        if ($hydrationMode === Query::HYDRATE_OBJECT) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_OBJECT_TRANSLATION,
                ObjectHydrator::class
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        } elseif ($hydrationMode === Query::HYDRATE_SIMPLEOBJECT) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_SIMPLE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
                SimpleObjectHydrator::class
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        }

        return $result;
    }

    /**
     * @param $selectClause
     * @return string
     */
    public function walkSelectClause($selectClause): string
    {
        $result = parent::walkSelectClause($selectClause);
        $result = $this->replace($this->replacements, $result);

        return $result;
    }

    /**
     * Replaces given sql $str with required
     * results
     *
     * @param array $repl
     * @param string $str
     *
     * @return string
     */
    private function replace(array $repl, $str): string
    {
        foreach ($repl as $target => $result) {
            $str = preg_replace_callback('/(\s|\()(' . $target . ')(,?)(\s|\))/smi', static function ($m) use ($result) {
                return $m[1] . $result . $m[3] . $m[4];
            }, $str);
        }

        return $str;
    }

    /**
     * @param $fromClause
     * @return string
     */
    public function walkFromClause($fromClause): string
    {
        $result = parent::walkFromClause($fromClause);
        $result .= $this->joinTranslations($fromClause);

        return $result;
    }

    /**
     * Walks from clause, and creates translation joins
     * for the translated components
     *
     * @param FromClause $from
     *
     * @return string
     */
    private function joinTranslations($from): string
    {
        $result = '';
        foreach ($from->identificationVariableDeclarations as $decl) {
            if (($decl->rangeVariableDeclaration instanceof RangeVariableDeclaration) && isset($this->components[$decl->rangeVariableDeclaration->aliasIdentificationVariable])) {
                $result .= $this->components[$decl->rangeVariableDeclaration->aliasIdentificationVariable];
            }
            if (isset($decl->joinVariableDeclarations)) {
                foreach ((array)$decl->joinVariableDeclarations as $joinDecl) {
                    if (($joinDecl->join instanceof Join) && isset($this->components[$joinDecl->join->aliasIdentificationVariable])) {
                        $result .= $this->components[$joinDecl->join->aliasIdentificationVariable];
                    }
                }
            } else {
                // based on new changes
                foreach ((array)$decl->joins as $join) {
                    if (($join instanceof Join) && isset($this->components[$join->joinAssociationDeclaration->aliasIdentificationVariable])) {
                        $result .= $this->components[$join->joinAssociationDeclaration->aliasIdentificationVariable];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkWhereClause($whereClause): string
    {
        $result = parent::walkWhereClause($whereClause);

        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkHavingClause($havingClause): string
    {
        $result = parent::walkHavingClause($havingClause);

        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkOrderByClause($orderByClause): string
    {
        $result = parent::walkOrderByClause($orderByClause);

        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkSubselect($subselect): string
    {
        return parent::walkSubselect($subselect);
    }

    /**
     * {@inheritDoc}
     */
    public function walkSubselectFromClause($subselectFromClause): string
    {
        $result = parent::walkSubselectFromClause($subselectFromClause);
        $result .= $this->joinTranslations($subselectFromClause);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause): string
    {
        $result = parent::walkSimpleSelectClause($simpleSelectClause);

        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkGroupByClause($groupByClause): string
    {
        $result = parent::walkGroupByClause($groupByClause);

        return $this->replace($this->replacements, $result);
    }
}
