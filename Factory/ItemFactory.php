<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Factory;

use Owl\Bundle\RbacManagerBundle\Types\Item;
use Owl\Bundle\RbacManagerBundle\Types\Role;
use Owl\Bundle\RbacManagerBundle\Types\Permission;
use RuntimeException;

final class ItemFactory implements ItemFactoryInterface
{
    /**
     * @return Permission|Role
     */
    public function create(string $type, string $name): object
    {
        switch($type) {
            case Item::TYPE_PERMISSION:
                $class = Permission::class;
                break;
            case Item::TYPE_ROLE:
                $class = Role::class;
                break;
            default:
                throw new RuntimeException('Invalid type % ', $type);
                break;
        }

        return new $class($name);
    }
}
