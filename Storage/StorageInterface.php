<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Storage;

use Owl\Bundle\RbacManagerBundle\Types\Item;
use Owl\Bundle\RbacManagerBundle\Types\Role;
use Owl\Bundle\RbacManagerBundle\Types\Permission;
use Owl\Bundle\RbacManagerBundle\Types\Assignment;

interface StorageInterface
{
    public function getItems(): array;

    public function getItemByName(string $name): ?Item;

    public function addItem(Item $item): void;

    public function updateItem(string $name, Item $item): void;

    public function removeItem(Item $item): void;

    public function getChildren(): array;

    public function getRoles(): array;

    public function getRoleByName(string $name): ?Role;

    public function clearRoles(): void;

    public function getPermissions(): array;

    public function getPermissionByName(string $name): ?Permission;

    public function clearPermissions(): void;

    public function getChildrenByName(string $name): array;

    public function hasChildren(string $name): bool;

    public function addChild(Item $parent, Item $child): void;

    public function removeChild(Item $parent, Item $child): void;

    public function removeChildren(Item $parent): void;

    public function getAssignments(): array;

    public function getUserAssignments(int $userId): array;

    public function getUserAssignmentByName(int $userId, string $name): ?Assignment;

    public function addAssignment(int $userId, Item $item): void;

    public function removeAssignment(int $userId, Assignment $assigment): void;

    public function removeAllAssignments(int $userId): void;
}
