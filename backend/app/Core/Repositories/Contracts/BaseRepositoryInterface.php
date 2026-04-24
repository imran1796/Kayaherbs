<?php

namespace App\Core\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    public function create(array $data): Model;

    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
