<?php



$tempColumns = array(
	'tx_gridelements_backend_layout' => array(
		'exclude' => 1,
		'label'   => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_backend_layout',
		'config'  => array(
			'type'          => 'select',
			'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->layoutItemsProcFunc',
			'size'          => 1,
			'selicon_cols'  => 9,
			'maxitems'      => 1,
			'default'       => '',
		)
	),
	'tx_gridelements_children'       => array(
		'exclude' => 1,
		'label'   => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_children',
		'config'  => array(
			'type'           => 'inline',
			'appearance'     => array(
				'levelLinksPosition'              => 'top',
				'showPossibleLocalizationRecords' => TRUE,
				'showRemovedLocalizationRecords'  => TRUE,
				'showAllLocalizationLink'         => TRUE,
				'showSynchronizationLink'         => TRUE,
				'enabledControls'                 => array(
					'info'     => TRUE,
					'new'      => FALSE,
					'dragdrop' => FALSE,
					'sort'     => FALSE,
					'hide'     => TRUE,
					'delete'   => TRUE,
					'localize' => TRUE,
				)
			),
			'inline'         => array(
				'inlineNewButtonStyle' => 'display: inline-block;',
			),
			'behaviour'      => array(
				'localizationMode'                     => 'select',
				'localizeChildrenAtParentLocalization' => TRUE,
			),
			'foreign_table'  => 'tt_content',
			'foreign_field'  => 'tx_gridelements_container',
			'foreign_record_defaults' => array(
				'colPos' => -1,
			),
			'foreign_sortby' => 'sorting',
			'size'           => 5,
			'autoSizeMax'    => 20,
		)
	),
	'tx_gridelements_container'      => array(
		'exclude' => 1,
		'label'   => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_container',
		'config'  => array(
			'type'                => 'select',
			'items'               => array(
				array(
					'',
					0
				),
			),
			'foreign_table'       => 'tt_content',
			'foreign_table_where' => 'AND (tt_content.sys_language_uid = ###REC_FIELD_sys_language_uid### OR tt_content.sys_language_uid = -1) AND tt_content.pid=###CURRENT_PID### AND tt_content.CType=\'gridelements_pi1\' AND (tt_content.uid != ###THIS_UID###) AND (tt_content.tx_gridelements_container != ###THIS_UID### OR tt_content.tx_gridelements_container=0) ORDER BY tt_content.header, tt_content.uid',
			'dontRemapTablesOnCopy' => 'tt_content',
			'itemsProcFunc'       => 'GridElementsTeam\Gridelements\Backend\TtContent->containerItemsProcFunc',
			'size'                => 1,
			'minitems'            => 0,
			'maxitems'            => 1,
		)
	),
	'tx_gridelements_columns'        => array(
		'exclude' => 1,
		'label'   => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_columns',
		'config'  => array(
			'type'          => 'select',
			'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->columnsItemsProcFunc',
			'size'          => 1,
			'maxitems'      => 1,
		)
	),
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

$GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',tx_gridelements_container,tx_gridelements_columns,colPos';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['gridelements_pi1'] = 'backend_layout.gif';
$GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',tx_gridelements_container,tx_gridelements_columns';
$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] .= ',tx_gridelements_container,tx_gridelements_columns';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['gridelements_pi1'] = 'tcarecords-tx_gridelements_backend_layout-default';

$GLOBALS['TCA']['tt_content']['types']['gridelements_pi1']['showitem'] = $GLOBALS['TCA']['tt_content']['types']['text']['showitem'];

$GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\ColPosList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\CTypeList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['sys_language_uid']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\SysLanguageUidList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,gridelements_pi1'] = '';
$GLOBALS['TCA']['tt_content']['columns']['records']['config']['allowed'] .= ',pages';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'recursive', 'shortcut', 'after:records');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_gridelements_backend_layout', 'gridelements_pi1', 'replace:bodytext');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform, tx_gridelements_children', 'gridelements_pi1', 'replace:rte_enabled');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_gridelements_container, tx_gridelements_columns');