<?php

namespace Tests\Unit;

use App\Services\ModerationSettingsFormMapper;
use PHPUnit\Framework\TestCase;

class ModerationSettingsFormMapperTest extends TestCase
{
    private ModerationSettingsFormMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new ModerationSettingsFormMapper();
    }

    /** @return array<string, array{0: bool, 1: string, 2: string}> */
    public static function strategies(): array
    {
        return [
            'off, shadow' => [false, 'shadow', 'manual'],
            'off, auto' => [false, 'auto', 'manual'],
            'on, shadow' => [true, 'shadow', 'assist'],
            'on, auto' => [true, 'auto', 'auto'],
            'on, unknown mode' => [true, 'whatever', 'assist'],
        ];
    }

    /** @dataProvider strategies */
    public function test_storage_flags_become_one_plain_choice(bool $enabled, string $mode, string $expected): void
    {
        $form = $this->mapper->toForm(['ai_enabled' => $enabled, 'mode' => $mode, 'max_risk' => 10.0, 'excluded_categories' => '']);

        $this->assertSame($expected, $form['strategy']);
    }

    /** @return array<string, array{0: float, 1: string}> */
    public static function risks(): array
    {
        return [
            'strict' => [5.0, 'strict'],
            'normal' => [10.0, 'normal'],
            'soft' => [25.0, 'soft'],
            'rounded value' => [10.004, 'normal'],
            'custom' => [7.0, 'custom'],
            'zero' => [0.0, 'custom'],
        ];
    }

    /** @dataProvider risks */
    public function test_risk_threshold_is_matched_to_a_preset_or_kept_custom(float $risk, string $expected): void
    {
        $this->assertSame($expected, $this->mapper->strictnessFor($risk));
    }

    public function test_excluded_categories_become_a_clean_list(): void
    {
        $form = $this->mapper->toForm(['excluded_categories' => ' weapons, medicine ,,weapons,  ']);

        $this->assertSame(['weapons', 'medicine'], $form['excluded_categories']);
        $this->assertSame([], $this->mapper->toForm(['excluded_categories' => ''])['excluded_categories']);
    }

    public function test_choices_are_written_back_as_storage_values(): void
    {
        $assist = $this->mapper->toStorage(['strategy' => 'assist', 'strictness' => 'soft', 'excluded_categories' => ['a', 'b']]);

        $this->assertTrue($assist['ai_enabled']);
        $this->assertSame('shadow', $assist['mode']);
        $this->assertSame(25.0, $assist['max_risk']);
        $this->assertSame('a,b', $assist['excluded_categories']);
        $this->assertArrayNotHasKey('strategy', $assist);
        $this->assertArrayNotHasKey('strictness', $assist);

        $auto = $this->mapper->toStorage(['strategy' => 'auto', 'strictness' => 'strict']);

        $this->assertTrue($auto['ai_enabled']);
        $this->assertSame('auto', $auto['mode']);
        $this->assertSame(5.0, $auto['max_risk']);
    }

    public function test_manual_choice_keeps_the_saved_mode(): void
    {
        $manual = $this->mapper->toStorage(['strategy' => 'manual'], ['mode' => 'auto']);

        $this->assertFalse($manual['ai_enabled']);
        $this->assertSame('auto', $manual['mode']);

        $this->assertSame('shadow', $this->mapper->toStorage(['strategy' => 'manual'], ['mode' => 'broken'])['mode']);
    }

    public function test_custom_risk_is_clamped_to_a_valid_percentage(): void
    {
        $this->assertSame(7.0, $this->mapper->toStorage(['strategy' => 'auto', 'strictness' => 'custom', 'max_risk' => '7'])['max_risk']);
        $this->assertSame(100.0, $this->mapper->toStorage(['strategy' => 'auto', 'strictness' => 'custom', 'max_risk' => 900])['max_risk']);
        $this->assertSame(0.0, $this->mapper->toStorage(['strategy' => 'auto', 'strictness' => 'custom', 'max_risk' => -5])['max_risk']);
        $this->assertSame(10.0, $this->mapper->toStorage(['strategy' => 'auto', 'strictness' => 'custom', 'max_risk' => 'abc'])['max_risk']);
    }

    public function test_values_missing_from_the_form_fall_back_to_saved_ones(): void
    {
        $result = $this->mapper->toStorage(
            ['strategy' => 'assist'],
            ['ai_enabled' => true, 'mode' => 'auto', 'max_risk' => 25.0, 'excluded_categories' => 'weapons,medicine'],
        );

        $this->assertSame(25.0, $result['max_risk']);
        $this->assertSame('weapons,medicine', $result['excluded_categories']);
    }

    public function test_commas_inside_an_alias_cannot_smuggle_extra_categories(): void
    {
        $this->assertSame(['ab', 'c'], $this->mapper->aliases(['a,b', 'c']));
    }
}
