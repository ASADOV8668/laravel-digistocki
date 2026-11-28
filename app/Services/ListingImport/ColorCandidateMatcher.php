<?php

namespace App\Services\ListingImport;

final class ColorCandidateMatcher
{
    private const ALIASES = [
        ['burgundy', 'زرشکی', 'شرابی'],
        ['red', 'قرمز'],
        ['yellow', 'زرد'],
        ['gold', 'طلایی'],
        ['green', 'سبز'],
        ['teal', 'سبز آبی', 'سبزآبی', 'سبز کله غازی'],
        ['blue', 'آبی'],
        ['midnight', 'میدنایت', 'آبی نیمه شب', 'نیمه شب'],
        ['glacier', 'گلیشر', 'آبی یخچالی', 'یخچالی'],
        ['purple', 'بنفش'],
        ['black', 'مشکی', 'سیاه'],
        ['night', 'نایت', 'شب'],
        ['white', 'سفید'],
        ['star', 'starlight', 'استارلایت', 'نور ستاره', 'ستاره ای'],
        ['silver', 'نقره ای', 'سیلور'],
        ['graphite', 'گرافیتی', 'گرافیت'],
        ['orange', 'نارنجی'],
        ['desert', 'desert titanium', 'دزرت', 'تیتانیوم صحرایی', 'صحرایی'],
    ];

    public function match(string $text, array $candidates, array $options): ?string
    {
        $text = $this->normalize($text);
        $explicit = [];
        $eligible = [];
        $compound = [];
        $candidateLabels = array_merge([], ...array_map($this->labels(...), $candidates));

        foreach ($options as $option) {
            $labels = $this->labels($option);
            $written = array_filter($labels, fn (string $label): bool => $this->contains($text, $label));
            if ($written !== []) {
                $explicit[$option] = $written;
            }

            foreach ($candidates as $candidate) {
                if (in_array($this->normalize($candidate), $labels, true)) {
                    $eligible[$option] = true;
                    break;
                }
            }

            $normalizedOption = $this->normalize($option);
            foreach ($candidateLabels as $label) {
                if ($this->contains($normalizedOption, $label)) {
                    $compound[$option] = true;
                    break;
                }
            }
        }

        if ($explicit !== []) {
            $writtenLabels = array_merge(...array_values($explicit));
            // Prefer specific labels, such as «آبی نیمه شب», over words inside them.
            $explicit = array_filter($explicit, fn (array $labels): bool => (bool) array_filter(
                $labels, fn (string $label): bool => ! array_filter($writtenLabels,
                    fn (string $other): bool => $other !== $label && $this->contains($other, $label)),
            ));

            return count($explicit) === 1 ? (string) array_key_first($explicit) : null;
        }

        $matches = $eligible ?: $compound;

        return count($matches) === 1 ? (string) array_key_first($matches) : null;
    }

    private function labels(string $option): array
    {
        $normalized = $this->normalize($option);
        foreach (self::ALIASES as $aliases) {
            $labels = array_map($this->normalize(...), $aliases);
            if (in_array($normalized, $labels, true)) {
                return $labels;
            }
        }

        return [$normalized];
    }

    private function normalize(string $value): string
    {
        $value = strtr(mb_strtolower($value), ['آ' => 'ا', 'أ' => 'ا', 'ي' => 'ی', 'ك' => 'ک']);

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '');
    }

    private function contains(string $text, string $label): bool
    {
        return $label !== '' && str_contains(' '.$text.' ', ' '.$label.' ');
    }
}
