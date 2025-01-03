<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Middleware;

use Ameos\AmeosScim\Service\RoutingService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\ForbiddenException;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ScimRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @param RoutingService $routingService
     * @param ConnectionPool $connectionPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RoutingService $routingService,
        private readonly ConnectionPool $connectionPool,
        #[Channel('scim')]
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * process middle ware
     * if uri start with /v2/Users or /v2/Groups : call Scim controller
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('is_scim_request')) {
            $authorization = $request->getHeader('authorization');
            $scimAccess = false;
            if (isset($authorization[0]) && preg_match('/Bearer\s(\S+)/', $authorization[0], $matches)) {
                $scimAccess = $this->isAuthorizedSecret($matches[1]);
                if (!$scimAccess) {
                    $this->logger->critical('Access denied - Bearer not match');
                    throw new ForbiddenException('Access denied');
                }
            } else {
                $this->logger->critical('Access denied - Bearer missing');
                throw new ForbiddenException('Access denied');
            }

            $GLOBALS['TSFE']->no_cache = true;

            $request = $request->withAttribute('scim_access', $scimAccess);

            return $this->routingService->route($request);
        }

        return $handler->handle($request);
    }

    /**
     * return access ID if secret is authorized
     *
     * @param string $secret
     * @return int|false
     */
    private function isAuthorizedSecret(string $secret): int|false
    {
        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_ameosscim_access');
        $result = $qb->select('*')->from('tx_ameosscim_access')->executeQuery();
        while ($access = $result->fetchAssociative()) {
            if ($hashInstance->checkPassword($secret, $access['secret'])) {
                return (int)$access['uid'];
            }
        }
        return false;
    }
}
