<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Factory;

interface ItemFactoryInterface
{
    public function create(string $type, string $name): object;
}
