<?php

namespace App\Modules\User\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }
}
