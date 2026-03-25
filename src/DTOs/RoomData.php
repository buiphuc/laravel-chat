<?php

namespace PhucBui\Chat\DTOs;

class RoomData
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $maxMembers = null,
        public readonly ?array $metadata = null,
        public readonly array $participantIds = [],
        public readonly ?string $participantType = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            name: $data['name'] ?? null,
            maxMembers: $data['max_members'] ?? null,
            metadata: $data['metadata'] ?? null,
            participantIds: $data['participant_ids'] ?? [],
            participantType: $data['participant_type'] ?? null,
        );
    }
}
