<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Test\Unit\Design\Fallback\Rule;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\View\Design\Fallback\Rule\RuleInterface;
use \Magento\Framework\View\Design\Fallback\Rule\ModularSwitch;

class ModularSwitchTest extends TestCase
{
    /**
     * @var ModularSwitch
     */
    protected $object;

    /**
     * @var MockObject|RuleInterface
     */
    protected $ruleNonModular;

    /**
     * @var MockObject|RuleInterface
     */
    protected $ruleModular;

    protected function setUp(): void
    {
        $this->ruleNonModular = $this->getMockForAbstractClass(
            RuleInterface::class
        );
        $this->ruleModular = $this->getMockForAbstractClass(
            RuleInterface::class
        );
        $this->object = new ModularSwitch($this->ruleNonModular, $this->ruleModular);
    }

    protected function tearDown(): void
    {
        $this->object = null;
        $this->ruleNonModular = null;
        $this->ruleModular = null;
    }

    public function testGetPatternDirsNonModular()
    {
        $inputParams = ['param_one' => 'value_one', 'param_two' => 'value_two'];
        $expectedResult = new \stdClass();
        $this->ruleNonModular->expects(
            $this->once()
        )->method(
            'getPatternDirs'
        )->with(
            $inputParams
        )->will(
            $this->returnValue($expectedResult)
        );

        $this->ruleModular->expects($this->never())->method('getPatternDirs');

        $this->assertSame($expectedResult, $this->object->getPatternDirs($inputParams));
    }

    public function testGetPatternDirsModular()
    {
        $inputParams = ['param' => 'value', 'module_name' => 'Magento_Core'];
        $expectedResult = new \stdClass();
        $this->ruleNonModular->expects($this->never())->method('getPatternDirs');

        $this->ruleModular->expects(
            $this->once()
        )->method(
            'getPatternDirs'
        )->with(
            $inputParams
        )->will(
            $this->returnValue($expectedResult)
        );

        $this->assertSame($expectedResult, $this->object->getPatternDirs($inputParams));
    }
}
