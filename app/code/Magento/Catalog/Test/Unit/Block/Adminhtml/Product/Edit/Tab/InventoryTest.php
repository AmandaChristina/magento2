<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Block\Adminhtml\Product\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Inventory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Source\Backorders;
use Magento\CatalogInventory\Model\Source\Stock;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryTest extends TestCase
{
    /**
     * @var Manager|MockObject
     */
    protected $moduleManager;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var Stock|MockObject
     */
    protected $stockMock;

    /**
     * @var Backorders|MockObject
     */
    protected $backordersMock;

    /**
     * @var StockRegistryInterface|MockObject
     */
    protected $stockRegistryMock;

    /**
     * @var StockConfigurationInterface|MockObject
     */
    protected $stockConfigurationMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Inventory
     */
    protected $inventory;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->contextMock = $this->createPartialMock(
            Context::class,
            ['getRequest', 'getStoreManager']
        );
        $this->stockConfigurationMock = $this->getMockForAbstractClass(
            StockConfigurationInterface::class,
            [],
            '',
            false
        );
        $this->stockRegistryMock =  $this->getMockForAbstractClass(
            StockRegistryInterface::class,
            [],
            '',
            false
        );
        $this->backordersMock = $this->createMock(Backorders::class);
        $this->stockMock = $this->createMock(Stock::class);
        $this->coreRegistryMock = $this->createMock(Registry::class);
        $this->moduleManager = $this->createMock(Manager::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );

        $this->contextMock->expects($this->once())
            ->method('getStoreManager')
            ->will($this->returnValue($this->storeManagerMock));

        $this->inventory = $objectManager->getObject(
            Inventory::class,
            [
                'context' => $this->contextMock,
                'backorders' => $this->backordersMock,
                'stock' => $this->stockMock,
                'moduleManager' => $this->moduleManager,
                'coreRegistry' => $this->coreRegistryMock,
                'stockRegistry' => $this->stockRegistryMock,
                'stockConfiguration' => $this->stockConfigurationMock,
            ]
        );
    }

    /**
     * Run test getBackordersOption method
     *
     * @param bool $moduleEnabled
     * @return void
     *
     * @dataProvider dataProviderModuleEnabled
     */
    public function testGetBackordersOption($moduleEnabled)
    {
        $this->moduleManager->expects($this->once())
            ->method('isEnabled')
            ->with('Magento_CatalogInventory')
            ->will($this->returnValue($moduleEnabled));
        if ($moduleEnabled) {
            $this->backordersMock->expects($this->once())
                ->method('toOptionArray')
                ->will($this->returnValue(['test-value', 'test-value']));
        }

        $result = $this->inventory->getBackordersOption();
        $this->assertEquals($moduleEnabled, !empty($result));
    }

    /**
     * Run test getStockOption method
     *
     * @param bool $moduleEnabled
     * @return void
     *
     * @dataProvider dataProviderModuleEnabled
     */
    public function testGetStockOption($moduleEnabled)
    {
        $this->moduleManager->expects($this->once())
            ->method('isEnabled')
            ->with('Magento_CatalogInventory')
            ->will($this->returnValue($moduleEnabled));
        if ($moduleEnabled) {
            $this->stockMock->expects($this->once())
                ->method('toOptionArray')
                ->will($this->returnValue(['test-value', 'test-value']));
        }

        $result = $this->inventory->getStockOption();
        $this->assertEquals($moduleEnabled, !empty($result));
    }

    /**
     * Run test getProduct method
     *
     * @return void
     */
    public function testGetProduct()
    {
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->getProduct();
        $this->assertEquals('return-value', $result);
    }

    /**
     * Run test getStockItem method
     *
     * @return void
     */
    public function testGetStockItem()
    {
        $productId = 10;
        $websiteId = 15;
        $productMock = $this->createPartialMock(Product::class, ['getId', 'getStore']);
        $storeMock = $this->createPartialMock(Store::class, ['getWebsiteId']);
        $productMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($productId));
        $productMock->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($storeMock));
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->will($this->returnValue($websiteId));
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with($productId, $websiteId)
            ->will($this->returnValue('return-value'));

        $resultItem = $this->inventory->getStockItem();
        $this->assertEquals('return-value', $resultItem);
    }

    /**
     * Run test getFieldValue method
     *
     * @param int $stockId
     * @param array $methods
     * @param string $result
     * @return void
     *
     * @dataProvider dataProviderGetFieldValue
     */
    public function testGetFieldValue($stockId, $methods, $result)
    {
        $productId = 10;
        $websiteId = 15;
        $fieldName = 'field';

        $stockItemMock = $this->getMockForAbstractClass(
            StockItemInterface::class,
            [],
            '',
            false,
            false,
            false,
            $methods
        );
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);
        $productMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($productId));
        $productMock->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($storeMock));
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->will($this->returnValue($websiteId));
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with($productId, $websiteId)
            ->will($this->returnValue($stockItemMock));
        $stockItemMock->expects($this->once())
            ->method('getItemId')
            ->will($this->returnValue($stockId));

        if (!empty($methods)) {
            $stockItemMock->expects($this->once())
                ->method(reset($methods))
                ->will($this->returnValue('call-method'));
        }
        if (empty($methods) || empty($stockId)) {
            $this->stockConfigurationMock->expects($this->once())
                ->method('getDefaultConfigValue')
                ->will($this->returnValue('default-result'));
        }

        $resultValue = $this->inventory->getFieldValue($fieldName);
        $this->assertEquals($result, $resultValue);
    }

    /**
     * Run test getConfigFieldValue method
     *
     * @param int $stockId
     * @param array $methods
     * @param string $result
     * @return void
     *
     * @dataProvider dataProviderGetConfigFieldValue
     */
    public function testGetConfigFieldValue($stockId, $methods, $result)
    {
        $productId = 10;
        $websiteId = 15;
        $fieldName = 'field';

        $stockItemMock = $this->getMockForAbstractClass(
            StockItemInterface::class,
            [],
            '',
            false,
            false,
            false,
            $methods
        );
        $productMock = $this->createMock(Product::class);
        $storeMock = $this->createMock(Store::class);
        $productMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($productId));
        $productMock->expects($this->once())
            ->method('getStore')
            ->will($this->returnValue($storeMock));
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->will($this->returnValue($websiteId));
        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with($productId, $websiteId)
            ->will($this->returnValue($stockItemMock));
        $stockItemMock->expects($this->once())
            ->method('getItemId')
            ->will($this->returnValue($stockId));

        if (!empty($methods)) {
            $stockItemMock->expects($this->once())
                ->method(reset($methods))
                ->will($this->returnValue('call-method'));
        }
        if (empty($methods) || empty($stockId)) {
            $this->stockConfigurationMock->expects($this->once())
                ->method('getDefaultConfigValue')
                ->will($this->returnValue('default-result'));
        }

        $resultField = $this->inventory->getConfigFieldValue($fieldName);
        $this->assertEquals($result, $resultField);
    }

    /**
     * Run test getDefaultConfigValue method
     *
     * @return void
     */
    public function testGetDefaultConfigValue()
    {
        $field = 'filed-name';
        $this->stockConfigurationMock->expects($this->once())
            ->method('getDefaultConfigValue')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->getDefaultConfigValue($field);
        $this->assertEquals('return-value', $result);
    }

    /**
     * Run test isReadonly method
     *
     * @return void
     */
    public function testIsReadonly()
    {
        $productMock = $this->createPartialMock(Product::class, ['getInventoryReadonly']);
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));

        $productMock->expects($this->once())
            ->method('getInventoryReadonly')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->isReadonly();
        $this->assertEquals('return-value', $result);
    }

    /**
     * Run test isNew method
     *
     * @param int|null $id
     * @param bool $result
     * @return void
     *
     * @dataProvider dataProviderGetId
     */
    public function testIsNew($id, $result)
    {
        $productMock = $this->createPartialMock(Product::class, ['getId']);
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $productMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $methodResult = $this->inventory->isNew();
        $this->assertEquals($result, $methodResult);
    }

    /**
     * Run test getFieldSuffix method
     *
     * @return void
     */
    public function testGetFieldSuffix()
    {
        $result = $this->inventory->getFieldSuffix();
        $this->assertEquals('product', $result);
    }

    /**
     * Run test canUseQtyDecimals method
     *
     * @return void
     */
    public function testCanUseQtyDecimals()
    {
        $productMock = $this->createPartialMock(Product::class, ['getTypeInstance']);
        $typeMock = $this->getMockForAbstractClass(
            AbstractType::class,
            [],
            '',
            false,
            true,
            true,
            ['canUseQtyDecimals']
        );
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $productMock->expects($this->once())
            ->method('getTypeInstance')
            ->will($this->returnValue($typeMock));
        $typeMock->expects($this->once())
            ->method('canUseQtyDecimals')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->canUseQtyDecimals();
        $this->assertEquals('return-value', $result);
    }

    /**
     * Run test isVirtual method
     *
     * @return void
     */
    public function testIsVirtual()
    {
        $productMock = $this->createPartialMock(Product::class, ['getIsVirtual']);
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('product')
            ->will($this->returnValue($productMock));
        $productMock->expects($this->once())
            ->method('getIsVirtual')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->isVirtual();
        $this->assertEquals('return-value', $result);
    }

    /**
     * Run test isSingleStoreMode method
     *
     * @return void
     */
    public function testIsSingleStoreMode()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('isSingleStoreMode')
            ->will($this->returnValue('return-value'));

        $result = $this->inventory->isSingleStoreMode();
        $this->assertEquals('return-value', $result);
    }

    /**
     * Data for Module Enabled
     *
     * @return array
     */
    public function dataProviderModuleEnabled()
    {
        return [
            [
                'ModuleEnabled' => true,
            ],
            [
                'ModuleEnabled' => false
            ]
        ];
    }

    /**
     * Data for getFieldValue method
     *
     * @return array
     */
    public function dataProviderGetFieldValue()
    {
        return [
            [
                'stockId' => 99,
                'methods' => ['getField'],
                'result' => 'call-method',
            ],
            [
                'stockId' => null,
                'methods' => [],
                'result' => 'default-result'
            ],
            [
                'stockId' => 99,
                'methods' => [],
                'result' => 'default-result'
            ]
        ];
    }

    /**
     * Data for getConfigFieldValue and getFieldValue method
     *
     * @return array
     */
    public function dataProviderGetConfigFieldValue()
    {
        return [
            [
                'stockId' => 99,
                'methods' => ['getUseConfigField'],
                'result' => 'call-method',
            ],
            [
                'stockId' => null,
                'methods' => [],
                'result' => 'default-result'
            ],
            [
                'stockId' => 99,
                'methods' => [],
                'result' => 'default-result'
            ]
        ];
    }

    /**
     * Data for isNew method
     *
     * @return array
     */
    public function dataProviderGetId()
    {
        return [
            [
                'id' => 99,
                'result' => false,
            ],
            [
                'id' => null,
                'result' => true
            ]
        ];
    }
}
