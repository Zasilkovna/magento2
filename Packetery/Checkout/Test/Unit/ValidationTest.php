<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test\Unit;

use Packetery\Checkout\Model\Carrier\PacketeryConfig;
use Packetery\Checkout\Model\ResourceModel\PricingruleRepository;

class ValidationTest extends \Packetery\Checkout\Test\BaseTest
{
    public function testMaxWeight()
    {
        $packeteryConfig = $this->createMock(PacketeryConfig::class);
        $packeteryConfig->method('getMaxWeight')->willReturn(20.0);

        /** @var PricingruleRepository $repo */
        $repo = $this->createProxy(PricingruleRepository::class, [
            'packeteryConfig' => $packeteryConfig
        ]);

        $this->assertTrue(is_numeric(0));
        $this->assertTrue(is_numeric('0'));
        $this->assertTrue(is_numeric('-0'));
        $this->assertTrue(is_numeric('0.1'));
        $this->assertTrue(is_numeric(10.55));
        $this->assertTrue(is_numeric(-10.55));
        $this->assertFalse(is_numeric(null));
        $this->assertFalse(is_numeric(false));
        $this->assertFalse(is_numeric(''));

        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 10.2], ['max_weight' => 10.8]])); // 0 does not mean null
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 1], ['max_weight' => 3]]));
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 1.5], ['max_weight' => 3.5]]));
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 1], ['max_weight' => 3.7]]));
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 1], ['max_weight' => 0]]));
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 0], ['max_weight' => 0]])); // duplicate weights
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 2], ['max_weight' => 2.00]])); // duplicate weights
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 5.5555], ['max_weight' => 4]]));
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 0.0000000000009], ['max_weight' => 0.0000000000009]])); // notice more zeros. DB saves just 4 decimals. duplicate weight not allowed
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => 5.5], ['max_weight' => null]]));
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => null], ['max_weight' => 20]]));
        $this->assertTrue($repo->validatePricingRuleMaxWeight([['max_weight' => null], ['max_weight' => 10]]));
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 1], ['max_weight' => 20.1]])); // 20.1 is bigger than 20
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => null], ['max_weight' => 20.00001]]));
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => null], ['max_weight' => 21]]));
        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 10], ['max_weight' => 21]]));

        $packeteryConfig = $this->createMock(PacketeryConfig::class);
        $packeteryConfig->method('getMaxWeight')->willReturn(null);

        /** @var PricingruleRepository $repo */
        $repo = $this->createProxy(PricingruleRepository::class, [
            'packeteryConfig' => $packeteryConfig
        ]);

        $this->assertFalse($repo->validatePricingRuleMaxWeight([['max_weight' => 1], ['max_weight' => 1]])); // global max weight is required
    }
}
