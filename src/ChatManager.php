<?php

namespace PhucBui\Chat;

/**
 * ChatManager handles actor resolution and matching at runtime.
 * Closures cannot be stored in config, so they are registered here.
 */
class ChatManager
{
    /**
     * Actor resolvers: actorName => Closure($guard): ?Model
     */
    protected array $resolvers = [];

    /**
     * Actor matchers: actorName => Closure($user): bool
     */
    protected array $matchers = [];

    /**
     * Display name resolver: Closure($user): string
     */
    protected $displayNameResolver = null;

    /**
     * Avatar resolver: Closure($user): ?string
     */
    protected $avatarResolver = null;

    /**
     * Register a resolver for an actor type.
     * The resolver receives the guard name and returns the authenticated user or null.
     */
    public function resolveActorUsing(string $actorName, \Closure $resolver): static
    {
        $this->resolvers[$actorName] = $resolver;
        return $this;
    }

    /**
     * Register a matcher for an actor type.
     * The matcher receives a user model and returns true if the user belongs to this actor type.
     */
    public function matchActorUsing(string $actorName, \Closure $matcher): static
    {
        $this->matchers[$actorName] = $matcher;
        return $this;
    }

    /**
     * Set display name resolver.
     */
    public function resolveDisplayNameUsing(\Closure $resolver): static
    {
        $this->displayNameResolver = $resolver;
        return $this;
    }

    /**
     * Set avatar resolver.
     */
    public function resolveAvatarUsing(\Closure $resolver): static
    {
        $this->avatarResolver = $resolver;
        return $this;
    }

    /**
     * Resolve the authenticated user for a given actor type.
     */
    public function resolveActor(string $actorName): mixed
    {
        $config = config("chat.actors.{$actorName}");
        if (!$config) {
            return null;
        }

        $guard = $config['guard'] ?? null;

        if (isset($this->resolvers[$actorName])) {
            return ($this->resolvers[$actorName])($guard);
        }

        // Default: use Laravel's auth guard
        return $guard ? auth($guard)->user() : auth()->user();
    }

    /**
     * Check if a given user matches an actor type.
     */
    public function isActorType(string $actorName, $user): bool
    {
        if (isset($this->matchers[$actorName])) {
            return ($this->matchers[$actorName])($user);
        }

        // Default: check if user is instance of the actor's model class
        $modelClass = config("chat.actors.{$actorName}.model");
        return $modelClass && $user instanceof $modelClass;
    }

    /**
     * Determine which actor type a user belongs to.
     */
    public function detectActorType($user): ?string
    {
        $actors = config('chat.actors', []);

        foreach ($actors as $actorName => $config) {
            if ($this->isActorType($actorName, $user)) {
                return $actorName;
            }
        }

        return null;
    }

    /**
     * Get display name for a user.
     */
    public function getDisplayName($user): string
    {
        if ($this->displayNameResolver) {
            return ($this->displayNameResolver)($user);
        }

        return $user->name ?? (string) $user->getKey();
    }

    /**
     * Get avatar URL for a user.
     */
    public function getAvatar($user): ?string
    {
        if ($this->avatarResolver) {
            return ($this->avatarResolver)($user);
        }

        return $user->avatar_url ?? null;
    }

    /**
     * Check if an actor has a specific capability.
     */
    public function hasCapability(string $actorName, string $capability): bool
    {
        return config("chat.actors.{$actorName}.capabilities.{$capability}", false);
    }

    /**
     * Get all capabilities for an actor.
     */
    public function getCapabilities(string $actorName): array
    {
        return config("chat.actors.{$actorName}.capabilities", []);
    }

    /**
     * Get actors that have a specific capability enabled.
     */
    public function getActorsWithCapability(string $capability): array
    {
        $result = [];
        $actors = config('chat.actors', []);

        foreach ($actors as $actorName => $config) {
            if ($config['capabilities'][$capability] ?? false) {
                $result[] = $actorName;
            }
        }

        return $result;
    }

    /**
     * Determine if an actor type can start a chat with another actor type based on config.
     */
    public function canChatWith(string $actorAType, string $actorBType): bool
    {
        $allowedPairs = config('chat.chat_types.allowed_pairs', []);
        
        foreach ($allowedPairs as $pair) {
            if (
                (in_array($actorAType, $pair, true) && in_array($actorBType, $pair, true))
            ) {
                // If the pair requires exact equality like ['admin', 'admin'],
                // and the types differ, it won't be caught by just in_array for both 
                // unless checking count properly, but in_array($a, $pair) && in_array($b, $pair)
                // actually covers ['client', 'admin'] nicely.
                // However, for ['admin', 'admin'] and checking ('client', 'admin'), it won't match.
                // It is correct. Oh wait, if $pair is ['admin', 'client'] it matches both.
                // Let's accurately verify.
                if (
                    ($pair[0] === $actorAType && $pair[1] === $actorBType) ||
                    ($pair[1] === $actorAType && $pair[0] === $actorBType)
                ) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
