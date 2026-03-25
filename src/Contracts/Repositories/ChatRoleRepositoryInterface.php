<?php

namespace PhucBui\Chat\Contracts\Repositories;

use PhucBui\Chat\Models\ChatRole;

interface ChatRoleRepositoryInterface
{
    public function find(int $id): ?ChatRole;

    public function findByName(string $name): ?ChatRole;

    public function getDefault(): ?ChatRole;

    public function all(): \Illuminate\Support\Collection;

    public function create(array $data): ChatRole;

    public function update(ChatRole $role, array $data): ChatRole;
}
