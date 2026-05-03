<?php

namespace App\Modules\Setting\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Setting\Requests\UpdateModuleToggleRequest;
use App\Modules\Setting\Resources\ModuleToggleResource;
use App\Modules\Setting\Services\ModuleToggleService;

class ModuleToggleController extends Controller
{
    public function __construct(
        protected ModuleToggleService $moduleToggleService
    ) {}

    public function edit()
    {
        return view('setting::module-toggles', [
            'toggles' => $this->moduleToggleService->getToggles(),
        ]);
    }

    public function update(UpdateModuleToggleRequest $request)
    {
        $toggles = $this->moduleToggleService->updateToggles(
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new ModuleToggleResource($toggles),
            'Module toggles updated.'
        );
    }

    public function show()
    {
        return ApiResponse::success(
            new ModuleToggleResource($this->moduleToggleService->getToggles()),
            'Module toggles fetched successfully.'
        );
    }

    public function updateApi(UpdateModuleToggleRequest $request)
    {
        return ApiResponse::success(
            new ModuleToggleResource($this->moduleToggleService->updateToggles(
                $request->validated(),
                $request->user()
            )),
            'Module toggles updated successfully.'
        );
    }
}
