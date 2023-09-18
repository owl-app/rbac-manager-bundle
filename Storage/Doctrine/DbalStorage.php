<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Storage\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Query\QueryBuilder;
use Owl\Bundle\RbacManagerBundle\Storage\StorageInterface;
use Owl\Bundle\RbacManagerBundle\Types\Assignment;
use Owl\Bundle\RbacManagerBundle\Types\Item;
use Owl\Bundle\RbacManagerBundle\Types\Permission;
use Owl\Bundle\RbacManagerBundle\Types\Role;

final class DbalStorage implements StorageInterface
{
    private Connection $connection;

    private string $itemTable = 'auth_item';

    private string $itemChildTable = 'auth_item_child';

    private string $assignmentTable = 'auth_assignment';

    private string $ruleTable = 'auth_rule';

    private array $itemsLoaded = [];

    private ?array $children = null;

    private array $assigmentsLoaded = [];

    private array $castAttributes = [
        'ruleName' => 'rule_name',
        'createdAt' => 'created_time',
        'updatedAt' => 'updated_time',
    ];

    public function __construct(
        Connection $connection,
        string $itemTable = null,
        string $itemChildTable = null,
        string $assignmentTable = null,
        string $ruleTable = null,
    ) {
        $this->connection = $connection;
        $this->itemTable = $itemTable ?? $this->itemTable;
        $this->itemChildTable = $itemChildTable ?? $this->itemChildTable;
        $this->assignmentTable = $assignmentTable ?? $this->assignmentTable;
        $this->ruleTable = $ruleTable ?? $this->ruleTable;
    }

    public function getItems(): array
    {
        return $this->getItemsByTypes([Item::TYPE_ROLE, Item::TYPE_PERMISSION]);
    }

    public function getItemsValues(): array
    {
        $this->getNotLoadedItems([Item::TYPE_ROLE, Item::TYPE_PERMISSION]);

        return array_merge(...array_values($this->itemsLoaded));
    }

    public function getItemByName(string $name): ?Item
    {
        return $this->getItems()[$name] ?? null;
    }

    public function addItem(Item $item): void
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->itemTable);

        foreach ($item->getAttributes() as $name => $value) {
            $queryBuilder->setValue(
                $this->castAttributes[$name] ?? $name,
                $queryBuilder->createNamedParameter($value),
            );
        }

        $queryBuilder->execute();

        $this->itemsLoaded[$item->getType()][$item->getName()] = array_merge(
            [
                'id' => $this->connection->lastInsertId()
            ], 
            $item->getAttributes()
        );
    }

    public function updateItem(string $name, Item $item): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $parameterName = $queryBuilder->createNamedParameter($name);

        if ($item->getName() !== $name && !$this->supportsCascadeUpdate()) {
            $parameterItemName = $queryBuilder->createNamedParameter($item->getName());

            $queryBuilder->update($this->itemChildTable, 'ct')
                ->set('ct.parent', $parameterItemName)
                ->where($queryBuilder->expr()->eq('parent', $parameterName))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);

            $queryBuilder->update($this->itemChildTable, 'ct')
                ->set('ct.child', $parameterItemName)
                ->where($queryBuilder->expr()->eq('child', $parameterName))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);

            $queryBuilder->update($this->assignmentTable, 'a')
                ->set('a.item_name', $parameterItemName)
                ->where($queryBuilder->expr()->eq('a.item_name', $parameterName))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);
        }

        $item = $item->withUpdatedTime((string) time());

        $queryBuilder->update($this->itemTable, 'i')
            ->where($queryBuilder->expr()->eq('name', $parameterName));

        foreach ($item->getAttributes() as $name => $value) {
            $queryBuilder->set(
                'i.' . ($this->castAttributes[$name] ?? $name),
                $queryBuilder->createNamedParameter($value),
            );
        }

        $queryBuilder->execute();
    }

    public function removeItem(Item $item): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $parameterItemName = $queryBuilder->createNamedParameter($item->getName());

        if (!$this->supportsCascadeUpdate()) {
            $queryBuilder->delete($this->itemChildTable)
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('parent', $parameterItemName),
                        $queryBuilder->expr()->eq('child', $parameterItemName),
                    ),
                )
                ->execute();
            $this->clearQueryBuilder($queryBuilder);

            $queryBuilder->delete($this->assignmentTable)
                ->where($queryBuilder->expr()->eq('item_name', $parameterItemName))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);
        }

        $queryBuilder->delete($this->itemTable)
            ->where($queryBuilder->expr()->eq('name', $parameterItemName))
            ->execute();
    }

    public function getRoles(): array
    {
        return $this->getItemsByTypes(Item::TYPE_ROLE);
    }

    public function getRoleByName(string $name): ?Role
    {
        return $this->getItemByType(Item::TYPE_ROLE, $name) ?? null;
    }

    public function clearRoles(): void
    {
        $this->removeAllItems(Item::TYPE_ROLE);
    }

    public function getPermissions(): array
    {
        return $this->getItemsByTypes(Item::TYPE_PERMISSION);
    }

    public function getPermissionByName(string $name): ?Permission
    {
        return $this->getItemByType(Item::TYPE_PERMISSION, $name) ?? null;
    }

    private function supportsCascadeUpdate(): bool
    {
        return !$this->connection->getDriver() instanceof Driver\PDO\SQLite\Driver;
    }

    public function clearPermissions(): void
    {
        $this->removeAllItems(Item::TYPE_PERMISSION);
    }

    /**
     * @psalm-param 'permission'|'role' $type
     *
     * @return Item|null
     */
    private function getItemByType(string $type, string $name)
    {
        $this->getNotLoadedItems([$type]);

        if (isset($this->itemsLoaded[$type][$name])) {
            return $this->getInstanceFromAttributes($this->itemsLoaded[$type][$name]);
        }
    }

    /**
     * @param string|string[] $types
     *
     * @psalm-param 'permission'|'role'|list{'role', 'permission'} $types
     *
     * @return Item[]
     *
     * @psalm-return array<Item>
     */
    private function getItemsByTypes(array|string $types): array
    {
        $types = (array) $types;
        $this->getNotLoadedItems($types);
        $items = [];

        foreach ($types as $type) {
            if (isset($this->itemsLoaded[$type])) {
                foreach ($this->itemsLoaded[$type] as $item) {
                    $items[$item['name']] = $this->getInstanceFromAttributes($item);
                }
            }
        }

        return $items;
    }

    /**
     * @return Item|null
     */
    private function getItemFromLoaded(string $name)
    {
        foreach ($this->itemsLoaded as $type => $items) {
            if (isset($items[$name])) {
                return $this->getInstanceFromAttributes($items[$name]);
            }
        }
    }

    private function getNotLoadedItems(array $types): void
    {
        $notLoadedTypes = [];
        if ($types) {
            foreach ($types as $type) {
                if (!isset($this->itemsLoaded[$type])) {
                    $notLoadedTypes[] = $type;
                }
            }
        }
        if ($notLoadedTypes) {
            $this->loadItems($notLoadedTypes);
        }
    }

    /**
     * @psalm-param non-empty-list<mixed> $types
     */
    private function loadItems(array $types): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $stmt = $queryBuilder
            ->select('*')
            ->from($this->itemTable)
            ->where(
                $queryBuilder->expr()->in(
                    'type',
                    /** @psalm-suppress RedundantCast */
                    $queryBuilder->createNamedParameter($types, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY),
                ),
            )
            ->execute();

        $rows = $stmt->fetchAllAssociative();

        if ($rows) {
            foreach ($rows as $row) {
                $this->itemsLoaded[$row['type']][$row['name']] = $row;
            }
        }
    }

    private function removeAllItems(string $type = null): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $parameterType = $queryBuilder->createNamedParameter($type);

        if (!$this->supportsCascadeUpdate()) {
            $stmt = $queryBuilder
                ->select('name')
                ->from($this->itemTable)
                ->where($queryBuilder->expr()->eq('type', $parameterType))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);

            $names = $stmt->fetchFirstColumn();

            if (empty($names)) {
                return;
            }
            $key = $type == Item::TYPE_PERMISSION ? 'child' : 'parent';
            $parameterNames = $queryBuilder->createNamedParameter($names, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

            $queryBuilder->delete($this->itemChildTable)
                ->where($queryBuilder->expr()->in($key, $parameterNames))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);

            $queryBuilder->delete($this->assignmentTable)
                ->where($queryBuilder->expr()->in('item_name', $parameterNames))
                ->execute();
            $this->clearQueryBuilder($queryBuilder);
        }

        $queryBuilder->delete($this->itemTable)
            ->where($queryBuilder->expr()->in('type', $parameterType))
            ->execute();
    }

    private function getInstanceByTypeAndName(string $type, string $name): Permission|Role
    {
        return $type === Item::TYPE_PERMISSION ? new Permission($name) : new Role($name);
    }

    private function getInstanceFromAttributes(array $attributes): Item
    {
        return $this
            ->getInstanceByTypeAndName($attributes['type'], $attributes['name'])
            ->withDescription($attributes['description'] ?? '');
    }

    private function clearQueryBuilder(QueryBuilder $queryBuilder, bool $clearParameters = false): void
    {
        if ($clearParameters) {
            $queryBuilder->setParameters([]);
        }

        $queryBuilder->resetQueryParts();
    }

    public function getChildren(): ? array
    {
        if (null === $this->children) {
            $this->loadChildrens();
        }

        return $this->children;
    }

    public function getChildrenByName(string $name): array
    {
        return $this->getChildren()[$name] ?? [];
    }

    public function addChild(Item $parent, Item $child): void
    {
        $items = $this->getItemsValues();
        $queryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->itemChildTable);

        $parentData = $items[$parent->getName()];
        $childData = $items[$child->getName()];

        $queryBuilder->setValue('parent', $queryBuilder->createNamedParameter($parentData['id']));
        $queryBuilder->setValue('child', $queryBuilder->createNamedParameter($childData['id']));

        $queryBuilder->execute();
    }

    public function hasChildren(string $name): bool
    {
        return isset($this->getChildren()[$name]);
    }

    public function removeChild(Item $parent, Item $child): void
    {
        $items = $this->getItemsValues();
        $queryBuilder = $this->connection->createQueryBuilder();

        $parentData = $items[$parent->getName()];
        $childData = $items[$child->getName()];

        $queryBuilder->delete($this->itemChildTable)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parentData['id'])),
                    $queryBuilder->expr()->eq('child', $queryBuilder->createNamedParameter($childData['id'])),
                ),
            )
            ->execute();
    }

    public function removeChildren(Item $parent): void
    {
        $items = $this->getItemsValues();
        $queryBuilder = $this->connection->createQueryBuilder();

        $parentData = $items[$parent->getName()];

        $queryBuilder->delete($this->itemChildTable)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parentData['id'])),
                ),
            )
            ->execute();
    }

    /**
     * @return array[]
     *
     * @psalm-return array<array>
     */
    private function loadChildrens(): array
    {
        $items = $this->getItemsById();
        $children = [];
        $stmt = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->itemChildTable)
            ->execute();

        $rows = $stmt->fetchAllAssociative();

        if ($rows) {
            foreach ($rows as $row) {
                $children[($items[$row['parent']])->getName()][($items[$row['child']])->getName()] = $items[$row['child']];
            }
        }

        return $this->children = $children;
    }

    /**
     * @return Item[]
     *
     * @psalm-return array<Item>
     */
    private function getItemsById(): array
    {
        $items = [];
        $queryBuilder = $this->connection->createQueryBuilder();
        $stmt = $queryBuilder
            ->select('*')
            ->from($this->itemTable)
            ->execute();

        $rows = $stmt->fetchAllAssociative();

        if ($rows) {
            foreach ($rows as $row) {
                $items[$row['id']] = $this->getInstanceFromAttributes($row);
            }
        }

        return $items;
    }

    public function getUserAssignments(int $userId): array
    {
        if (!isset($this->assigmentsLoaded[$userId])) {
            $queryBuilder = $this->connection->createQueryBuilder();
            $stmt = $queryBuilder
                ->select('at.item_id, ai.name')
                ->from($this->assignmentTable, 'at')
                ->leftJoin('at', $this->itemTable, 'ai', 'at.item_id = ai.id')
                ->where(
                    $queryBuilder->expr()->eq(
                        'user_id',
                        $queryBuilder->createNamedParameter($userId),
                    ),
                )
                ->execute();

            $rows = $stmt->fetchAllAssociative();

            if ($rows) {
                foreach ($rows as $row) {
                    $this->assigmentsLoaded[$userId][$row['name']] = new Assignment((string) $userId, (string) $row['item_id'], $row['name']);
                }
            } else {
                $this->assigmentsLoaded[$userId] = [];
            }
        }

        return $this->assigmentsLoaded[$userId];
    }

    public function getAssignments(): array
    {
        return $this->assigmentsLoaded;
    }

    public function getUserAssignmentByName(int $userId, string $name): ?Assignment
    {
        return $this->getUserAssignments($userId)[$name] ?? null;
    }

    public function addAssignment(int $userId, Item $item): void
    {
        $items = $this->getItemsValues();
        $item = $items[$item->getName()];

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->insert($this->assignmentTable)
            ->setValue('item_id', '?')
            ->setValue('user_id', '?')
            ->setParameter(0, $item['id'])
            ->setParameter(1, $userId)
            ->execute();

        $this->assigmentsLoaded[$userId][$item['name']] = new Assignment((string) $userId, (string) $item['id'], $item['name']);
    }

    public function removeAssignment(int $userId, Assignment $assigment): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->delete($this->assignmentTable)
            ->leftJoin('at', $this->itemTable, 'ai', 'at.item_id = ai.id')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('item_id', $queryBuilder->createNamedParameter($assigment->getItemId())),
                    $queryBuilder->expr()->eq('user_id', $queryBuilder->createNamedParameter($userId)),
                ),
            )
            ->execute();

        unset($this->assigmentsLoaded[$userId][$assigment->getItemName()]);
    }

    public function removeAllAssignments(int $userId): void
    {
        $this->assigmentsLoaded[$userId] = [];

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->delete($this->assignmentTable)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('user_id', $queryBuilder->createNamedParameter($userId)),
                ),
            )
            ->execute();
    }

    public function clearLoadedItems(): void
    {
        $this->itemsLoaded = [];
    }
}
