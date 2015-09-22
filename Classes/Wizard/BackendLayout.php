<?php
namespace GridElementsTeam\Gridelements\Wizard;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for grid wizard
 * @author T3UXW09 Team1 <modernbe@cybercraft.de>
 */
class BackendLayout {

	/**
	 * Wizard parameters, coming from TCEforms linking to the wizard.
	 */
	public $P;

	/**
	 * Document template object
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Accumulated content.
	 */
	public $content;

	/**
	 * @var string
	 */
	public $formName;

	/**
	 * @var string
	 */
	public $fieldName;

	/**
	 * @var string
	 */
	protected $md5ID = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_wizards.xml');
		$this->init();
	}

	/**
	 * Initialises the Class
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function init() {
		$lang = $this->getLanguageService();
		$lang->includeLLFile('EXT:lang/locallang_wizards.xlf');

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
		// Initialize document object:
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');

		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->loadExtJS();
		$pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/grideditor.js');
		$pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', BackendUtility::getModuleUrl('help_cshmanual'));
		$pageRenderer->addJsInlineCode('storeData', '
			function storeData(data) {
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . ']) {
					parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . '].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config","data[backend_layout][' . $uid . '][config]");
				}
			}
			', false);
		$languageLabels = array('save' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_labelSave', true), 'title' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_windowTitle', true), 'editCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_editCell', true), 'mergeCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_mergeCell', true), 'splitCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_splitCell', true), 'name' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_name', true), 'column' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_column', true), 'notSet' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_notSet', true), 'nameHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_nameHelp', true), 'columnHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xml:grid_columnHelp', 1), 'allowedElementTypes' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedElementTypes', 1), 'allowedElementTypesHelp' => $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:allowedElementTypesHelp', 1),);
		$pageRenderer->addInlineLanguageLabelArray($languageLabels);
		// add gridelement wizard options information
		$ctypeLabels = array();
		$ctypeIcons = array();
		foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $item) {
			$itemKey = $item[1];
			if (substr($itemKey, 0, 2) !== '--') {
				$ctypeLabels[$itemKey] = $this->getLanguageService()->sL($item[0], TRUE);
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
		$record = $this->getDatabaseConnection()->exec_SELECTgetRows($this->P['field'], $this->P['table'], 'uid=' . (int)$this->P['uid']);
		if (trim($record[0][$this->P['field']]) === '') {
			$rows = array(array(array('colspan' => 1, 'rowspan' => 1, 'spanned' => false, 'name' => '', 'allowed' => '')));
			$colCount = 1;
			$rowCount = 1;
		} else {
			// load TS parser
			$parser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
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
						if (is_array($columns) && count($columns)) {
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
						}
					} else {
						$cellData = array('colspan' => 1, 'rowspan' => 1, 'spanned' => 1, 'allowed' => '*');
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
		$this->doc->styleSheetFile_post = ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/Css/grideditor.css';
	}

	/**
	 * Main Method, rendering either colorpicker or frameset depending on ->showPicker
	 * @return void
	 */
	public function main() {
		$lang = $this->getLanguageService();
		$resourcePath = ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/Images/BackendLayoutWizard/';
		$content = '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', true) . '" onclick="storeData(t3Grid.export2LayoutRecord());return true;">' . IconUtility::getSpriteIcon('actions-document-save') . '</a>';
		$content .= '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', true) . '" onclick="storeData(t3Grid.export2LayoutRecord());window.close();return true;">' . IconUtility::getSpriteIcon('actions-document-save-close') . '</a>';
		$content .= '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', true) . '" onclick="window.close();return true;">' . IconUtility::getSpriteIcon('actions-document-close') . '</a>';
		$content .= $this->doc->spacer(10);
		$content .= '
		<table border="0" width="90%" height="90%" id="outer_container">
			<tr>
				<td class="editor_cell">
					<div id="editor">
					</div>
				</td>
				<td width="20" valign="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addColumn') . '" onclick="t3Grid.addColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableright.png" border="0" />
					</a><br />
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeColumn') . '" onclick="t3Grid.removeColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableleft.png" border="0" />
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="2" height="20" align="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addRow') . '" onclick="t3Grid.addRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tabledown.png" border="0" />
					</a>
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeRow') . '" onclick="t3Grid.removeRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableup.png" border="0" />
					</a>
				</td>
			</tr>
		</table>
		';
		$this->content = $content;

		echo $this->doc->render('Grid wizard', $this->content);
	}

	/**
	 * Returns LanguageService
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the database connection
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
