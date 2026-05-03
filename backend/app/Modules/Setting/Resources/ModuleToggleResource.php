<?php

namespace App\Modules\Setting\Resources;

use App\Modules\Setting\Services\ModuleToggleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleToggleResource extends JsonResource
{
    /**
     * @return array<string, bool>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach (ModuleToggleService::MODULES as $module) {
            $data[$module] = (bool) ($this->resource[$module] ?? false);
        }

        return $data;
    }
}
