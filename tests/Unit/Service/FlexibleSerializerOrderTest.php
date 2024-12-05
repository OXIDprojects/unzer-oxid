<?php

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\Eshop\Core\Field;
use OxidSolutionCatalysts\Unzer\Service\FlexibleSerializer;
use PHPUnit\Framework\TestCase;
use stdClass;

class FlexibleSerializerOrderTest extends IntegrationTestCase
{
    private FlexibleSerializer $flexibleSerializer;

    public function setUp(): void
    {
        $this->flexibleSerializer = new FlexibleSerializer();
    }

    private function createSerializedData(mixed $data): string
    {
        $testData = new stdClass();
        foreach ($data as $key => $value) {
            $testData->$key = $value;
        }
        return serialize($testData);
    }

    public function testRestoreOrderFromStdClass(): void
    {
        // Prepare test data
        $testData = $this->createSerializedData([
            'oxid' => 'testOrderId',
            'oxordernr' => '12345',
            'oxtotalordersum' => '99.99',
            'oxorderdate' => '2024-01-01 12:00:00',
            'oxbillfname' => 'John',
            'oxbilllname' => 'Doe'
        ]);

        // Execute method
        $result = $this->flexibleSerializer->restoreOrderFromStrClass($testData);

        // Verify result is Order instance with finalizeTmpOrder method
        $this->assertInstanceOf(Order::class, $result);
        $this->assertTrue(method_exists($result, 'finalizeTmpOrder'), 'Result should have finalizeTmpOrder method');

        // Verify fields were set correctly using getFieldData
        $this->assertEquals('testOrderId', $result->getFieldData('oxid'));
        $this->assertEquals('12345', $result->getFieldData('oxordernr'));
        $this->assertEquals('99.99', $result->getFieldData('oxtotalordersum'));
        $this->assertEquals('2024-01-01 12:00:00', $result->getFieldData('oxorderdate'));
        $this->assertEquals('John', $result->getFieldData('oxbillfname'));
        $this->assertEquals('Doe', $result->getFieldData('oxbilllname'));
    }

    public function testRestoreOrderFromOrderClass(): void
    {
        $order = oxNew(Order::class);
        $order = [
            'oxid' => 'testOrderId',
            'oxordernr' => '12345',
            'oxtotalordersum' => '99.99',
            'oxorderdate' => '2024-01-01 12:00:00',
            'oxbillfname' => 'John',
            'oxbilllname' => 'Doe'
        ];

        $testData = $this->createSerializedData($order);

        $flexResult = $this->flexibleSerializer->safeSerialize($order);
        $flexUnserialized = (array)$this->flexibleSerializer->safeUnserialize($flexResult);

        $this->assertEquals('testOrderId', $flexUnserialized['oxid']);
        $this->assertEquals('12345', $flexUnserialized['oxordernr']);
        $this->assertEquals('99.99', $flexUnserialized['oxtotalordersum']);

        $result = $this->flexibleSerializer->restoreOrderFromStrClass($testData);

        // Verify result is Order instance with finalizeTmpOrder method
        $this->assertInstanceOf(Order::class, $result);
        $this->assertTrue(method_exists($result, 'finalizeTmpOrder'), 'Result should have finalizeTmpOrder method');

        // Verify fields were set correctly using getFieldData
        $this->assertEquals('testOrderId', $result->getFieldData('oxid'));
        $this->assertEquals('12345', $result->getFieldData('oxordernr'));
        $this->assertEquals('99.99', $result->getFieldData('oxtotalordersum'));
        $this->assertEquals('2024-01-01 12:00:00', $result->getFieldData('oxorderdate'));
        $this->assertEquals('John', $result->getFieldData('oxbillfname'));
        $this->assertEquals('Doe', $result->getFieldData('oxbilllname'));
    }

    public function testRestoreOrderFromStrClassWithEmptyData(): void
    {
        $testData = $this->createSerializedData([]);

        $result = $this->flexibleSerializer->restoreOrderFromStrClass($testData);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertNull($result->getFieldData('oxid'));
        $this->assertNull($result->getFieldData('oxordernr'));
    }

    public function testRestoreOrderFromStrClassWithNullValues(): void
    {
        $testData = $this->createSerializedData([
            'oxid' => null,
            'oxordernr' => null,
            'oxtotalordersum' => null
        ]);

        $result = $this->flexibleSerializer->restoreOrderFromStrClass($testData);

        $this->assertNull($result->getFieldData('oxid'));
        $this->assertNull($result->getFieldData('oxordernr'));
        $this->assertNull($result->getFieldData('oxtotalordersum'));
    }

    public function testRestoreOrderFromStrClassWithMixedTypes(): void
    {
        $testData = $this->createSerializedData([
            'oxid' => 'testOrderId',
            'oxtotalordersum' => 99.99,
            'oxordernr' => 12345,
            'oxdelcost' => '10.00',
            'oxpaid' => true
        ]);

        $result = $this->flexibleSerializer->restoreOrderFromStrClass($testData);

        $this->assertEquals('testOrderId', $result->getFieldData('oxid'));
        $this->assertEquals(99.99, $result->getFieldData('oxtotalordersum'));
        $this->assertEquals(12345, $result->getFieldData('oxordernr'));
        $this->assertEquals('10.00', $result->getFieldData('oxdelcost'));
        $this->assertEquals(true, $result->getFieldData('oxpaid'));
    }
}
