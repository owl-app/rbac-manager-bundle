<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Types;

final class Role extends Item
{
    /**
     * @psalm-return 'role'
     */
    public function getType(): string
    {
        return self::TYPE_ROLE;
    }
}
