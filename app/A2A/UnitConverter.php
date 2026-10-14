<?php

declare(strict_types=1);

namespace App\A2A;

/**
 * The agent's "brain": plain PHP with no A2A in it, so it is easy to test and
 * easy to replace (call an LLM, a database or an API here instead).
 *
 * Understands requests like "convert 10 km to miles", "72 f in c" or "5 kg".
 */
final class UnitConverter
{
    /**
     * Each unit: [dimension, factor to the base unit, label]. Temperatures
     * are converted separately (they need an offset, not just a factor).
     */
    private const UNITS = [
        // length (base: metre)
        'mm' => ['length', 0.001, 'mm'],
        'cm' => ['length', 0.01, 'cm'],
        'm' => ['length', 1.0, 'm'],
        'km' => ['length', 1000.0, 'km'],
        'in' => ['length', 0.0254, 'in'],
        'ft' => ['length', 0.3048, 'ft'],
        'yd' => ['length', 0.9144, 'yd'],
        'mi' => ['length', 1609.344, 'mi'],
        // mass (base: kilogram)
        'g' => ['mass', 0.001, 'g'],
        'kg' => ['mass', 1.0, 'kg'],
        'oz' => ['mass', 0.028349523125, 'oz'],
        'lb' => ['mass', 0.45359237, 'lb'],
        // volume (base: litre)
        'ml' => ['volume', 0.001, 'ml'],
        'l' => ['volume', 1.0, 'l'],
        'cup' => ['volume', 0.2365882365, 'cups'],
        'gal' => ['volume', 3.785411784, 'gal'],
        // temperature
        'c' => ['temperature', 1.0, '°C'],
        'f' => ['temperature', 1.0, '°F'],
        'k' => ['temperature', 1.0, 'K'],
    ];

    private const ALIASES = [
        'millimeter' => 'mm', 'millimeters' => 'mm', 'millimetre' => 'mm', 'millimetres' => 'mm',
        'centimeter' => 'cm', 'centimeters' => 'cm', 'centimetre' => 'cm', 'centimetres' => 'cm',
        'meter' => 'm', 'meters' => 'm', 'metre' => 'm', 'metres' => 'm',
        'kilometer' => 'km', 'kilometers' => 'km', 'kilometre' => 'km', 'kilometres' => 'km',
        'inch' => 'in', 'inches' => 'in', 'foot' => 'ft', 'feet' => 'ft',
        'yard' => 'yd', 'yards' => 'yd', 'mile' => 'mi', 'miles' => 'mi',
        'gram' => 'g', 'grams' => 'g', 'kilogram' => 'kg', 'kilograms' => 'kg', 'kgs' => 'kg',
        'ounce' => 'oz', 'ounces' => 'oz', 'pound' => 'lb', 'pounds' => 'lb', 'lbs' => 'lb',
        'milliliter' => 'ml', 'milliliters' => 'ml', 'millilitre' => 'ml', 'millilitres' => 'ml',
        'liter' => 'l', 'liters' => 'l', 'litre' => 'l', 'litres' => 'l',
        'cups' => 'cup', 'gallon' => 'gal', 'gallons' => 'gal',
        'celsius' => 'c', '°c' => 'c', 'fahrenheit' => 'f', '°f' => 'f', 'kelvin' => 'k',
    ];

    /**
     * Reads "<amount> <unit> [to|in <unit>]" out of free text. Returns null
     * when there is no amount and unit to work with.
     *
     * @return array{amount: float, from: string, to: ?string}|null
     */
    public function parse(string $text): ?array
    {
        $text = strtolower(trim($text));
        $pattern = '/(-?\d+(?:\.\d+)?)\s*([a-z°]+)(?:\s+(?:to|in|into|as)\s+([a-z°]+))?/u';
        if (preg_match($pattern, $text, $m) !== 1) {
            return null;
        }
        $from = $this->unit($m[2]);
        if ($from === null) {
            return null;
        }

        return ['amount' => (float) $m[1], 'from' => $from, 'to' => isset($m[3]) ? $this->unit($m[3]) : null];
    }

    /**
     * The canonical key of a unit name ("miles" -> "mi"), or null when unknown.
     */
    public function unit(string $name): ?string
    {
        $name = strtolower(trim($name));
        $key = self::ALIASES[$name] ?? $name;

        return isset(self::UNITS[$key]) ? $key : null;
    }

    /**
     * The units $from can be converted to, for "to which unit?" questions.
     *
     * @return list<string>
     */
    public function compatibleUnits(string $from): array
    {
        $dimension = self::UNITS[$from][0];

        return array_values(array_filter(
            array_keys(self::UNITS),
            static fn (string $key): bool => self::UNITS[$key][0] === $dimension && $key !== $from,
        ));
    }

    /**
     * @throws \InvalidArgumentException when the units measure different things
     */
    public function convert(float $amount, string $from, string $to): float
    {
        [$fromDimension, $fromFactor] = self::UNITS[$from];
        [$toDimension, $toFactor] = self::UNITS[$to];
        if ($fromDimension !== $toDimension) {
            throw new \InvalidArgumentException("Can't convert {$fromDimension} to {$toDimension}.");
        }
        if ($fromDimension === 'temperature') {
            return $this->fromKelvin($this->toKelvin($amount, $from), $to);
        }

        return $amount * $fromFactor / $toFactor;
    }

    /**
     * "10 km = 6.2137 mi"
     */
    public function describe(float $amount, string $from, string $to): string
    {
        $result = $this->convert($amount, $from, $to);

        return sprintf('%s %s = %s %s', $this->number($amount), self::UNITS[$from][2], $this->number($result), self::UNITS[$to][2]);
    }

    private function toKelvin(float $value, string $unit): float
    {
        return match ($unit) {
            'c' => $value + 273.15,
            'f' => ($value - 32) * 5 / 9 + 273.15,
            default => $value,
        };
    }

    private function fromKelvin(float $kelvin, string $unit): float
    {
        return match ($unit) {
            'c' => $kelvin - 273.15,
            'f' => ($kelvin - 273.15) * 9 / 5 + 32,
            default => $kelvin,
        };
    }

    private function number(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
