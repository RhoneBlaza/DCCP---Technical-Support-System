<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects Lorem Ipsum filler so placeholder text can never reach a
 * user-facing lookup table.
 *
 * Seeded demo data once shipped a priority named "doloremque" with the
 * description "Sint a nemo ex minus.", which is indistinguishable from a real
 * entry once it reaches production.
 *
 * The word list is deliberately restricted to filler that never appears in
 * genuine IT-support copy. Common words that Lorem Ipsum also happens to use
 * ("error", "sit", "ut", "est" and friends) are excluded so the rule cannot
 * block a legitimate value.
 */
class NotPlaceholderText implements ValidationRule
{
    /**
     * @var list<string>
     */
    protected const PLACEHOLDER_WORDS = [
        'aliqua', 'aliquam', 'aperiam', 'architecto', 'asperiores', 'assumenda',
        'beatae', 'commodi', 'consectetur', 'consequatur', 'corporis', 'cupiditate',
        'debitis', 'deleniti', 'deserunt', 'dignissimos', 'distinctio', 'dolore',
        'doloremque', 'doloribus', 'ducimus', 'eaque', 'eius', 'eligendi', 'enim',
        'eveniet', 'excepturi', 'exercitationem', 'expedita', 'explicabo', 'facilis',
        'fuga', 'harum', 'illo', 'illum', 'impedit', 'incidunt', 'inventore',
        'ipsam', 'ipsum', 'iste', 'itaque', 'iusto', 'labore', 'laboriosam',
        'laborum', 'laudantium', 'magnam', 'maiores', 'maxime', 'minima',
        'modi', 'molestiae', 'mollitia', 'necessitatibus', 'nemo', 'neque',
        'nesciunt', 'nihil', 'nostrum', 'nulla', 'numquam', 'occaecati', 'odio',
        'officia', 'omnis', 'pariatur', 'perferendis', 'perspiciatis', 'placeat',
        'porro', 'possimus', 'praesentium', 'provident', 'quibusdam', 'quisquam',
        'quisque', 'ratione', 'recusandae', 'reiciendis', 'repellat', 'repellendus',
        'reprehenderit', 'repudiandae', 'saepe', 'sapiente', 'sequi', 'similique',
        'suscipit', 'temporibus', 'tenetur', 'totam', 'ullam', 'velit', 'veniam',
        'veritatis', 'voluptate', 'voluptatem', 'voluptatibus', 'voluptatum',
    ];

    /**
     * @param  list<string>  $words
     */
    public function __construct(protected array $words = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $vocabulary = array_map('mb_strtolower', $this->words ?: self::PLACEHOLDER_WORDS);

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $hits = array_values(array_unique(array_intersect($tokens, $vocabulary)));

        if ($hits !== []) {
            $fail('The :attribute contains placeholder text ("'.implode('", "', $hits).'"). Please enter a real value.');
        }
    }
}
