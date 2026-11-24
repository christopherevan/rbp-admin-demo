<?php

namespace App\Services;

/**
 * Fact-checks an AI-written product description before a human sees it.
 *
 * A trimmed re-implementation of the production guard, which ran several
 * hundred rules. These four cover the failure modes that mattered most:
 *
 *   1. Figures the product facts don't contain (an invented load rating).
 *   2. Compliance claims with no evidence (FDA, Halal, ATEX...).
 *   3. The wrong kind of product ("a modular belt" for a chain).
 *   4. Copy lifted too closely from the manufacturer's own text.
 *
 * Errors block approval; warnings are for the reviewer to judge.
 */
class DescriptionGuard
{
    public const SIMILARITY_LIMIT = 0.25;

    private const COMPLIANCE_TERMS = [
        'FDA', 'EU 10/2011', 'EC 1935/2004', 'USDA', 'NSF', '3-A', 'HACCP', 'Halal', 'ATEX',
        'food grade', 'food-grade', 'antistatic', 'flame retardant', 'UL listed',
    ];

    /** family => how a description names it. */
    private const FAMILY_TERMS = [
        'belt' => 'modular belt',
        'chain' => 'table top chain',
        'sprocket' => 'sprocket',
        'foot' => 'levelling foot',
        'roller' => 'return roller',
        'bearing' => 'bearing unit',
        'profile' => 'wear strip',
    ];

    /**
     * @param  array<string, list<string>>  $facts
     * @return array{errors: list<string>, warnings: list<string>, similarity: float}
     */
    public function check(string $text, array $facts, string $family, string $title, string $manufacturerText): array
    {
        $errors = [];
        $warnings = [];
        $evidence = $title.' '.implode(' ', array_merge(...array_values($facts ?: [[]])));

        foreach ($this->numbers($text) as $number) {
            if (in_array($number, $this->numbers($evidence), true)) {
                continue;
            }

            if (strlen(str_replace('.', '', $number)) > 1) {
                $errors[] = "Unverified figure “{$number}”: it isn't in the product facts.";
            } else {
                $warnings[] = "Small number “{$number}” isn't in the facts; check it reads as prose, not a spec.";
            }
        }

        foreach (self::COMPLIANCE_TERMS as $term) {
            if ($this->mentions($text, $term) && ! $this->mentions($evidence, $term)) {
                $errors[] = "Compliance claim “{$term}” has no evidence in the product facts.";
            }
        }

        if ($claimed = $this->claimedFamily($text, $family)) {
            $errors[] = 'Describes the product as a '.self::FAMILY_TERMS[$claimed].', but it is a '.(self::FAMILY_TERMS[$family] ?? $family).'.';
        }

        $similarity = $this->similarity($text, $manufacturerText);

        if ($similarity > self::SIMILARITY_LIMIT) {
            $errors[] = sprintf('Too close to the manufacturer\'s text (%d%% of its phrases reused).', round($similarity * 100));
        }

        $length = mb_strlen(trim($text));

        if ($length < 300) {
            $warnings[] = "Short for a product page ({$length} characters).";
        } elseif ($length > 1400) {
            $warnings[] = "Long for a product page ({$length} characters).";
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'similarity' => round($similarity, 3),
        ];
    }

    /**
     * Share of the manufacturer's word 4-grams that reappear in the draft.
     */
    public function similarity(string $text, string $source): float
    {
        $source = $this->shingles($source);

        if ($source === []) {
            return 0.0;
        }

        return count(array_intersect_key($source, $this->shingles($text))) / count($source);
    }

    /** @return list<string> */
    private function numbers(string $text): array
    {
        preg_match_all('/(?<![\w.])\d+(?:[.,]\d+)?(?![\w])/u', $text, $matches);

        return array_values(array_unique(array_map(fn ($n) => str_replace(',', '.', $n), $matches[0])));
    }

    private function mentions(string $haystack, string $term): bool
    {
        return (bool) preg_match('/(?<!\w)'.preg_quote($term, '/').'(?!\w)/iu', $haystack);
    }

    /** The family named in the opening "X is a ..." claim, when it isn't the product's own. */
    private function claimedFamily(string $text, string $family): ?string
    {
        $opening = preg_split('/(?<=\.)\s/', trim($text), 2)[0] ?? '';

        if (! preg_match('/\bis an? (.+)$/iu', $opening, $match)) {
            return null;
        }

        $claim = mb_strtolower($match[1]);

        if (isset(self::FAMILY_TERMS[$family]) && str_contains($claim, self::FAMILY_TERMS[$family])) {
            return null;
        }

        foreach (self::FAMILY_TERMS as $other => $term) {
            if ($other !== $family && str_contains($claim, $term)) {
                return $other;
            }
        }

        return null;
    }

    /** @return array<string, true> */
    private function shingles(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $shingles = [];

        for ($i = 0; $i + 4 <= count($words); $i++) {
            $shingles[implode(' ', array_slice($words, $i, 4))] = true;
        }

        return $shingles;
    }
}
