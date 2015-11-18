<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class LayoutSetupTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test make query array post
	 *
	 * @test
	 */
	public function testGetLayoutSetup() {
		$setup = array();
		$setup['1']['uid'] = 12;
		$setup['1']['pid'] = 10;
		$setup['1']['title'] = 'Grid-News';
		$setup['2']['uid'] = 11;
		$setup['2']['pid'] = 10;
		$setup['2']['title'] = 'Grid-Sample';

		$expectedSetup = $setup;
		$layoutSetup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup');
		$layoutSetup->setLayoutSetup($setup);
		$result = $layoutSetup->getLayoutSetup();
		$this->assertEquals($expectedSetup, $result);

		$result = $layoutSetup->getLayoutSetup('5');
		$this->assertEquals($expectedSetup, $result);

		$expectedSetup = $setup['2'];
		$layoutSetup->setLayoutSetup($setup);
		$result = $layoutSetup->getLayoutSetup('2');
		$this->assertEquals($expectedSetup, $result);
	}

	/**
	 * test get typoscript setup
	 *
	 * @test
	 */
	public function testGetTypoScriptSetup() {
		$setup['setup.']['default.'] = 'Hello';
		$setup['setup.']['1.'] = 'world';

		$layoutSetup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\LayoutSetup');
		$layoutSetup->setTypoScriptSetup($setup);
		$result = $layoutSetup->getTypoScriptSetup(NULL);
		$expectedResult['columns.']['default.']['renderObj'] = '<tt_content';
		$this->assertEquals($expectedResult, $result);

		$result = $layoutSetup->getTypoScriptSetup(0);
		$expectedResult = $setup['setup.']['default.'];
		$this->assertEquals($expectedResult, $result);

		$result = $layoutSetup->getTypoScriptSetup(1);
		$expectedResult = $setup['setup.']['1.'];
		$this->assertEquals($expectedResult, $result);

		$result = $layoutSetup->getTypoScriptSetup(2);
		$expectedResult = $setup['setup.']['default.'];
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * test get layout columns
	 *
	 * @test
	 */
	public function testGetLayoutColumns() {
		$setup = array();
		$setup['1']['config']['rows.']['1']['columns.']['1']['colPos'] = 12;
		$setup['1']['config']['rows.']['1']['columns.']['1']['allowed'] = 0;
		$setup['1']['config']['rows.']['2']['columns.']['1']['colPos'] = 13;
		$setup['1']['config']['rows.']['2']['columns.']['1']['allowed'] = 0;
		$setup['1']['config']['rows.']['2']['columns.']['2']['colPos'] = 14;
		$setup['1']['config']['rows.']['2']['columns.']['2']['allowed'] = 1;

		$layoutSetup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\LayoutSetup');
		$layoutSetup->setLayoutSetup($setup);
		$result = $layoutSetup->getLayoutColumns(NULL);
		$expectedResult = array();
		$this->assertEquals($expectedResult, $result);

		$result = $layoutSetup->getLayoutColumns(1);
		$expectedResult = array(
				'CSV' => '-2,-1,12,13,14',
				'12' => '*',
				'allowed' => '*,*,1',
				'13' => '*',
				'14' => '1'
		);
		$this->assertEquals($expectedResult, $result);
	}
}
