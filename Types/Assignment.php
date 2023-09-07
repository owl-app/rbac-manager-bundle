<?php

declare(strict_types=1);

namespace Owl\Bundle\RbacManagerBundle\Types;

/**
 * Assignment represents an assignment of a role or a permission to a user.
 */
class Assignment
{
    /**
     * @var string The user ID. This should be a int representing the unique identifier of a user.
     */
    private string $userId;
    /**
     * @var string The role or permission id.
     */
    private string $itemId;

    /**
     * @var string The user ID. This should be a string representing the unique identifier of a user.
     */
    private string $itemName;

    /**
     * @var string|null UNIX timestamp representing the assignment creation time.
     */
    private ?string $createdAt;

    /**
     * @param string $userId The user ID. This should be a string representing the unique identifier of a user.
     * @param string $itemId The role or permission id.
     * @param null|string $createdAt UNIX timestamp representing the assignment creation time.
     */
    public function __construct(string $userId, string $itemId, string $itemName, string $createdAt = null)
    {
        $this->userId = $userId;
        $this->itemId = $itemId;
        $this->itemName = $itemName;
        $this->createdAt = $createdAt;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function withItemId(int $id): static
    {
        $new = clone $this;
        $new->itemId = $id;
        return $new;
    }

    public function getCreatedAt():? string
    {
        return $this->createdAt;
    }
}
