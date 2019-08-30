<?php

namespace ZF3Belcebur\DoctrineORMResources;


use CrEOF\Spatial\DBAL\Types\Geometry\LineStringType;
use CrEOF\Spatial\DBAL\Types\Geometry\MultiPolygonType;
use CrEOF\Spatial\DBAL\Types\Geometry\PointType;
use CrEOF\Spatial\DBAL\Types\Geometry\PolygonType;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\Area;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\Contains;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\GeomFromText;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\GLength;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\LineString;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\LineStringFromWKB;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\Point;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\STBuffer;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\STContains;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\STDistance;
use CrEOF\Spatial\ORM\Query\AST\Functions\MySql\STIntersects;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationORMDriver;
use DoctrineExtensions\Query\Mysql\Acos;
use DoctrineExtensions\Query\Mysql\Asin;
use DoctrineExtensions\Query\Mysql\Atan;
use DoctrineExtensions\Query\Mysql\Atan2;
use DoctrineExtensions\Query\Mysql\Binary;
use DoctrineExtensions\Query\Mysql\CharLength;
use DoctrineExtensions\Query\Mysql\ConcatWs;
use DoctrineExtensions\Query\Mysql\Cos;
use DoctrineExtensions\Query\Mysql\Cot;
use DoctrineExtensions\Query\Mysql\CountIf;
use DoctrineExtensions\Query\Mysql\Crc32;
use DoctrineExtensions\Query\Mysql\Date;
use DoctrineExtensions\Query\Mysql\DateAdd;
use DoctrineExtensions\Query\Mysql\DateDiff;
use DoctrineExtensions\Query\Mysql\DateFormat;
use DoctrineExtensions\Query\Mysql\Day;
use DoctrineExtensions\Query\Mysql\DayName;
use DoctrineExtensions\Query\Mysql\Degrees;
use DoctrineExtensions\Query\Mysql\Field;
use DoctrineExtensions\Query\Mysql\FindInSet;
use DoctrineExtensions\Query\Mysql\GroupConcat;
use DoctrineExtensions\Query\Mysql\Hour;
use DoctrineExtensions\Query\Mysql\IfElse;
use DoctrineExtensions\Query\Mysql\IfNull;
use DoctrineExtensions\Query\Mysql\LastDay;
use DoctrineExtensions\Query\Mysql\MatchAgainst;
use DoctrineExtensions\Query\Mysql\Md5;
use DoctrineExtensions\Query\Mysql\Minute;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\MonthName;
use DoctrineExtensions\Query\Mysql\NullIf;
use DoctrineExtensions\Query\Mysql\Pi;
use DoctrineExtensions\Query\Mysql\Power;
use DoctrineExtensions\Query\Mysql\Quarter;
use DoctrineExtensions\Query\Mysql\Radians;
use DoctrineExtensions\Query\Mysql\Rand;
use DoctrineExtensions\Query\Mysql\Regexp;
use DoctrineExtensions\Query\Mysql\Replace;
use DoctrineExtensions\Query\Mysql\Round;
use DoctrineExtensions\Query\Mysql\Second;
use DoctrineExtensions\Query\Mysql\Sha1;
use DoctrineExtensions\Query\Mysql\Sha2;
use DoctrineExtensions\Query\Mysql\Sin;
use DoctrineExtensions\Query\Mysql\Soundex;
use DoctrineExtensions\Query\Mysql\Std;
use DoctrineExtensions\Query\Mysql\StrToDate;
use DoctrineExtensions\Query\Mysql\Tan;
use DoctrineExtensions\Query\Mysql\Time;
use DoctrineExtensions\Query\Mysql\TimestampAdd;
use DoctrineExtensions\Query\Mysql\TimestampDiff;
use DoctrineExtensions\Query\Mysql\UuidShort;
use DoctrineExtensions\Query\Mysql\Week;
use DoctrineExtensions\Query\Mysql\WeekDay;
use DoctrineExtensions\Query\Mysql\Year;
use DoctrineExtensions\Types\CarbonDateType;
use DoctrineExtensions\Types\CarbonTimeType;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use ZF3Belcebur\DoctrineORMResources\ORM\Mapping\ClassMetadataFactory;
use ZF3Belcebur\DoctrineORMResources\Query\Functions\Mysql\STDistanceSphere;
use ZF3Belcebur\DoctrineORMResources\Repository\BaseRepository;
use ZF3Belcebur\DoctrineORMResources\Repository\BaseRepositoryFactory;

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
