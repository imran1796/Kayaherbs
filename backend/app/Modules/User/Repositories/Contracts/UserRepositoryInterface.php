<?php

namespace App\Modules\User\Repositories\Contracts;

use App\Core\Repositories\Contracts\BaseRepositoryInterface;
use App\Models\User;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
}
