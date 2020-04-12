<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerFactory;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\Composite;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class CompositeTest extends TestCase
{
    public function testHandle()
    {
        $factoryMock = $this->createMock(
            HandlerFactory::class
        );

        $constructorMock = $this->createMock(
            HandlerInterface::class
        );

        $factoryMock->expects(
            $this->exactly(2)
        )->method(
            'create'
        )->with(
            'handlerInstance'
        )->will(
            $this->returnValue($constructorMock)
        );

        $productMock = $this->createMock(Product::class);

        $constructorMock->expects($this->exactly(2))->method('handle')->with($productMock);

        $model = new Composite($factoryMock, ['handlerInstance', 'handlerInstance']);

        $model->handle($productMock);
    }
}
