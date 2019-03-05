<?php

namespace GridElementsTeam\Gridelements\DataProcessing;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use GridElementsTeam\Gridelements\Plugin\Gridelements;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Fetch records from the database, using the default .select syntax from TypoScript.
 */
class GridChildrenProcessor implements DataProcessorInterface
{
    /**
     * @var Gridelements
     */
    protected $gridelements;

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * @var array
     */
    protected $contentObjectConfiguration;

    /**
     * @var array
     */
    protected $processorConfiguration;

    /**
     * @var array
     */
    protected $containerProcessorConfiguration;

    /**
     * @var array
     */
    protected $processedData;

    /**
     * @var array
     */
    protected $processedRecordVariables = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $registeredOptions = [
        'sortingDirection'      => 'asc',
        'sortingDirection.'     => [],
        'sortingField'          => 'sorting',
        'sortingField.'         => [],
        'recursive'             => 0,
        'recursive.'            => [],
        'resolveFlexFormData'   => 1,
        'resolveFlexFormData.'  => [],
        'resolveBackendLayout'  => 1,
        'resolveBackendLayout.' => [],
        'respectColumns'        => 1,
        'respectColumns.'       => [],
        'respectRows'           => 1,
        'respectRows.'          => [],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->gridelements = GeneralUtility::makeInstance(Gridelements::class);
        $this->layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class);
        $this->contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
    }

    /**
     * Fetches records from the database as an array
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     *
     * @return array the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        if (
            $processedData['data']['CType'] !== 'gridelements_pi1' ||
            empty($processorConfiguration)
        ) {
            return $processedData;
        }

        $this->containerProcessorConfiguration = $processorConfiguration[$processedData['data']['tx_gridelements_backend_layout'] . '.'];
        if (empty($this->containerProcessorConfiguration)) {
            $this->containerProcessorConfiguration = $processorConfiguration['default.'];
            if (empty($this->containerProcessorConfiguration)) {
                return $processedData;
            }
        }

        $this->contentObjectConfiguration = $contentObjectConfiguration;
        $this->processorConfiguration = $processorConfiguration;
        unset($processorConfiguration);
        $this->processedData = $processedData;
        unset($processedData);

        $targetVariableName = $cObj->stdWrapValue('as', $this->containerProcessorConfiguration, 'children');
        $options = $this->containerProcessorConfiguration['options.'] ? $this->containerProcessorConfiguration['options.'] : [];
        $this->options = array_merge(
            $this->registeredOptions,
            array_intersect_key($options, $this->registeredOptions)
        );
        unset($options);
        foreach ($this->options as $key => &$option) {
            $option = $cObj->stdWrapValue($key, $this->options, $option);
        }

        $this->checkOptions($this->processedData['data']);
        if (isset($this->processorConfiguration['recursive'])) {
            $this->options['recursive'] = $this->processorConfiguration['recursive'];
        }

        $queryConfiguration = [
            'pidInList' => (int)$cObj->data['pid'],
            'orderBy'   => (
                $this->options['sortingField'] ? htmlspecialchars($this->options['sortingField']) : 'sorting'
                ) . ' ' . (
                strtolower($this->options['sortingDirection']) === 'desc' ? 'DESC' : 'ASC'
                ),
            'where'     => 'tx_gridelements_container = ' . (int)$cObj->data['uid'],
        ];
        $records = $cObj->getRecords('tt_content', $queryConfiguration);
        foreach ($records as $record) {
            $this->processChildRecord($record);
        }

        if (
            $this->options['respectColumns'] ||
            $this->options['respectRows']
        ) {
            $this->processedData[$targetVariableName] = $this->sortRecordsIntoMatrix();
        } else {
            $this->processedData[$targetVariableName] = $this->processedRecordVariables;
        }
        unset($this->processedRecordVariables);

        foreach ($this->options as $key => $option) {
            unset($this->options[$key . '.']);
        }
        $this->processedData['options'] = $this->options;
        unset($this->options);

        return $this->processedData;
    }

    /**
     * @param $data
     */
    protected function checkOptions(&$data)
    {
        if (
            (
                $this->options['resolveBackendLayout'] ||
                $this->options['respectColumns'] ||
                $this->options['respectRows']
            ) && !$this->layoutSetup->getRealPid()
        ) {
            $this->layoutSetup->init((int)$data['pid'], $this->contentObjectConfiguration);
        }

        if ($this->options['resolveFlexFormData'] && !empty($data['pi_flexform'])) {
            $this->gridelements->initPluginFlexForm('pi_flexform', $data);
            $this->gridelements->getPluginFlexFormData($data);
        }
        if ($this->options['resolveBackendLayout']) {
            if (!empty($this->layoutSetup->getLayoutSetup($data['tx_gridelements_backend_layout']))) {
                $data['tx_gridelements_backend_layout_resolved'] = $this->layoutSetup->getLayoutSetup($data['tx_gridelements_backend_layout']);
            } elseif (!empty($this->layoutSetup->getLayoutSetup('default'))) {
                $data['tx_gridelements_backend_layout_resolved'] = $this->layoutSetup->getLayoutSetup('default');
            }
        }
    }

    /**
     * Processes child records recursively to get other children into the same array
     *
     * @param $record
     */
    protected function processChildRecord($record)
    {
        $id = (int)$record['uid'];
        $this->checkOptions($record);
        /* @var $recordContentObjectRenderer ContentObjectRenderer */
        $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $recordContentObjectRenderer->start($record, 'tt_content');
        $this->processedRecordVariables[$id] = ['data' => $record];
        if (
            (int)$this->options['recursive'] > 0 &&
            $record['CType'] === 'gridelements_pi1' &&
            !empty($record['tx_gridelements_backend_layout'])
        ) {
            $childProcessorConfiguration = $this->containerProcessorConfiguration;
            $childProcessorConfiguration['dataProcessing.']['0.'] = $this->processorConfiguration;
            $childProcessorConfiguration['dataProcessing.']['0.']['recursive'] = (int)$this->options['recursive'] - 1;
            $childProcessorConfiguration['dataProcessing.']['0'] = 'GridElementsTeam\Gridelements\DataProcessing\GridChildrenProcessor';
            $this->processedRecordVariables[$id] = $this->contentDataProcessor->process(
                $recordContentObjectRenderer,
                $childProcessorConfiguration,
                $this->processedRecordVariables[$id]
            );
        } else {
            $this->processedRecordVariables[$id] = $this->contentDataProcessor->process(
                $recordContentObjectRenderer,
                $this->containerProcessorConfiguration,
                $this->processedRecordVariables[$id]
            );
        }
    }

    /**
     * @return array
     */
    protected function sortRecordsIntoMatrix()
    {
        $processedColumns = [];
        foreach ($this->processedRecordVariables as $key => $processedRecord) {
            if (!isset($processedColumns[$processedRecord['data']['tx_gridelements_columns']])) {
                $processedColumns[$processedRecord['data']['tx_gridelements_columns']] = [];
            }
            $processedColumns[$processedRecord['data']['tx_gridelements_columns']][$key] = $processedRecord;
        }
        if ($this->options['respectRows']) {
            $this->options['respectColumns'] = 1;
            $processedRows = [];
            if (!empty($this->processedData['data']['tx_gridelements_backend_layout_resolved'])) {
                foreach ($this->processedData['data']['tx_gridelements_backend_layout_resolved']['config']['rows.'] as $rowNumber => $row) {
                    foreach ($row['columns.'] as $column) {
                        $key = substr($rowNumber, 0, -1);
                        $processedRows[$key][$column['colPos']] = $processedColumns[$column['colPos']];
                    }
                }
            }
            return $processedRows;
        }
        return $processedColumns;
    }
}
