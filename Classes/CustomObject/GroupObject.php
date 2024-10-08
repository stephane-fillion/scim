<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\CustomObject;

use Ameos\AmeosScim\Enum\Context;
use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GroupObject implements CustomObjectInterface
{
    /**
     * @param ConnectionPool $connectionPool
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * return payload for $data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function read(array $data, array $configuration, Context $context)
    {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');
        $confPath = $context === Context::Frontend ? 'fe_path' : 'be_path';
        $table = $context === Context::Frontend ? 'fe_groups' : 'be_groups';
        $apiPath = $this->extensionConfiguration->get('ameos_scim', $confPath) . 'Groups/';

        $usergroups = [];
        if (isset($data['usergroup'])) {
            $qb = $this->connectionPool->getQueryBuilderForTable($table);
            $usergroups = $qb
                ->select('*')
                ->from($table)
                ->where(
                    $qb->expr()->in(
                        'uid',
                        $qb->createNamedParameter(
                            GeneralUtility::trimExplode(',', $data['usergroup']),
                            ArrayParameterType::INTEGER
                        )
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();
        }

        return array_map(
            fn ($group) => [
                'value' => $group['scim_id'],
                'display' => $group['title'],
                '$ref' => rtrim($normalizedParams->getSiteUrl(), '/') . $apiPath . $group['scim_id'],
            ],
            $usergroups
        );
    }

    /**
     * return update $data array
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     * @return array
     */
    public function write(array $payload, array $data, array $configuration): array
    {
        return $data;
    }

    /**
     * return fields associate to properties
     *
     * @param array $configuration
     * @param string $filter
     * @return array|false
     */
    public function getAssociateFields(array $configuration, ?string $filter): array|false
    {
        return false;
    }
}
