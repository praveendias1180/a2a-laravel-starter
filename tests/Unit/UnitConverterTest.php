<?php

namespace Tests\Unit;

use App\A2A\UnitConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function conversions(): iterable
    {
        yield 'length' => ['convert 10 km to miles', '10 km = 6.2137 mi'];
        yield 'mass' => ['5 lb in kg', '5 lb = 2.268 kg'];
        yield 'volume' => ['2 cups to ml', '2 cups = 473.1765 ml'];
        yield 'temperature' => ['72 f in c', '72 °F = 22.2222 °C'];
        yield 'freezing' => ['0 celsius to kelvin', '0 °C = 273.15 K'];
        yield 'negative' => ['-40 c to f', '-40 °C = -40 °F'];
        yield 'aliases' => ['3 Feet into Inches', '3 ft = 36 in'];
    }

    #[DataProvider('conversions')]
    public function test_it_converts(string $text, string $expected): void
    {
        $converter = new UnitConverter;
        $request = $converter->parse($text);

        $this->assertNotNull($request);
        $this->assertNotNull($request['to']);
        $this->assertSame($expected, $converter->describe($request['amount'], $request['from'], $request['to']));
    }

    public function test_a_missing_target_unit_is_null(): void
    {
        $this->assertSame(['amount' => 10.0, 'from' => 'km', 'to' => null], (new UnitConverter)->parse('convert 10 km'));
    }

    public function test_text_without_an_amount_and_unit_is_not_a_request(): void
    {
        $this->assertNull((new UnitConverter)->parse('hello there'));
        $this->assertNull((new UnitConverter)->parse('10 parsecs to km'));
    }

    public function test_it_refuses_to_mix_dimensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new UnitConverter)->convert(1, 'kg', 'km');
    }
}
