<?php

namespace Ameos\AmeosScim\Controller\Traits;

use Ameos\AmeosScim\Configuration;
use Ameos\AmeosScim\Enum\Context;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ConfigurationAccess
{
    /**
     * return configuration from request
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getConfiguration(ServerRequestInterface $request): array
    {
        $configuration = [];
        $mappingKey = $request->getAttribute('scim_context') === Context::Frontend ? 'frontend' : 'backend';
        $yamlConfiguration = (new YamlFileLoader())->load(Configuration::getConfigurationFilepath());
        $configuration = $yamlConfiguration['scim'][$mappingKey];
        $configuration['pid'] = 0;

        if ($request->getAttribute('scim_context') === Context::Frontend) {
            $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_ameosscim_access');

            $result = $qb
                ->select('*')
                ->from('tx_ameosscim_access')
                ->where(
                    $qb->expr()->eq(
                        'uid',
                        $qb->createNamedParameter(
                            $request->getAttribute('scim_access'),
                            Connection::PARAM_INT
                        )
                    )
                )
                ->executeQuery();
            if ($access = $result->fetchAssociative()) {
                $configuration['pid'] = (int)$access['pid_frontend'];
            }
        }

        $configuration['pid'] = isset($typoscript['pid']) ? (int)$typoscript['pid'] : 0;
        return $configuration;
    }
}
