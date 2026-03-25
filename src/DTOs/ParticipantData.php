<?php

namespace PhucBui\Chat\DTOs;

class ParticipantData
{
    public function __construct(
        public readonly string $actorType,
        public readonly int $actorId,
        public readonly ?string $roleName = 'member',
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            actorType: $data['actor_type'],
            actorId: $data['actor_id'],
            roleName: $data['role_name'] ?? 'member',
        );
    }
}
