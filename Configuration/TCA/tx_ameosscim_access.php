<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang_db.xlf:scim_access',
        'label' => 'name',
        'crdate' => 'createdon',
        'tstamp' => 'updatedon',
        'adminOnly' => true,
        'hideTable' => false,
        'rootLevel' => 1,
        'default_sortby' => 'name',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
        ],
        'searchFields' => 'name',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'iconfile' => 'EXT:ameos_scim/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => 'disabled, name, secret, pid_frontend',
        ],
    ],
    'palettes' => [],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang_db.xlf:scim_access.name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'secret' => [
            'label' => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang_db.xlf:scim_access.secret',
            'config' => [
                'type' => 'password',
                'required' => true,
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'allowEdit' => false,
                            'passwordRules' => [
                                'length' => 40,
                                'random' => 'hex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'disabled' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'pid_frontend' => [
            'label' => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang_db.xlf:scim_access.pid_frontend',
            'config' => [
                'type' => 'group',
                'required' => false,
                'allowed' => 'pages',
                'multiple' => false,
                'autoSizeMax' => 1,
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
    ],
];
