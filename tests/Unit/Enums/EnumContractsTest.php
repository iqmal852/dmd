<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Contracts\HasColor;
use App\Contracts\HasLabel;
use App\Enums\Direction;
use App\Enums\DocumentType;
use App\Enums\QcStatus;
use App\Enums\StationStatus;
use Tests\TestCase;

class EnumContractsTest extends TestCase
{
    /**
     * @return array<string, array{class-string<\UnitEnum&HasLabel>}>
     */
    public static function labelledEnums(): array
    {
        return [
            StationStatus::class => [StationStatus::class],
            Direction::class => [Direction::class],
            QcStatus::class => [QcStatus::class],
            DocumentType::class => [DocumentType::class],
        ];
    }

    /**
     * @return array<string, array{class-string<\UnitEnum&HasColor>}>
     */
    public static function colouredEnums(): array
    {
        return [
            StationStatus::class => [StationStatus::class],
            QcStatus::class => [QcStatus::class],
        ];
    }

    public function test_every_case_of_every_labelled_enum_has_a_non_empty_label(): void
    {
        foreach (self::labelledEnums() as [$enum]) {
            foreach ($enum::cases() as $case) {
                $this->assertNotSame('', $case->label(), "{$enum}::{$case->name} has an empty label");
            }
        }
    }

    /**
     * The semantic token vocabulary from plan/03-design-system.md §2.2. Phase 02 wires
     * these up as real `--color-*` custom properties in resources/css/app.css; a
     * dedicated Phase 02 test then cross-checks every enum color() against the
     * declared tokens. This phase only guards that no enum invents a token outside
     * the agreed vocabulary.
     */
    private const ALLOWED_COLOR_TOKENS = [
        'primary', 'primary-bright', 'accent', 'info', 'violet',
        'warning', 'danger', 'ink-muted',
    ];

    public function test_every_case_of_every_coloured_enum_names_an_allowed_token(): void
    {
        foreach (self::colouredEnums() as [$enum]) {
            foreach ($enum::cases() as $case) {
                $token = $case->color();
                $this->assertNotSame('', $token, "{$enum}::{$case->name} has an empty color token");
                $this->assertContains(
                    $token,
                    self::ALLOWED_COLOR_TOKENS,
                    "{$enum}::{$case->name} names token '{$token}' which is outside the agreed design-token vocabulary",
                );
            }
        }
    }

    public function test_station_status_pill_colours_match_the_poster(): void
    {
        $this->assertSame('accent', StationStatus::Active->color());
        $this->assertSame('Active', StationStatus::Active->label());
    }

    public function test_qc_status_pill_colours_match_the_poster(): void
    {
        $this->assertSame('accent', QcStatus::Verified->color());
        $this->assertSame('Verified', QcStatus::Verified->label());
    }
}
