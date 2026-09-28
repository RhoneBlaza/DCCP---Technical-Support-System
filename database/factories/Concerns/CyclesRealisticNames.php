<?php

namespace Database\Factories\Concerns;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Lookup tables are seeded with a small pool of realistic names so that rendered
 * test output stays readable, but faker's unique() generator throws once a pool
 * is exhausted, which any test that creates a batch of records will hit. These
 * helpers cycle the pool instead, keeping values unique without ever throwing.
 *
 * @mixin Factory
 */
trait CyclesRealisticNames
{
    protected static int $nameSequence = 0;

    protected static int $numberSequence = 0;

    protected static int $codeSequence = 0;

    /**
     * @param  list<string>  $pool
     */
    protected static function nextRealisticName(array $pool): string
    {
        $index = static::$nameSequence++;
        $name = $pool[$index % count($pool)];
        $round = intdiv($index, count($pool));

        return $round === 0 ? $name : $name.' '.$round;
    }

    /**
     * A unique number from an inclusive range, cycling back to the start of the
     * range only after every value in it has been handed out.
     */
    protected static function nextUniqueNumber(int $min, int $max): int
    {
        $span = $max - $min + 1;

        return $min + (static::$numberSequence++ % $span);
    }

    /**
     * A short readable code, unique per record, for lookup tables keyed by code.
     */
    protected static function nextCode(string $prefix = 'DPT'): string
    {
        return $prefix.'-'.str_pad((string) (static::$codeSequence++ + 1), 3, '0', STR_PAD_LEFT);
    }
}
