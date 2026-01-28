<?php

namespace Tests\Unit;

use App\Classes\CombatEntity;
use App\Enums\CharacterClass;
use App\Models\Character;
use App\Models\CharacterStats;
use App\Models\ItemInstance;
use App\Models\ItemTemplate;
use App\Services\Combat\PowerCalculatorService;
use PHPUnit\Framework\TestCase;

class PowerCalculatorServiceTest extends TestCase
{
    protected PowerCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PowerCalculatorService();
    }

    public function test_calculate_survivability()
    {
        $entity = $this->createMockEntity();
        $entity->maxHp = 100;
        $entity->defense = 10;

        // Surv = 100 * (1 + 10/100) = 100 * 1.1 = 110
        $this->assertEqualsWithDelta(110, $this->service->calculateSurvivability($entity), 0.001);
    }

    public function test_calculate_offensive()
    {
        $entity = $this->createMockEntity();
        $entity->minDmg = 10;
        $entity->maxDmg = 10;
        $entity->accuracy = 100; // 1.0 factor
        $entity->attackSpeed = 1.0; // Interval 3000ms. SpeedFactor = 2000/3000 = 0.666

        // Crit: Base 5 + (Acc 100 * 0.1)=10 => 15%. 
        // Mult 1.5. 
        // CritFactor = 1 + (15 * 1.5 / 100) = 1 + 0.225 = 1.225

        // Offensive = (10 * 1.0) * 0.6666 * 1.225
        // = 10 * 0.8166 = 8.166

        $expected = 10 * (100 / 100) * (2000 / 3000) * (1 + (15 * 1.5 / 100));

        $this->assertEqualsWithDelta($expected, $this->service->calculateOffensive($entity), 0.001);
    }

    public function test_calculate_cp()
    {
        $entity = $this->createMockEntity();
        $entity->maxHp = 100;  // Surv = 100
        $entity->defense = 0;

        $entity->minDmg = 0; // Off = 0
        $entity->maxDmg = 0;

        // CP = 100 * 0.1 + 0 = 10
        $this->assertEquals(10, $this->service->calculate($entity));
    }

    // Helper to bypass CombatEntity strict constructor without DB
    protected function createMockEntity(): CombatEntity
    {
        // We can create a dummy Character using Mockery or just a stub instance
        // but CombatEntity constructor requires instance of Character/Monster.
        // We cannot easily mock the constructor logic which reads properties.
        // So we create a real Character instance but detached from DB.

        $char = new Character();
        $char->id = 'test';
        $char->name = 'Test';
        $char->setRelation('stats', new CharacterStats([
            'attack_speed' => 1.0,
            'computed_stats' => [
                'max_hp' => 100,
                'damage_min' => 1,
                'damage_max' => 2,
                'dexterity' => 50,
                'defense' => 0
            ]
        ]));

        return new CombatEntity($char);
    }
}
