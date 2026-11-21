<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\TestCase;
use Kohana\Http\Upload;
use Kohana\Foundation\KohanaException;

/**
 * Tests Kohana upload class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.upload')]
class Kohana_UploadTest extends TestCase
{
    /**
     * Provides test data for test_size()
     *
     * @return array
     */
    public static function provider_size(): array
    {
        return [
            // $field, $bytes, $environment, $expected
            [
                'unit_test',
                5,
                [
                    '_FILES' => [
                        'unit_test' => [
                            'error' => UPLOAD_ERR_INI_SIZE
                        ]
                    ]
                ],
                false
            ],
            [
                'unit_test',
                5,
                [
                    '_FILES' => [
                        'unit_test' => [
                            'error' => UPLOAD_ERR_NO_FILE
                        ]
                    ]
                ],
                true
            ],
            [
                'unit_test',
                '6K',
                [
                    '_FILES' => [
                        'unit_test' => [
                            'error' => UPLOAD_ERR_OK,
                            'name' => 'Unit_Test File',
                            'type' => 'image/png',
                            'tmp_name' => KOHANA_FIXTURES . '/github.png',
                            'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                        ]
                    ]
                ],
                true
            ],
            [
                'unit_test',
                '1B',
                ['_FILES' => [
                        'unit_test' => [
                            'error' => UPLOAD_ERR_OK,
                            'name' => 'Unit_Test File',
                            'type' => 'image/png',
                            'tmp_name' => KOHANA_FIXTURES . '/github.png',
                            'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                        ]
                    ]
                ],
                false
            ],
        ];
    }

    /**
     * Tests Upload::size
     *
     * @param string $field the files field to test
     * @param string $bytes valid bite size
     * @param array $environment set the $_FILES array
     * @param bool $expected what to expect
     * @throws KohanaException
     * @throws ReflectionException
     */
    #[DataProvider('provider_size')]
    #[Test]
    public function test_size($field, $bytes, array $environment, $expected)
    {
        $this->setEnvironment($environment);

        $this->assertSame($expected, Upload::size($_FILES[$field], $bytes));
    }

    /**
     * size() should throw an exception if the supplied max size is invalid
     */
    #[Test]
    public function test_size_throws_exception_for_invalid_size()
    {
        $this->expectException(KohanaException::class);

        $this->setEnvironment([
            '_FILES' => [
                'unit_test' => [
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Unit_Test File',
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ]
        ]);

        Upload::size($_FILES['unit_test'], '1DooDah');
    }

    /**
     * Provides test data for test_valid()
     *
     * @return array
     */
    public static function provider_valid(): array
    {
        return [
            [
                true,
                [
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Unit_Test File',
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ],
            [
                false,
                [
                    'name' => 'Unit_Test File',
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ],
            [
                false,
                [
                    'error' => UPLOAD_ERR_OK,
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ],
            [
                false,
                [
                    'name' => 'Unit_Test File',
                    'error' => UPLOAD_ERR_OK,
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ],
            [
                false,
                [
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Unit_Test File',
                    'type' => 'image/png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ],
            [
                false,
                [
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'Unit_Test File',
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                ]
            ],
        ];
    }

    /**
     * Test Upload::valid
     */
    #[DataProvider('provider_valid')]
    #[Test]
    public function test_valid($expected, $file)
    {
        $this->setEnvironment([
            '_FILES' => [
                'unit_test' => $file,
            ],
        ]);

        $this->assertSame($expected, Upload::valid($_FILES['unit_test']));
    }

    /**
     * Tests Upload::type
     */
    #[Test]
    public function test_type()
    {
        $this->setEnvironment([
            '_FILES' => [
                'unit_test' => [
                    'error' => UPLOAD_ERR_OK,
                    'name' => 'github.png',
                    'type' => 'image/png',
                    'tmp_name' => KOHANA_FIXTURES . '/github.png',
                    'size' => filesize(KOHANA_FIXTURES . '/github.png'),
                ]
            ]
        ]);

        $this->assertTrue(Upload::type($_FILES['unit_test'], ['jpg', 'png', 'gif']));

        $this->assertFalse(Upload::type($_FILES['unit_test'], ['docx']));
    }

}
