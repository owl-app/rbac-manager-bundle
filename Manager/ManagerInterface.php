<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Manager;

use Owl\Bundle\RbacManagerBundle\Types\Assignment;
use Owl\Bundle\RbacManagerBundle\Types\Item;

interface ManagerInterface
{
    public function assign(Item $item, int $userId): ?Assignment;

    public function revoke(Item $item, int $userId): void;

    public function getRolesByUser(int $userId): array;

    public function getPermissionsByUser(int $userId, bool $group = false): array;

    public function getPermissionsByRole(string $roleName): array;
}
