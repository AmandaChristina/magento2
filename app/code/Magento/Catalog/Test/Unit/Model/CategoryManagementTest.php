<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\Tree;
use Magento\Catalog\Model\CategoryManagement;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryManagementTest extends TestCase
{
    /**
     * @var CategoryManagement
     */
    protected $model;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var Tree|MockObject
     */
    protected $categoryTreeMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $categoriesFactoryMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var ScopeResolverInterface|MockObject
     */
    protected $scopeResolverMock;

    /**
     * @var ScopeInterface|MockObject
     */
    protected $scopeMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->categoryRepositoryMock = $this->createMock(CategoryRepositoryInterface::class);
        $this->categoryTreeMock = $this->createMock(Tree::class);
        $this->categoriesFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create', 'addFilter', 'getFirstItem']
        );

        $this->model = $this->objectManagerHelper->getObject(
            CategoryManagement::class,
            [
                'categoryRepository' => $this->categoryRepositoryMock,
                'categoryTree' => $this->categoryTreeMock,
                'categoriesFactory' => $this->categoriesFactoryMock
            ]
        );

        $this->scopeResolverMock = $this->createMock(ScopeResolverInterface::class);

        $this->scopeMock = $this->createMock(ScopeInterface::class);

        $this->objectManagerHelper->setBackwardCompatibleProperty(
            $this->model,
            'scopeResolver',
            $this->scopeResolverMock
        );
    }

    public function testGetTree()
    {
        $rootCategoryId = 1;
        $depth = 2;
        $categoryMock = $this->createMock(Category::class);
        $nodeMock = $this->createMock(Node::class);

        $this->categoryRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($rootCategoryId)
            ->willReturn($categoryMock);
        $this->categoryTreeMock
            ->expects($this->once())
            ->method('getRootNode')
            ->with($categoryMock)
            ->willReturn($nodeMock);
        $this->categoryTreeMock
            ->expects($this->once())
            ->method('getTree')
            ->with($nodeMock, $depth)
            ->willReturn('expected');
        $this->assertEquals(
            'expected',
            $this->model->getTree($rootCategoryId, $depth)
        );
    }

    public function testGetTreeWithNullArguments()
    {
        $rootCategoryId = null;
        $depth = null;
        $category = null;

        $this->categoryRepositoryMock->expects($this->never())->method('get');
        $this->categoryTreeMock->expects($this->once())->method('getRootNode')->with($category)->willReturn(null);
        $this->categoryTreeMock->expects($this->exactly(2))->method('getTree')->with($category, $depth);

        $this->scopeResolverMock
            ->expects($this->once())
            ->method('getScope')
            ->willReturn($this->scopeMock);

        $this->scopeMock
            ->expects($this->once())
            ->method('getCode')
            ->willReturn(1);

        $this->assertEquals(
            $this->model->getTree($rootCategoryId, $depth),
            $this->categoryTreeMock->getTree(null, null)
        );
    }

    /**
     * Check is possible to get all categories for all store starting from top level root category
     */
    public function testGetTreeForAllScope()
    {
        $depth = null;
        $categoriesMock = $this->createMock(Collection::class);
        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMockClassName('categoryMock')
            ->disableOriginalConstructor()
            ->getMock();
        $categoriesMock
            ->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($categoryMock);
        $categoriesMock
            ->expects($this->once())
            ->method('addFilter')
            ->with('level', ['eq' => 0])
            ->willReturnSelf();
        $this->categoriesFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($categoriesMock);
        $nodeMock = $this->createMock(Node::class);

        $this->categoryTreeMock
            ->expects($this->once())
            ->method('getTree')
            ->with($nodeMock, $depth);
        $this->categoryRepositoryMock
            ->expects($this->never())
            ->method('get');
        $this->categoryTreeMock
            ->expects($this->once())
            ->method('getRootNode')
            ->with($categoryMock)
            ->willReturn($nodeMock);

        $this->scopeResolverMock
            ->expects($this->once())
            ->method('getScope')
            ->willReturn($this->scopeMock);

        $this->scopeMock
            ->expects($this->once())
            ->method('getCode')
            ->willReturn(Store::ADMIN_CODE);

        $this->model->getTree();
    }

    public function testMove()
    {
        $categoryId = 2;
        $parentId = 1;
        $afterId = null;
        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMockClassName('categoryMock')
            ->disableOriginalConstructor()
            ->getMock();
        $parentCategoryMock = $this->getMockBuilder(Category::class)
            ->setMockClassName('parentCategoryMock')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepositoryMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [$categoryId, null, $categoryMock],
                    [$parentId, null, $parentCategoryMock],
                ]
            ));
        $parentCategoryMock->expects($this->once())->method('hasChildren')->willReturn(true);
        $parentCategoryMock->expects($this->once())->method('getChildren')->willReturn('5,6,7');
        $categoryMock->expects($this->once())->method('getPath');
        $parentCategoryMock->expects($this->once())->method('getPath');
        $categoryMock->expects($this->once())->method('move')->with($parentId, '7');
        $this->assertTrue($this->model->move($categoryId, $parentId, $afterId));
    }

    public function testMoveWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Operation do not allow to move a parent category to any of children category');
        $categoryId = 2;
        $parentId = 1;
        $afterId = null;
        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMockClassName('categoryMock')
            ->disableOriginalConstructor()
            ->getMock();
        $parentCategoryMock = $this->getMockBuilder(Category::class)
            ->setMockClassName('parentCategoryMock')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepositoryMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [$categoryId, null, $categoryMock],
                    [$parentId, null, $parentCategoryMock],
                ]
            ));
        $categoryMock->expects($this->once())->method('getPath')->willReturn('test');
        $parentCategoryMock->expects($this->once())->method('getPath')->willReturn('test');
        $this->model->move($categoryId, $parentId, $afterId);
    }

    public function testMoveWithCouldNotMoveException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Could not move category');
        $categoryId = 2;
        $parentId = 1;
        $afterId = null;
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMockClassName('categoryMock')
            ->getMock();
        $parentCategoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMockClassName('parentCategoryMock')
            ->getMock();

        $this->categoryRepositoryMock
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [$categoryId, null, $categoryMock],
                    [$parentId, null, $parentCategoryMock],
                ]
            ));
        $categoryMock->expects($this->once())
            ->method('move')
            ->with($parentId, $afterId)
            ->willThrowException(new LocalizedException(__('message')));
        $this->model->move($categoryId, $parentId, $afterId);
    }

    public function testGetCount()
    {
        $categoriesMock = $this->createMock(Collection::class);

        $this->categoriesFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($categoriesMock);
        $categoriesMock
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->with('parent_id', ['gt' => 0])
            ->willReturnSelf();
        $categoriesMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn('expected');

        $this->assertEquals(
            'expected',
            $this->model->getCount()
        );
    }
}
