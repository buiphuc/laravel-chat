<?php

namespace PhucBui\Chat\DTOs;

class MessageData
{
    public function __construct(
        public readonly string $type = 'text',
        public readonly ?string $body = null,
        public readonly ?array $metadata = null,
        public readonly ?int $parentId = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            type: $data['type'] ?? 'text',
            body: $data['body'] ?? null,
            metadata: $data['metadata'] ?? null,
            parentId: $data['parent_id'] ?? null,
        );
    }
}
