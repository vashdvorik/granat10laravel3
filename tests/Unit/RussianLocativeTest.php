<?php

namespace Tests\Unit;

use App\Support\RussianLocative;
use PHPUnit\Framework\TestCase;

class RussianLocativeTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function pmrSettlements(): array
    {
        return [
            'Тирасполь' => ['Тирасполь', 'Тирасполе'],
            'Новотираспольский' => ['Новотираспольский', 'Новотираспольском'],
            'Бендеры' => ['Бендеры', 'Бендерах'],
            'Гиска' => ['Гиска', 'Гиске'],
            'Протягайловка' => ['Протягайловка', 'Протягайловке'],
            'Слободзея' => ['Слободзея', 'Слободзее'],
            'Днестровск' => ['Днестровск', 'Днестровске'],
            'Первомайск' => ['Первомайск', 'Первомайске'],
            'Красное' => ['Красное', 'Красном'],
            'Парканы' => ['Парканы', 'Парканах'],
            'Суклея' => ['Суклея', 'Суклее'],
            'Кицканы' => ['Кицканы', 'Кицканах'],
            'Терновка' => ['Терновка', 'Терновке'],
            'Ближний Хутор' => ['Ближний Хутор', 'Ближнем Хуторе'],
            'Чобручи' => ['Чобручи', 'Чобручах'],
            'Карагаш' => ['Карагаш', 'Карагаше'],
            'Глиное' => ['Глиное', 'Глином'],
            'Коротное' => ['Коротное', 'Коротном'],
            'Рыбница' => ['Рыбница', 'Рыбнице'],
            'Ержово' => ['Ержово', 'Ержове'],
            'Колбасна' => ['Колбасна', 'Колбасне'],
            'Попенки' => ['Попенки', 'Попенках'],
            'Большой Молокиш' => ['Большой Молокиш', 'Большом Молокише'],
            'Дубоссары' => ['Дубоссары', 'Дубоссарах'],
            'Лунга' => ['Лунга', 'Лунге'],
            'Дзержинское' => ['Дзержинское', 'Дзержинском'],
            'Дойбаны-1' => ['Дойбаны-1', 'Дойбанах-1'],
            'Дойбаны-2' => ['Дойбаны-2', 'Дойбанах-2'],
            'Григориополь' => ['Григориополь', 'Григориополе'],
            'Маяк' => ['Маяк', 'Маяке'],
            'Малаешты' => ['Малаешты', 'Малаештах'],
            'Бутор' => ['Бутор', 'Буторе'],
            'Ташлык' => ['Ташлык', 'Ташлыке'],
            'Спея' => ['Спея', 'Спее'],
            'Делакеу' => ['Делакеу', 'Делакеу'],
            'Каменка' => ['Каменка', 'Каменке'],
            'Подойма' => ['Подойма', 'Подойме'],
            'Подоймица' => ['Подоймица', 'Подоймице'],
            'Рашков' => ['Рашков', 'Рашкове'],
            'Катериновка' => ['Катериновка', 'Катериновке'],
        ];
    }

    /** @dataProvider pmrSettlements */
    public function test_every_settlement_of_pmr_has_a_correct_prepositional_form(string $name, string $expected): void
    {
        $this->assertSame($expected, RussianLocative::prepositional($name));
    }

    public function test_phrase_with_preposition(): void
    {
        $this->assertSame('в Тирасполе', RussianLocative::in('Тирасполь'));
        $this->assertSame('в Бендерах', RussianLocative::in('Бендеры'));
        $this->assertSame('в Рыбнице', RussianLocative::in('Рыбница'));
    }

    public function test_case_and_extra_spaces_do_not_matter(): void
    {
        $this->assertSame('Тирасполе', RussianLocative::prepositional('  ТИРАСПОЛЬ '));
        $this->assertSame('Ближнем Хуторе', RussianLocative::prepositional('ближний   хутор'));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function otherPlaces(): array
    {
        return [
            'masculine soft sign' => ['Ставрополь', 'Ставрополе'],
            'feminine -а' => ['Лиса', 'Лисе'],
            'feminine -я' => ['Земля', 'Земле'],
            '-ия' => ['Одессия', 'Одессии'],
            'neuter -о' => ['Орехово', 'Орехове'],
            'consonant' => ['Вадул', 'Вадуле'],
            'plural -ы' => ['Зубры', 'Зубрах'],
            'plural -ки' => ['Яблонки', 'Яблонках'],
            'plural -и after soft consonant' => ['Бани', 'Банях'],
            'adjective -ая' => ['Новая Каховка', 'Новой Каховке'],
            'adjective -ский' => ['Приднестровский', 'Приднестровском'],
            'indeclinable' => ['Бельцу', 'Бельцу'],
        ];
    }

    /** @dataProvider otherPlaces */
    public function test_unknown_names_follow_the_ending_rules(string $name, string $expected): void
    {
        $this->assertSame($expected, RussianLocative::prepositional($name));
    }

    public function test_empty_name_gives_empty_string(): void
    {
        $this->assertSame('', RussianLocative::prepositional(''));
        $this->assertSame('', RussianLocative::in('   '));
    }

    public function test_vo_is_used_before_v_and_f_with_a_consonant(): void
    {
        $this->assertSame('во Владимировке', RussianLocative::in('Владимировка'));
        $this->assertSame('во Фрунзе', RussianLocative::in('Фрунзе'));
        $this->assertSame('в Вадуле', RussianLocative::in('Вадул'));
    }
}
