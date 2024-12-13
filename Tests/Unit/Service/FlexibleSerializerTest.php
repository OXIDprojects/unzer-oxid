<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidSolutionCatalysts\Unzer\Service\FlexibleSerializer;
use PHPUnit\Framework\TestCase;
use OxidEsales\Eshop\Application\Model\Order;

class FlexibleSerializerTest extends TestCase
{
    private FlexibleSerializer $flexibleSerializer;

    protected function setUp(): void
    {
        $this->flexibleSerializer = new FlexibleSerializer();
    }

    public function testSafeUnserializeWithAllowedClasses(): void
    {
        $order = new \OxidEsales\Eshop\Application\Model\Order();
        $order->id = 1;
        $order->customerName = 'John Doe';

        $serialized = $this->flexibleSerializer->safeSerialize($order);
        $unserialized = $this->flexibleSerializer->safeUnserialize(
            $serialized
        );

        $this->assertInstanceOf(\stdClass::class, $unserialized);
        $this->assertEquals(1, $unserialized->id);
        $this->assertEquals('John Doe', $unserialized->customerName);
    }

    public function testSafeSerializeAndUnserializeCustomObject(): void
    {
        $extendedOrder = new Order();
        $extendedOrder->id = 1;
        $extendedOrder->customerName = 'John Doe';
        $extendedOrder->extraField = 'Extra Info';

        $serialized = $this->flexibleSerializer->safeSerialize($extendedOrder);
        $unserialized = $this->flexibleSerializer->safeUnserialize(
            $serialized,
            ['OxidEsales\Eshop\Application\Model\Order']
        );

        $this->assertEquals(1, $unserialized->id);
        $this->assertEquals('John Doe', $unserialized->customerName);
        $this->assertEquals('Extra Info', $unserialized->extraField);
    }

    public function testSafeSerializeAndUnserializeSimpleObject(): void
    {
        $testObject = new \stdClass();
        $testObject->name = 'Test';
        $testObject->value = 42;

        $serialized = $this->flexibleSerializer->safeSerialize($testObject);
        $unserialized = $this->flexibleSerializer->safeUnserialize($serialized);

        $this->assertInstanceOf(\stdClass::class, $unserialized);
        $this->assertEquals('Test', $unserialized->name);
        $this->assertEquals(42, $unserialized->value);
    }

    public function testSafeSerializeAndUnserializeNestedObject(): void
    {
        $testObject = new \stdClass();
        $testObject->name = 'Test';
        $testObject->nested = new \stdClass();
        $testObject->nested->value = 42;

        $serialized = $this->flexibleSerializer->safeSerialize($testObject);
        $unserialized = $this->flexibleSerializer->safeUnserialize($serialized);

        $this->assertInstanceOf(\stdClass::class, $unserialized);
        $this->assertEquals('Test', $unserialized->name);
        $this->assertInstanceOf(\stdClass::class, $unserialized->nested);
        $this->assertEquals(42, $unserialized->nested->value);
    }

    public function testSafeSerializeAndUnserializeWithNonSerializableProperty(): void
    {
        $testObject = new class {
            public string $name = 'Test';
            public $resource;

            public function __construct()
            {
                $this->resource = fopen('php://memory', 'r');
            }
        };

        $serialized = $this->flexibleSerializer->safeSerialize($testObject);
        $unserialized = $this->flexibleSerializer->safeUnserialize($serialized);

        $this->assertEquals('Test', $unserialized->name);
        $this->assertNull($unserialized->resource);
    }

    public function testSafeUnserializeWithoutAllowedClasses(): void
    {
        $order = new \OxidEsales\Eshop\Application\Model\Order();
        $order->id = 1;
        $order->customerName = 'John Doe';

        $serialized = $this->flexibleSerializer->safeSerialize($order);
        $unserialized = $this->flexibleSerializer->safeUnserialize($serialized);

        $this->assertInstanceOf(\stdClass::class, $unserialized);
        $this->assertEquals(1, $unserialized->id);
        $this->assertEquals('John Doe', $unserialized->customerName);
    }

    public function testSafeSerializeAndUnserializeExtendedObject(): void
    {
        $extendedOrder = new Order();
        $extendedOrder->id = 1;
        $extendedOrder->customerName = 'John Doe';
        $extendedOrder->extraField = 'Extra Info';

        $serialized = $this->flexibleSerializer->safeSerialize($extendedOrder);
        $unserialized = $this->flexibleSerializer->safeUnserialize(
            $serialized,
            [\OxidEsales\Eshop\Application\Model\Order::class]
        );

        $this->assertEquals(1, $unserialized->id);
        $this->assertEquals('John Doe', $unserialized->customerName);
        $this->assertEquals('Extra Info', $unserialized->extraField);
    }
}
