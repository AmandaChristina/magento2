<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Plugin\Model\Product\Action;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Plugin\Model\Product\Action\UpdateAttributesFlushCache;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateAttributesFlushCacheTest extends TestCase
{
    /**
     * @var UpdateAttributesFlushCache
     */
    private $model;

    protected function setUp(): void
    {
        $cacheContextMock = $this->createMock(CacheContext::class);

        $eventManagerMock = $this->createMock(ManagerInterface::class);
        $eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('clean_cache_by_tags', ['object' => $cacheContextMock]);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            UpdateAttributesFlushCache::class,
            [
                'cacheContext' => $cacheContextMock,
                'eventManager' => $eventManagerMock,
            ]
        );
    }

    public function testAroundUpdateAttributes()
    {
        /** @var Action $productActionMock */
        $productActionMock = $this->createMock(Action::class);
        $this->model->afterUpdateAttributes($productActionMock, $productActionMock);
    }

    public function testAroundUpdateWebsites()
    {
        /** @var Action $productActionMock */
        $productActionMock = $this->createMock(Action::class);
        $this->model->afterUpdateWebsites($productActionMock, $productActionMock);
    }
}
