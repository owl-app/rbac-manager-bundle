<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Types;

abstract class Item
{
    public const TYPE_ROLE = 'role';
    public const TYPE_PERMISSION = 'permission';

    /**
     * @var string The name of the item. This must be globally unique.
     */
    private string $name;

        /**
     * @var string The path for permission
     */
    private string $path;

    /**
     * @var string The item description.
     */
    private string $description = '';

    /**
     * @var int|null date time representing the item creation time.
     */
    private ?int $createdTime = null;

    /**
     * @var int|null date time representing the item updating time.
     */
    private ?int $updatedTime = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    abstract public function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): self
    {
        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withDescription(string $description): self
    {
        $new = clone $this;
        $new->description = $description;
        return $new;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function withCreatedTime(string $createdTime = null): self
    {
        $new = clone $this;
        $new->createdTime = $createdTime;
        return $new;
    }

    public function getCreatedTime(): ?string
    {
        return $this->createdTime;
    }

    public function withUpdatedTime(string $updatedTime = null): self
    {
        $new = clone $this;
        $new->updatedTime = $updatedTime;
        return $new;
    }

    public function getUpdatedTime(): ?string
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

    public function getAttributes(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'type' => $this->getType(),
            'updated_time' => $this->getUpdatedTime(),
            'created_time' => $this->getCreatedTime(),
        ];
    }
}
