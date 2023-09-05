<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Manager;

use InvalidArgumentException;
use Owl\Bundle\RbacManagerBundle\Storage\StorageInterface;
use Owl\Bundle\RbacManagerBundle\Types\Assignment;
use Owl\Bundle\RbacManagerBundle\Types\Item;
use Owl\Bundle\RbacManagerBundle\Types\Role;
use Owl\Bundle\RbacManagerBundle\Types\Permission;
use RuntimeException;

interface ManagerInterface
{
    public function assign(Item $item, int $userId): ?Assignment;

    public function getRolesByUser(int $userId): array;

    public function getPermissionsByUser(int $userId, bool $group = false): array;
}
