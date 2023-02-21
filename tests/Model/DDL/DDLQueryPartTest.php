<?php

declare(strict_types=1);

namespace App\Tests\Model\DDL;

use App\Model\DDL\DDLQueryPartInterface;
use App\Model\DDL\FieldStructure;
use App\Model\DDL\TableStructure;
use PHPUnit\Framework\TestCase;

class DDLQueryPartTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDDLQueryInterfaceReturnValidDDL(DDLQueryPartInterface $input, string $expected): void
    {
        self::assertEquals($input->toDDL(), $expected);
    }

    /**
     * @return array<array-key, array>
     */
    public static function dataProvider(): array
    {
        return [
            [
                new FieldStructure(
                    'description',
                    'character varying',
                    'YES',
                    'NULL::character varying',
                    500
                ),
                'description character varying(500) DEFAULT NULL::character varying'
            ],
            [
                new FieldStructure(
                    'id',
                    'uuid',
                    'NO',
                    null,
                    null
                ),
                'id uuid NOT NULL'
            ],
            [
                new TableStructure(
                    'test_table',
                    [
                        new FieldStructure(
                            'id',
                            'uuid',
                            'NO',
                            null,
                            null
                        ),
                        new FieldStructure(
                            'description',
                            'character varying',
                            'YES',
                            'NULL::character varying',
                            500
                        ),
                    ]
                ),
                "CREATE TABLE test_table \n(\n     id uuid NOT NULL,\n     description character varying(500) DEFAULT NULL::character varying\n);"
            ],
        ];
    }
}
