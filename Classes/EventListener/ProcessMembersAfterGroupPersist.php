<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\EventListener;

use Ameos\AmeosScim\CustomObject\MemberObject;
use Ameos\AmeosScim\Domain\Repository\AbstractResourceRepository;
use Ameos\AmeosScim\Domain\Repository\BackendUserRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendUserRepository;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\PostPersistMode;
use Ameos\AmeosScim\Event\PostPersistGroupEvent;
use Ameos\AmeosScim\Registry\BulkIdRegistry;
use Ameos\AmeosScim\Service\PatchService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProcessMembersAfterGroupPersist
{
    /**
     * @param FrontendUserRepository $frontendUserRepository
     * @param BackendUserRepository $backendUserRepository
     * @param BulkIdRegistry $bulkIdRegistry
     */
    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BackendUserRepository $backendUserRepository,
        private readonly BulkIdRegistry $bulkIdRegistry
    ) {
    }

    /**
     * attach member after persist
     *
     * @param PostPersistGroupEvent $event
     * @return void
     */
    public function __invoke(PostPersistGroupEvent $event): void
    {
        foreach ($event->getConfiguration()['mapping'] as $property => $configuration) {
            if (isset($configuration['object']) && $configuration['object'] === MemberObject::class) {
                $payload = array_change_key_case($event->getPayload());
                $property = mb_strtolower($property);
                $usersId = $this->convertPayloadToIds($payload[$property] ?? []);

                match ($event->getMode()) {
                    PostPersistMode::Create  => $this->attachUsers($usersId, '', $configuration, $event),
                    PostPersistMode::Update => $this->replaceUsers($usersId, '', $configuration, $event),
                    PostPersistMode::Patch => $this->patchUsers($payload, $property, $configuration, $event),
                };
            }
        }
    }

    /**
     * replace users
     *
     * @param array $usersId
     * @param string $filters // TODO
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function replaceUsers(
        array $usersId,
        string $filters,
        array $configuration,
        PostPersistGroupEvent $event
    ): void {
        $repository = $this->getRepository($event->getContext());

        $field = $configuration['arguments']['field_user'];

        $existingResults = $repository->findByGroup((int)$event->getRecord()['uid']);
        while ($user = $existingResults->fetchAssociative()) {
            if (!in_array($user['scim_id'], $usersId)) {
                $groups = array_diff(
                    array_filter(GeneralUtility::trimExplode(',', $user[$field])),
                    [$event->getRecord()['uid']]
                );
                $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
            }
        }

        $results = $repository->findById($usersId);
        while ($user = $results->fetchAssociative()) {
            $groups = array_filter(GeneralUtility::trimExplode(',', $user[$field]));
            if (!in_array((string)$event->getRecord()['uid'], $groups)) {
                $groups[] = $event->getRecord()['uid'];
                $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
            }
        }
    }

    /**
     * remove all users
     *
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function removeAllUsers(array $configuration, PostPersistGroupEvent $event): void
    {
        $repository = $this->getRepository($event->getContext());

        $field = $configuration['arguments']['field_user'];

        $existingResults = $repository->findByGroup((int)$event->getRecord()['uid']);
        while ($user = $existingResults->fetchAssociative()) {
            $groups = array_diff(
                array_filter(GeneralUtility::trimExplode(',', $user[$field])),
                [$event->getRecord()['uid']]
            );
            $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
        }
    }

    /**
     * remove users
     *
     * @param array $usersId
     * @param string $filters
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function removeUsers(
        array $usersId,
        string $filters,
        array $configuration,
        PostPersistGroupEvent $event
    ): void {
        $repository = $this->getRepository($event->getContext());

        if (empty($usersId) && empty($filters)) {
            $this->removeAllUsers($configuration, $event);
        } else {
            $field = $configuration['arguments']['field_user'];

            if (!empty($usersId)) {
                $results = $repository->findById($usersId);
                while ($user = $results->fetchAssociative()) {
                    $groups = array_diff(
                        array_filter(GeneralUtility::trimExplode(',', $user[$field])),
                        [$event->getRecord()['uid']]
                    );
                    $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
                }
            }

            if (!empty($filters)) {
                $filters = str_replace('value', 'id', $filters);

                [, $results] = $repository->findByFilters(
                    $filters,
                    $event->getConfiguration()['mapping'],
                    $event->getConfiguration()['meta'],
                    $event->getConfiguration()['pid']
                );
                while ($user = $results->fetchAssociative()) {
                    $groups = array_diff(
                        array_filter(GeneralUtility::trimExplode(',', $user[$field])),
                        [$event->getRecord()['uid']]
                    );
                    $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
                }
            }
        }
    }

    /**
     * attach users
     *
     * @param array $usersId
     * @param string $filters // TODO
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function attachUsers(
        array $usersId,
        string $filters,
        array $configuration,
        PostPersistGroupEvent $event
    ): void {
        $field = $configuration['arguments']['field_user'];
        $repository = $this->getRepository($event->getContext());
        $results = $repository->findById($usersId);
        while ($user = $results->fetchAssociative()) {
            $groups = array_filter(GeneralUtility::trimExplode(',', $user[$field]));
            if (!in_array((string)$event->getRecord()['uid'], $groups)) {
                $groups[] = $event->getRecord()['uid'];
                $repository->update($user['scim_id'], [$field => implode(',', $groups)]);
            }
        }
    }

    /**
     * patch users
     *
     * @param array $payload
     * @param string $property
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function patchUsers(
        array $payload,
        string $property,
        array $configuration,
        PostPersistGroupEvent $event
    ): void {
        foreach ($payload['operations'] as $operation) {
            $operation = array_change_key_case($operation);
            $filters = '';
            $path = mb_strtolower($operation['path']);

            if (preg_match('/([^\[]*)\[([^\]]*)\]/', $path, $matches)) {
                $path = $matches[1];
                $filters = $matches[2];
            }

            if ($property === $path) {
                $usersId = $this->convertPayloadToIds($operation['value'] ?? []);
                match (mb_strtolower($operation['op'])) {
                    PatchService::OP_ADD => $this->attachUsers($usersId, $filters, $configuration, $event),
                    PatchService::OP_REPLACE => $this->replaceUsers($usersId, $filters, $configuration, $event),
                    PatchService::OP_REMOVE => $this->removeUsers($usersId, $filters, $configuration, $event),
                };
            }
        }
    }

    /**
     * convert users value payload to scim id list
     *
     * @param array $payload
     * @return array
     */
    private function convertPayloadToIds(array $payload): array
    {
        $ids = [];
        foreach ($payload as $item) {
            if (preg_match('/bulkId:(.*)/i', $item['value'], $matches)) {
                $ids[] = $this->bulkIdRegistry->getResource($matches[1])['id'];
            } else {
                $ids[] = $item['value'];
            }
        }
        return $ids;
    }

    /**
     * return repository depending of content
     *
     * @param Context $context
     * @return BackendUserRepository|FrontendUserRepository
     */
    private function getRepository(Context $context): AbstractResourceRepository
    {
        return $context === Context::Frontend ? $this->frontendUserRepository : $this->backendUserRepository;
    }
}
