<?php

namespace PhucBui\Chat\Contracts;

/**
 * Interface that any User/Customer/Admin model must implement to participate in chat.
 */
interface ChatActorInterface
{
    /**
     * Get the unique identifier for the actor.
     */
    public function getKey();

    /**
     * Get the morph class name.
     */
    public function getMorphClass();

    /**
     * Get the actor's display name for chat.
     */
    public function getChatDisplayName(): string;

    /**
     * Get the actor's avatar URL for chat.
     */
    public function getChatAvatar(): ?string;
}
