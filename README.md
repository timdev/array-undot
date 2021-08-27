# timdev/array-undot

![PHP Version Support](https://img.shields.io/packagist/php-v/timdev/array-undot?style=flat)
![License](https://img.shields.io/packagist/l/timdev/array-undot?style=flat&colorB=darkcyan)
![Latest Release](https://img.shields.io/github/v/tag/timdev/array-undot?style=flat&label=release)
![Type Coverage](https://shepherd.dev/github/timdev/array-undot/coverage.svg)
![CIStatus](https://img.shields.io/github/workflow/status/timdev/array-undot/Continuous%20Integration?style=flat)

## What's This?

It normalizes arrays with dotted-string keys to arrays without any dotted-string
keys. 

For example, it turns `['a.b' => 'FOO']` into `['a' => ['b' => 'FOO']]`.

## Why?

I was working on a [mezzio] project that uses [laminas-config-aggregator] to
merge a bunch of configuration data into a big nested array. Sometimes, I'd want
to change a single value somewhere deep in the structure from a local config
file.

```php
// myapp.local.php
return [
    // sometimes it's nicer (reading and writing) to do this:
    'path.to.some.key' => 'value',

    // as opposed to this:
    'path' => [
        'to' => [
            'some' => [
                'key' => 'value'
            ]
        ]
  ]
];
```

## Behavior

`Undotter::undot()` recursively traverses (depth-first) its argument, building a
new array as it goes. 

```php
use \TimDev\ArrayUndot\Undotter;

/* Example 1: The basic idea: */ 
Undotter::undot(['a.b' => 'FOO']); 
// => ['a' => ['b' => 'FOO']]

/* Example 2: Dotted keys don't need to be at the top level: */
Undotter::undot(['top' => ['a' => ['b.c' => 'FOO']]]);
// => ['top' => ['a' => ['b' => ['c' => 'FOO']]]]

/* Example 3: Conflict-Resolution depends on the order of elements in the input: */ 
Undotter::undot([
    'a' => ['b' => 'FOO'],
    'a.b' => 'BAR'
]); 
// => ['a' => ['b' => 'BAR']]

// ... but if the order is swapped
Undotter::undot([
    'a.b' => 'BAR',
    'a' => ['b' => 'FOO']
]); 
// => ['a' => ['b' => 'FOO']]

/* Example 4:  If the conflicting values are both arrays, they're merged (using the same logic as ConfigAggregator): */
Undotter::undot([
    'a' => ['b' => ['foo' => 1, 'bar' => 2, 'baz' => 3]],
    'a.b' => ['baz' => 4, 'qux' => 5]
]);
// => 
//    [
//        'a' => [
//            'b' => [
//                'foo' => 1, 
//                'bar', => 2, 
//                'baz' => 4, 
//                'qux' => 5
//            ]
//        ]
//    ]
```

## With Laminas ConfigAggregator

`Undotter` is invokable, which makes it easy to use as a post-processor with
[laminas-config-aggregator]. That's handy, since it allows you to un-dot your 
array *before* ConfigAggregator writes its cache.

For an example, see the [testMergesSubArrays()] test method.

[testMergesSubArrays()]: https://github.com/timdev/array-undot/blob/78b3bcea760f3a14510a4de3ef62de26de9ae1b1/tests/UndotterTest.php#L65-L108
[mezzio]: https://github.com/mezzio/mezzio
[laminas-config-aggregator]: https://github.com/laminas/laminas-config-aggregator
