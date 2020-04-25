<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test of customization path model
 */
namespace Magento\Framework\View\Test\Unit\Design\Theme\Customization;

use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Design\Theme\Customization\Path;
use Magento\Theme\Model\Theme;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\Component\ComponentRegistrar;

class PathTest extends TestCase
{
    /**
     * @var Path
     */
    private $_model;

    /**
     * @var Theme|MockObject
     */
    private $_theme;

    /**
     * @var MockObject
     */
    private $_directory;

    /**
     * @var ComponentRegistrarInterface|MockObject
     */
    private $componentRegistrar;

    protected function setUp(): void
    {
        $this->_theme = $this->getMockForAbstractClass(ThemeInterface::class);
        /** @var Filesystem|MockObject $filesystem */
        $filesystem = $this->createMock(Filesystem::class);
        $this->_directory = $this->createMock(Read::class);
        $filesystem->expects($this->any())->method('getDirectoryRead')->will($this->returnValue($this->_directory));
        $this->_directory->expects($this->any())->method('getAbsolutePath')->will($this->returnArgument(0));
        $this->componentRegistrar = $this->getMockForAbstractClass(
            ComponentRegistrarInterface::class
        );
        $this->_model = new Path(
            $filesystem,
            $this->componentRegistrar
        );
    }

    protected function tearDown(): void
    {
        $this->_theme = null;
        $this->_directory = null;
        $this->_model = null;
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::__construct
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getCustomizationPath
     */
    public function testGetCustomizationPath()
    {
        $expectedPath = implode('/', [Path::DIR_NAME, '123']);
        $this->_theme->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(123));
        $this->assertEquals($expectedPath, $this->_model->getCustomizationPath($this->_theme));
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::__construct
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getCustomizationPath
     */
    public function testGetCustomizationPathNoId()
    {
        $this->_theme->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $this->assertNull($this->_model->getCustomizationPath($this->_theme));
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getThemeFilesPath
     */
    public function testGetThemeFilesPath()
    {
        $this->_theme->expects($this->any())
            ->method('getFullPath')
            ->will($this->returnValue('frontend/Magento/theme'));
        $expectedPath = '/fill/theme/path';
        $this->componentRegistrar->expects($this->once())
            ->method('getPath')
            ->with(ComponentRegistrar::THEME, 'frontend/Magento/theme')
            ->will($this->returnValue($expectedPath));
        $this->assertEquals($expectedPath, $this->_model->getThemeFilesPath($this->_theme));
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getThemeFilesPath
     */
    public function testGetThemeFilesPathNoPath()
    {
        $this->_theme->expects($this->any())
            ->method('getFullPath')
            ->will($this->returnValue(null));
        $this->componentRegistrar->expects($this->never())
            ->method('getPath');
        $this->assertNull($this->_model->getCustomizationPath($this->_theme));
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getCustomViewConfigPath
     */
    public function testGetCustomViewConfigPath()
    {
        $expectedPath = implode(
            '/',
            [
                Path::DIR_NAME,
                '123',
                ConfigInterface::CONFIG_FILE_NAME
            ]
        );
        $this->_theme->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(123));
        $this->assertEquals($expectedPath, $this->_model->getCustomViewConfigPath($this->_theme));
    }

    /**
     * @covers \Magento\Framework\View\Design\Theme\Customization\Path::getCustomViewConfigPath
     */
    public function testGetCustomViewConfigPathNoId()
    {
        $this->_theme->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $this->assertNull($this->_model->getCustomViewConfigPath($this->_theme));
    }
}
