<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Types;

abstract class Item
{
    public const TYPE_ROLE = 'role';

    public const TYPE_PERMISSION = 'permission';

    /** @var string The name of the item. This must be globally unique. */
    private string $name;

    /** @var string The path for permission */
    private string $path;

    /** @var string The item description. */
    private string $description = '';

    /** @var string|null date time representing the item creation time. */
    private ?string $createdTime = null;

    /** @var string|null date time representing the item updating time. */
    private ?string $updatedTime = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    abstract public function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): static
    {
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): static
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withDescription(string $description): static
    {
        $new = clone $this;
        $new->description = $description;

        return $new;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function withCreatedTime(string $createdTime = null): static
    {
        $new = clone $this;
        $new->createdTime = $createdTime;

        return $new;
    }

    public function getCreatedTime(): string|null
    {
        return $this->createdTime;
    }

    public function withUpdatedTime(string $updatedTime = null): static
    {
        $new = clone $this;
        $new->updatedTime = $updatedTime;

        return $new;
    }

    public function getUpdatedTime(): string|null
    {
        return $this->updatedTime;
    }

    public function hasCreatedTime(): bool
    {
        return $this->createdTime !== null;
    }

    public function hasUpdatedTime(): bool
    {
        return $this->updatedTime !== null;
    }

    /**
     * @return (string|null)[]
     *
     * @psalm-return array{name: string, description: string, type: string, updated_at: null|string, created_at: null|string}
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            'updated_at' => $this->getUpdatedTime(),
            'created_at' => $this->getCreatedTime(),
        ];
    }
}
