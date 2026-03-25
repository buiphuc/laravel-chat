<?php

namespace PhucBui\Chat;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PhucBui\Chat\ChatManager resolveActorUsing(string $actorName, \Closure $resolver)
 * @method static \PhucBui\Chat\ChatManager matchActorUsing(string $actorName, \Closure $matcher)
 * @method static \PhucBui\Chat\ChatManager resolveDisplayNameUsing(\Closure $resolver)
 * @method static \PhucBui\Chat\ChatManager resolveAvatarUsing(\Closure $resolver)
 * @method static mixed resolveActor(string $actorName)
 * @method static bool isActorType(string $actorName, $user)
 * @method static ?string detectActorType($user)
 * @method static string getDisplayName($user)
 * @method static ?string getAvatar($user)
 * @method static bool hasCapability(string $actorName, string $capability)
 * @method static array getCapabilities(string $actorName)
 * @method static array getActorsWithCapability(string $capability)
 *
 * @see \PhucBui\Chat\ChatManager
 */
class Chat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ChatManager::class;
    }
}
