<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Model\Product\CopyConstructor;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CopyConstructor\Composite;
use Magento\Catalog\Model\Product\CopyConstructorFactory;
use Magento\Catalog\Model\Product\CopyConstructorInterface;
use PHPUnit\Framework\TestCase;

class CompositeTest extends TestCase
{
    public function testBuild()
    {
        $factoryMock = $this->createMock(CopyConstructorFactory::class);

        $constructorMock = $this->createMock(CopyConstructorInterface::class);

        $factoryMock->expects(
            $this->exactly(2)
        )->method(
            'create'
        )->with(
            'constructorInstance'
        )->will(
            $this->returnValue($constructorMock)
        );

        $productMock = $this->createMock(Product::class);
        $duplicateMock = $this->createMock(Product::class);

        $constructorMock->expects($this->exactly(2))->method('build')->with($productMock, $duplicateMock);

        $model = new Composite(
            $factoryMock,
            ['constructorInstance', 'constructorInstance']
        );

        $model->build($productMock, $duplicateMock);
    }
}
