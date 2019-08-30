# zf3belcebur-doctrine-orm-resources
DoctrineORMResources

Integrate different modules and provide new resources for DoctrineORM & ZF3:
- [doctrine/doctrine-module](https://packagist.org/packages/doctrine/doctrine-module)
- [doctrine/doctrine-orm-module](https://packagist.org/packages/doctrine/doctrine-orm-module)
- [creof/doctrine2-spatial](https://packagist.org/packages/creof/doctrine2-spatial)
- [beberlei/doctrineextensions](https://packagist.org/packages/beberlei/doctrineextensions)
- [gedmo/doctrine-extensions](https://packagist.org/packages/gedmo/doctrine-extensions)


## See
- [https://packagist.org/explore/?query=zf3belcebur](https://packagist.org/explore/?query=zf3belcebur)

## Installation

Installation of this module uses composer. For composer documentation, please refer to
[getcomposer.org](http://getcomposer.org/).

```sh
composer require zf3belcebur/doctrine-orm-resources
```

Then add `ZF3Belcebur\DoctrineORMResources` to your `config/application.config.php`

## Entity Traits
- `ZF3\DoctrineORMResources\EntityTrait\Coordinates` 
    - Latitude and Longitude fields
- `ZF3\DoctrineORMResources\EntityTrait\GedmoEntityTranslatable` 
    - Gedmo locale field
- `ZF3\DoctrineORMResources\EntityTrait\Timestamp` 
    - modified_at and created_at fields with Gedmo Timestampable

## Extend your repository with`\ZF3Belcebur\DoctrineORMResources\Repository\BaseEntityRepository`

Extends `Doctrine\ORM\EntityRepository` with access to use `Zend\Http\PhpEnvironment\Request`,`Zend\Mvc\I18n\Router\TranslatorAwareTreeRouteStack`,`Zend\Router\RouteMatch` and `Zend\Router\RouteStackInterface` and use the new method findByQb

```php
return [
'doctrine' => [
    'configuration' => [
        'orm_default' => [
            'class_metadata_factory_name' => ClassMetadataFactory::class,
            'repository_factory' => BaseRepository::class,
        ]
    ]
];
```

### Event Post Construct Repository

- If you need to do something after the __construct of the repository then you need to extend your repository 
of class `ZF3Belcebur\DoctrineORMResources\Repository\BaseEntityRepository` and implement
 `ZF3Belcebur\DoctrineORMResources\Repository\PostConstructInterface`.


### How to integrate GedmoSortableListener with your repository

- Enable Sortable Listener in your config

```php
return [
    'doctrine' => [
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    TranslatableListener::class,
                    TimestampableListener::class,
                    SortableListener::class,
                ],
            ],
        ],
    ],
];
```

- Implement `PostConstructInterface` event use `ZF3Belcebur\DoctrineORMResources\RepositoryTrait` in your repository 

```php
/**
 * CustomRepo
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomRepo extends BaseEntityRepository implements PostConstructInterface
{
    use \ZF3Belcebur\DoctrineORMResources\RepositoryTrait;
    
    public function postConstruct(): void
    {
        $this->initListenerConfig();
    }
}
```

### New method getEntityAlias()
```php
    // Entity --> Application\Entity\Admin1
   echo $admin1Repo->getEntityAlias(); // --> "a" 
```

### New method getQueryWithGedmoTranslation()

This method apply Gedmo Walkers and Hints to query, get defaultLocale from param or find "locale" on routeMatch params
Definition
```php
   public function getQueryWithGedmoTranslation(QueryBuilder $qb, string $locale = null, string $defaultLocale = null): Query
```


### New method findByQb()

Definition
```php
   public function findByQb(array $criteria, array $orderBy = [], int $limit = null, int $offset = null, string $alias = null): QueryBuilder;
```

Examples
```php
$admin1sQb = $admin1Repo->findByQb(['country' => $country], ['name' => 'ASC']);
$criteria =
    [
        'orX' => [
            [
                'operator' => 'like',
                'value' => '%espa%',
                'field' => 'name',
            ],
            [
                'operator' => 'like',
                'value' => '%espa%',
                'field' => 'slugName',
            ],
            'andX' => [
                [
                    'operator' => 'eq',
                    'value' => '%espa%',
                    'field' => 'name',
                ],
                [
                    'operator' => 'isNull',
                    'field' => 'slugName',
                ],
            ],
        ],
        [
            'operator' => 'like',
            'value' => '%cosas%',
            'field' => 'slug',
        ],
    ];
   $admin1sQb = $admin1Repo->findByQb($criteria);
```



# Validators

- `NoObjectExist` allow use multiple fields in validator
- `UniqueObject` allow use multiple fields in validator


# Gedmo Performance Translatable

#### Walkers
Extends `GedmoTranslationWalker` to only do necessary query joins 

#### Custom class_metadata_factory_name `ZF3Belcebur\DoctrineORMResources\ORM\Mapping\ClassMetadata`
- [https://github.com/Atlantic18/DoctrineExtensions/pull/1432](https://github.com/Atlantic18/DoctrineExtensions/pull/1432) 

```php
return [
    __NAMESPACE__ => [
        'gedmo' => [
            'custom_translation_classes' => [
                // 'YourNameSpace\CustomEntityTranslation1',
                // 'YourNameSpace\CustomEntityTranslation2',
                // 'YourNameSpace\CustomEntityTranslation3',
            ]
        ]
    ],
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'class_metadata_factory_name' => ClassMetadataFactory::class,
            ]
        ]
    ]
];
```

## Configuration by default

[See module.config.php](./config/module.config.php)

```php
namespace ZF3Belcebur\DoctrineORMResources;

return [
    __NAMESPACE__ => [
        'gedmo' => [
            'custom_translation_classes' => [
                // 'YourNameSpace\CustomEntityTranslation'
            ]
        ]
    ],
    'service_manager' => [
        'factories' => [
            BaseRepository::class => BaseRepositoryFactory::class,
        ],
    ],
    'doctrine' => [
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    TranslatableListener::class,
                    TimestampableListener::class,
                    //SortableListener::class,
                ],
            ],
        ],
        'driver' => [
            'translatable_orm_metadata_driver' => [
                'class' => AnnotationORMDriver::class,
                'cache' => 'array',
                'paths' => [
                    getcwd() . '/vendor/gedmo/doctrine-extensions/src/Translatable/Entity',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    'Gedmo\Translatable\Entity' => 'translatable_orm_metadata_driver',
                ],
            ],
        ],
        'configuration' => [
            'orm_default' => [
                'class_metadata_factory_name' => ClassMetadataFactory::class,
                'repository_factory' => BaseRepository::class,
                'types' => [
                    'point' => PointType::class,
                    'carbondate' => CarbonDateType::class,
                    'carbontime' => CarbonTimeType::class,
                    'linestring' => LineStringType::class,
                    'polygon' => PolygonType::class,
                    'multipolygon' => MultiPolygonType::class,
                ],
                'datetime_functions' => [
                    'date' => Date::class,
                    'date_format' => DateFormat::class,
                    'dateadd' => DateAdd::class,
                    'datediff' => DateDiff::class,
                    'day' => Day::class,
                    'dayname' => DayName::class,
                    'last_day' => LastDay::class,
                    'minute' => Minute::class,
                    'second' => Second::class,
                    'strtodate' => StrToDate::class,
                    'time' => Time::class,
                    'timestampadd' => TimestampAdd::class,
                    'timestampdiff' => TimestampDiff::class,
                    'week' => Week::class,
                    'weekday' => WeekDay::class,
                    'year' => Year::class,
                ],
                'numeric_functions' => [
                    'acos' => Acos::class,
                    'asin' => Asin::class,
                    'atan2' => Atan2::class,
                    'atan' => Atan::class,
                    'cos' => Cos::class,
                    'cot' => Cot::class,
                    'hour' => Hour::class,
                    'pi' => Pi::class,
                    'power' => Power::class,
                    'quarter' => Quarter::class,
                    'rand' => Rand::class,
                    'round' => Round::class,
                    'sin' => Sin::class,
                    'std' => Std::class,
                    'tan' => Tan::class,
                    'st_contains' => STContains::class,
                    'contains' => Contains::class,
                    'st_area' => Area::class,
                    STDistanceSphere::FUNCTION_NAME => STDistanceSphere::class,
                    'ST_Distance' => STDistance::class,
                    'GeomFromText' => GeomFromText::class,
                    'st_intersects' => STIntersects::class,
                    'st_buffer' => STBuffer::class,
                    'Point' => Point::class,
                    'GLength' => GLength::class,
                    'LineString' => LineString::class,
                    'LineStringFromWKB' => LineStringFromWKB::class,
                ],
                'string_functions' => [
                    'binary' => Binary::class,
                    'char_length' => CharLength::class,
                    'concat_ws' => ConcatWs::class,
                    'countif' => CountIf::class,
                    'crc32' => Crc32::class,
                    'degrees' => Degrees::class,
                    'field' => Field::class,
                    'find_in_set' => FindInSet::class,
                    'group_concat' => GroupConcat::class,
                    'ifelse' => IfElse::class,
                    'ifnull' => IfNull::class,
                    'match_against' => MatchAgainst::class,
                    'md5' => Md5::class,
                    'month' => Month::class,
                    'monthname' => MonthName::class,
                    'nullif' => NullIf::class,
                    'radians' => Radians::class,
                    'regexp' => Regexp::class,
                    'replace' => Replace::class,
                    'sha1' => Sha1::class,
                    'sha2' => Sha2::class,
                    'soundex' => Soundex::class,
                    'uuid_short' => UuidShort::class,
                ],
            ],
        ],
    ],
];
    
```
