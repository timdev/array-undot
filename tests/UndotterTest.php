<?php
declare(strict_types=1);

namespace TimDev\Test\ArrayUndot;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use PHPUnit\Framework\TestCase;
use TimDev\ArrayUndot\Undotter;

class UndotterTest extends TestCase
{

    private function undot(array $array): array
    {
        return (new Undotter())($array);
    }

    public function testBasic(): void
    {
        $base = ['a.b.c' => 'PASS'];

        self::assertEquals(
            ['a' => ['b' => ['c' => 'PASS']]],
            $this->undot($base)
        );
    }

    public function testOrderMatters(): void
    {
        // stuff defined later should always win
        $config = [
            'a' => ['b' => 'Nested'],
            'a.b' => 'Dotted'
        ];

        self::assertSame('Dotted', $this->undot($config)['a']['b']);

        // if we swap the position of the elements, "Nested" should win.
        $config = array_reverse($config, true);
        self::assertSame('Nested', $this->undot($config)['a']['b']);
    }

    public function testMixedNestingAndDots(): void
    {
        $config = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 'FAIL'
                    ]
                ],
                'b.c' => [
                    'd' => 'PASS'
                ]
            ],
        ];

        self::assertEquals(
            ['a' => ['b' => ['c' =>['d' => 'PASS']]]],
            $this->undot($config)
        );
    }

    public function testMergesSubArrays(): void
    {
        // This also demonstrates using Undotter as a post-processor
        // with Laminas ConfigAggregator. Doing so is a good idea,
        // since post-processors run *before* the merged array is
        // cached by ConfigAggregator

        $base = [
            'a' => ['b' => ['c' => 'FAIL', 'd' => 'PASS']],
            'm' => ['n.o' => ['p.q' => 'PASS', 'p.r' => 'FAIL']],
            'x.y' => ['z' => 'FAIL'],
        ];

        $over = [
            'a.b.c' => 'PASS',
            'm' => ['n' => ['s' => ['p' => 'PASS'], 'o.p.r' => 'PASS']],
            'x' => ['y' => ['z' => 'PASS']]
        ];

        $over2 = [
            'a.b' => ['c' => ['dd' => 'PASS', 'ee' => 'PASS']]
        ];

        $result = (new ConfigAggregator(
            [
                new ArrayProvider($base),
                new ArrayProvider($over),
                new ArrayProvider($over2),
            ],
            null,
            [Undotter::class]       // Undotter is implements __invoke()!
        ))->getMergedConfig();

        $expected = [
            'c' => [
                'dd' => 'PASS',
                'ee' => 'PASS'
            ],
            'd' => 'PASS'
        ];
        self::assertEquals($expected, $result['a']['b']);
        self::assertEquals(['a', 'm', 'x'], array_keys($result));

    }

    public function testDocumentationExample1(): void
    {
        $input = ['a.b' => 'FOO'];
        $expected = ['a' => ['b' => 'FOO']];
        self::assertEquals($expected, $this->undot($input));
    }

    public function testDocumentationExample2(): void
    {
        $input = ['top' => ['a' => ['b.c' => 'FOO']]];
        $expected = ['top' => ['a' => ['b' => ['c' => 'FOO']]]];
        self::assertEquals($expected, $this->undot($input));
    }

    public function testDocumentationExample3(): void
    {
        $input = [
            'a' => ['b' => 'FOO'],
            'a.b' => 'BAR'
        ];
        $expected = ['a' => ['b' => 'BAR']];
        self::assertEquals($expected, $this->undot($input));

        $expected2 = ['a' => ['b' => 'FOO']];
        self::assertEquals($expected2, $this->undot(array_reverse($input)));
    }

    public function testDocumentationExample4(): void
    {
        $input = [
            'a' => [
                'b' => ['foo' => 1, 'bar' => 2, 'baz' => 3]
            ],
            'a.b' =>
                ['baz' => 4, 'qux' => 5]
        ];

        $expected = [
            'a' => [
                'b' => [
                    'foo' => 1,
                    'bar' => 2,
                    'baz' => 4,
                    'qux' => 5
                ]
            ]
        ];
        self::assertEquals($expected, $this->undot($input));
    }
}
