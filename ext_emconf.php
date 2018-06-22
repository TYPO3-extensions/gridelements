<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "gridelements".
 * Auto generated 17-06-2013 22:35
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Grid Elements',
    'description' => 'This extension integrates the grid layout concept also to regular content elements - the grid elements.
It offers a lot of new features like advanced drag & drop or real references, that improve the usability of the page and list module to speed up the daily work with the backend.',
    'category' => 'be',
    'version' => '8.2.3',
    'priority' => 'bottom',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => true,
    'createDirs' => '',
    'modify_tables' => 'tt_content',
    'clearcacheonload' => true,
    'author' => 'Grid Elements Team',
    'author_email' => 'info@cybercraft.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.99.99',
        ],
        'conflicts' => [
            'templavoila' => '',
            'jfmulticontent' => '',
            //https://forge.typo3.org/issues/85333
			'flux' => '',
        ],
        'suggests' => [],
    ],
];
