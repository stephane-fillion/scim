<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile('ameos_scim', 'Configuration/TypoScript', 'Scim configuration');
