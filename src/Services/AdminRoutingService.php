<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Models\ChatRoom;

class AdminRoutingService
{
    public function __construct(
        protected ChatRoomRepositoryInterface $roomRepository,
        protected ChatParticipantRepositoryInterface $participantRepository,
        protected ChatManager $chatManager,
    ) {
    }

    /**
     * Find the best admin to route a client to.
     */
    public function findBestAdmin(Model $client): ?Model
    {
        $config = config('chat.auto_routing', []);

        if (!($config['enabled'] ?? false)) {
            return null;
        }

        $strategy = $config['strategy'] ?? 'last_contacted';
        $toActorName = $config['to_actor'] ?? 'admin';

        // Try primary strategy
        $admin = $this->applyStrategy($strategy, $client, $toActorName);

        // Fallback
        if (!$admin) {
            $fallback = $config['fallback'] ?? 'least_busy';
            if ($fallback !== $strategy) {
                $admin = $this->applyStrategy($fallback, $client, $toActorName);
            }
        }

        return $admin;
    }

    /**
     * Apply a routing strategy.
     */
    protected function applyStrategy(string $strategy, Model $client, string $toActorName): ?Model
    {
        return match ($strategy) {
            'last_contacted' => $this->lastContacted($client, $toActorName),
            'least_busy' => $this->leastBusy($toActorName),
            'round_robin' => $this->roundRobin($toActorName),
            default => null,
        };
    }

    /**
     * Strategy: Find the admin that last chatted with this client.
     */
    protected function lastContacted(Model $client, string $toActorName): ?Model
    {
        $actorConfig = config("chat.actors.{$toActorName}");
        if (!$actorConfig) {
            return null;
        }

        $modelClass = $actorConfig['model'];

        // Find rooms where client is a participant
        $rooms = ChatRoom::where('max_members', 2)
            ->whereHas('participants', function ($q) use ($client) {
                $q->where('actor_type', $client->getMorphClass())
                  ->where('actor_id', $client->getKey());
            })
            ->whereHas('participants', function ($q) use ($modelClass, $toActorName) {
                $q->where('actor_type', $modelClass);
            })
            ->orderByDesc('last_message_at')
            ->first();

        if (!$rooms) {
            return null;
        }

        // Find the admin participant
        $adminParticipant = $rooms->participants()
            ->where('actor_type', $modelClass)
            ->where(function($q) use ($client) {
                $q->where('actor_type', '!=', $client->getMorphClass())
                  ->orWhere('actor_id', '!=', $client->getKey());
            })
            ->first();

        if ($adminParticipant) {
            $admin = $modelClass::find($adminParticipant->actor_id);
            if ($admin && $this->chatManager->isActorType($toActorName, $admin)) {
                return $admin;
            }
        }

        return null;
    }

    /**
     * Strategy: Find the admin with the least active conversations.
     */
    protected function leastBusy(string $toActorName): ?Model
    {
        $actorConfig = config("chat.actors.{$toActorName}");
        if (!$actorConfig) {
            return null;
        }

        $modelClass = $actorConfig['model'];
        $table = config('chat.table_names.participants', 'chat_participants');

        // Get all admins and count their active rooms
        $admins = $modelClass::select($modelClass::query()->getModel()->getTable() . '.*')
            ->selectRaw("(SELECT COUNT(*) FROM {$table} WHERE actor_type = ? AND actor_id = {$modelClass::query()->getModel()->getTable()}.id) as rooms_count", [$modelClass])
            ->orderBy('rooms_count')
            ->get()
            ->filter(function ($user) use ($toActorName) {
                return $this->chatManager->isActorType($toActorName, $user);
            });

        return $admins->first();
    }

    /**
     * Strategy: Round-robin based on last assignment.
     */
    protected function roundRobin(string $toActorName): ?Model
    {
        $actorConfig = config("chat.actors.{$toActorName}");
        if (!$actorConfig) {
            return null;
        }

        $modelClass = $actorConfig['model'];
        $table = config('chat.table_names.rooms', 'chat_rooms');

        // Find admin who was assigned a room the longest time ago
        $admins = $modelClass::select($modelClass::query()->getModel()->getTable() . '.*')
            ->selectRaw("(SELECT MAX(created_at) FROM {$table} WHERE created_by_type = ? AND created_by_id = {$modelClass::query()->getModel()->getTable()}.id) as last_assigned_at", [$modelClass])
            ->orderByRaw('last_assigned_at IS NULL DESC, last_assigned_at ASC')
            ->get()
            ->filter(function ($user) use ($toActorName) {
                return $this->chatManager->isActorType($toActorName, $user);
            });

        return $admins->first();
    }
}
