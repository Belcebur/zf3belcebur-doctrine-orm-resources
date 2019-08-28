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


### new method getEntityAlias()
```php
    // Entity --> Application\Entity\Admin1
   echo $admin1Repo->getEntityAlias(); // --> "a" 
```

### new method getQueryWithGedmoTranslation()

This method apply Gedmo Walkers and Hints to query, get defaultLocale from param or find "locale" on routeMatch params
Definition
```php
   public function getQueryWithGedmoTranslation(QueryBuilder $qb, string $locale = null, string $defaultLocale = null): Query
```

### new method findByQb()

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


# Gedmo Performance

#### Walkers
Extends `GedmoTranslationWalker` to only do necessary query joins 

#### Custom class_metadata_factory_name `ZF3Belcebur\DoctrineORMResources\ORM\Mapping`
- [https://github.com/Atlantic18/DoctrineExtensions/pull/1432](https://github.com/Atlantic18/DoctrineExtensions/pull/1432) 
- [https://github.com/Atlantic18/DoctrineExtensions/pull/1432](https://github.com/Atlantic18/DoctrineExtensions/pull/1432) 

```php
return [
'doctrine' => [
    'configuration' => [
        'orm_default' => [
            'class_metadata_factory_name' => ClassMetadataFactory::class,
        ]
    ]
];
```

## Configuration by default

```php
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
