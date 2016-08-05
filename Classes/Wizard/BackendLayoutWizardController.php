<?php
namespace GridElementsTeam\Gridelements\Wizard;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for grid wizard
 */
class BackendLayoutWizardController extends \TYPO3\CMS\Backend\Controller\BackendLayoutWizardController
{
    /**
     * @var string
     */
    protected $md5ID = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Initialises the Class
     *
     * @throws \InvalidArgumentException
     */
    public function init()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:lang/locallang_wizards.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // Setting GET vars (used in frameset script):
        $this->P = GeneralUtility::_GP('P');

        //data[layouts][2][config]
        $this->formName = $this->P['formName'];
        $this->fieldName = $this->P['itemName'];
        $hmac_validate = GeneralUtility::hmac($this->formName . $this->fieldName, 'wizard_js');
        if (!$this->P['hmac'] || ($this->P['hmac'] !== $hmac_validate)) {
            throw new \InvalidArgumentException('Hmac Validation failed for backend_layout wizard', 1385811397);
        }
        $this->md5ID = $this->P['md5ID'];
        $uid = (int)$this->P['uid'];

        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadExtJS();
        $pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/grideditor.js');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl',
            BackendUtility::getModuleUrl('help_CshmanualCshmanual', array(
                'tx_cshmanual_help_cshmanualcshmanual' => array(
                    'controller' => 'Help',
                    'action' => 'detail'
                )
            )));
        $pageRenderer->addJsInlineCode('storeData', '
			function storeData(data) {
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . ']) {
					parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . '].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config","data[backend_layout][' . $uid . '][config]");
				}
			}
			', false);
        $languageLabels = array(
            'save' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_labelSave', true),
            'title' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_windowTitle', true),
            'editCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_editCell', true),
            'mergeCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_mergeCell', true),
            'splitCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_splitCell', true),
            'name' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_name', true),
            'column' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_column', true),
            'notSet' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_notSet', true),
            'nameHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_nameHelp', true),
            'columnHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_columnHelp', true),
            'allowedElementTypes' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedElementTypes',
                1),
            'allowedElementTypesHelp' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedElementTypesHelp',
                1),
            'allowedGridElementTypes' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedGridElementTypes',
                1),
            'allowedGridElementTypesHelp' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedGridElementTypesHelp',
                1)
        );
        $pageRenderer->addInlineLanguageLabelArray($languageLabels);
        // add gridelement wizard options information
        $ctypeLabels = array();
        $ctypeIcons = array();
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $item) {
            $itemKey = $item[1];
            if (substr($itemKey, 0, 2) !== '--') {
                $ctypeLabels[$itemKey] = $this->getLanguageService()->sL($item[0], true);
                if (strstr($item[2], '/typo3')) {
                    $ctypeIcons[$itemKey] = '../../../' . $item[2];
                } else {
                    $ctypeIcons[$itemKey] = '../../../' . '../typo3/sysext/t3skin/icons/gfx/' . $item[2];
                }
            }
        }
        $pageRenderer->addInlineLanguageLabelArray($ctypeLabels);
        $pageRenderer->addJsInlineCode('availableCTypes', '
			TYPO3.Backend.availableCTypes = ["' . join('","', array_keys($ctypeLabels)) . '"];
			TYPO3.Backend.availableCTypeIcons = ["' . join('","', $ctypeIcons) . '"];
		');

        // select record
        $record = $this->getDatabaseConnection()->exec_SELECTgetRows($this->P['field'], $this->P['table'],
            'uid=' . (int)$this->P['uid']
        );
        if (trim($record[0][$this->P['field']]) == '') {
            $rows = array(array(array('colspan' => 1, 'rowspan' => 1, 'spanned' => false, 'name' => '')));
            $colCount = 1;
            $rowCount = 1;
        } else {
            // load TS parser
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parser->parse($record[0][$this->P['field']]);
            $data = $parser->setup['backend_layout.'];
            $rows = array();
            $colCount = $data['colCount'];
            $rowCount = $data['rowCount'];
            $dataRows = $data['rows.'];
            $spannedMatrix = array();
            for ($i = 1; $i <= $rowCount; $i++) {
                $cells = array();
                $row = array_shift($dataRows);
                $columns = $row['columns.'];
                for ($j = 1; $j <= $colCount; $j++) {
                    $cellData = array();
                    if (!$spannedMatrix[$i][$j]) {
                        if (is_array($columns) && !empty($columns)) {
                            $column = array_shift($columns);
                            if (isset($column['colspan'])) {
                                $cellData['colspan'] = (int)$column['colspan'];
                                $columnColSpan = (int)$column['colspan'];
                                if (isset($column['rowspan'])) {
                                    $columnRowSpan = (int)$column['rowspan'];
                                    for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                        for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                            $spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
                                        }
                                    }
                                } else {
                                    for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                        $spannedMatrix[$i][$j + $spanColumn] = 1;
                                    }
                                }
                            } else {
                                $cellData['colspan'] = 1;
                                if (isset($column['rowspan'])) {
                                    $columnRowSpan = (int)$column['rowspan'];
                                    for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                        $spannedMatrix[$i + $spanRow][$j] = 1;
                                    }
                                }
                            }
                            if (isset($column['rowspan'])) {
                                $cellData['rowspan'] = (int)$column['rowspan'];
                            } else {
                                $cellData['rowspan'] = 1;
                            }
                            if (isset($column['name'])) {
                                $cellData['name'] = $column['name'];
                            }
                            if (isset($column['colPos'])) {
                                $cellData['column'] = (int)$column['colPos'];
                            }
                            if (isset($column['allowed'])) {
                                $cellData['allowed'] = $column['allowed'];
                            }
                            if (isset($column['allowedGridTypes'])) {
                                $cellData['allowedGridTypes'] = $column['allowedGridTypes'];
                            }
                        }
                    } else {
                        $cellData = array('colspan' => 1, 'rowspan' => 1, 'spanned' => 1);
                    }
                    $cells[] = $cellData;
                }
                $rows[] = $cells;
                if (!empty($spannedMatrix[$i]) && is_array($spannedMatrix[$i])) {
                    ksort($spannedMatrix[$i]);
                }
            }
        }

        $pageRenderer->addExtOnReadyCode('
			t3Grid = new TYPO3.Backend.t3Grid({
				data: ' . json_encode($rows, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . ',
				colCount: ' . (int)$colCount . ',
				rowCount: ' . (int)$rowCount . ',
				targetElement: \'editor\'
			});
			t3Grid.drawTable();
			');

        $this->moduleTemplate->getPageRenderer()->addCssFile(ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/Css/grideditor.css');
        $this->moduleTemplate->getPageRenderer()->addCssFile(ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/Css/grideditor.css');
    }

    /**
     * getter for Icon Factory
     *
     * @return IconFactory iconFactory
     */
    public function getIconFactory()
    {
        return $this->iconFactory;
    }
}
