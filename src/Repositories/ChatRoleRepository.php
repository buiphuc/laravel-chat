<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Support\Collection;
use PhucBui\Chat\Contracts\Repositories\ChatRoleRepositoryInterface;
use PhucBui\Chat\Models\ChatRole;

class ChatRoleRepository extends BaseRepository implements ChatRoleRepositoryInterface
{
    public function __construct(ChatRole $model)
    {
        parent::__construct($model);
    }

    public function find(int $id): ?ChatRole
    {
        return $this->model->find($id);
    }

    public function findByName(string $name): ?ChatRole
    {
        return $this->model->where('name', $name)->first();
    }

    public function getDefault(): ?ChatRole
    {
        return $this->model->where('is_default', true)->first();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): ChatRole
    {
        return $this->model->create($data);
    }

    public function update(ChatRole|\Illuminate\Database\Eloquent\Model $role, array $data): ChatRole
    {
        $role->update($data);
        return $role->fresh();
    }
}
