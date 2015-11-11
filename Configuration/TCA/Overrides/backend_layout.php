<?php
// The default wizard from the Core is good enough for the time being, except that it does not provide the ctype limiting feature
$GLOBALS['TCA']['backend_layout']['columns']['config']['config']['wizards']['0']['module']['name'] = 'wizard_gridelements_backend_layout';