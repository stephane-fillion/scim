<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Service;

use Ameos\AmeosScim\Domain\Repository\AbstractResourceRepository;
use Ameos\AmeosScim\Domain\Repository\BackendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendGroupRepository;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\PostPersistMode;
use Ameos\AmeosScim\Enum\ResourceType;
use Ameos\AmeosScim\Event\PostDeleteGroupEvent;
use Ameos\AmeosScim\Event\PostPersistGroupEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class GroupService
{
    /**
     * @param ResourceService $resourceService
     * @param BackendGroupRepository $backendGroupRepository
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly FrontendGroupRepository $frontendGroupRepository,
        private readonly BackendGroupRepository $backendGroupRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * list groups
     *
     * @param array $queryParams
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function search(array $queryParams, array $configuration, Context $context): array
    {
        return $this->resourceService->search(
            $this->getRepository($context),
            ResourceType::Group,
            $context,
            $queryParams,
            $this->getConfiguration($configuration)
        );
    }

    /**
     * detail an group
     *
     * @param string $resourceId
     * @param array $queryParams
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function read(string $resourceId, array $queryParams, array $configuration, Context $context): array
    {
        return $this->resourceService->read(
            $this->getRepository($context),
            ResourceType::Group,
            $context,
            $resourceId,
            $queryParams,
            $this->getConfiguration($configuration)
        );
    }

    /**
     * create an group
     *
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function create(array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->create(
            $this->getRepository($context),
            $payload,
            $this->getConfiguration($configuration)
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $this->getConfiguration($configuration),
            $payload,
            $resource,
            PostPersistMode::Create,
            $context
        ));

        return $this->read($resource['scim_id'], [], $configuration, $context);
    }

    /**
     * update an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function update(string $resourceId, array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->update(
            $this->getRepository($context),
            $resourceId,
            $payload,
            $this->getConfiguration($configuration)
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $this->getConfiguration($configuration),
            $payload,
            $resource,
            PostPersistMode::Update,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * patch  an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function patch(string $resourceId, array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->patch(
            $this->getRepository($context),
            $resourceId,
            $payload,
            $this->getConfiguration($configuration)
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $this->getConfiguration($configuration),
            $payload,
            $resource,
            PostPersistMode::Patch,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * delete  an group
     *
     * @param string $resourceId
     * @param array $configuration
     * @param Context $context
     * @return void
     */
    public function delete(string $resourceId, array $configuration, Context $context): void
    {
        $this->resourceService->delete($this->getRepository($context), $resourceId);
        $this->eventDispatcher->dispatch(new PostDeleteGroupEvent(
            $resourceId,
            $this->getConfiguration($configuration)['mapping'],
            $context
        ));
    }

    /**
     * return repository
     *
     * @param Context $context
     * @return AbstractResourceRepository
     */
    private function getRepository(Context $context): AbstractResourceRepository
    {
        return $context === Context::Frontend ? $this->frontendGroupRepository : $this->backendGroupRepository;
    }

    /**
     * return configuration for group
     *
     * @param array $configuration
     * @return array
     */
    private function getConfiguration(array $configuration): array
    {
        return [
            'pid' => $configuration['pid'],
            'mapping' => $configuration['group']['mapping'],
            'meta' => $configuration['group']['meta'],
        ];
    }
}
