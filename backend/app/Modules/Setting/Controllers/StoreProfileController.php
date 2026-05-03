<?php

namespace App\Modules\Setting\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Setting\Requests\UpdateStoreProfileRequest;
use App\Modules\Setting\Resources\StoreProfileResource;
use App\Modules\Setting\Services\StoreProfileService;
use Illuminate\Http\UploadedFile;

class StoreProfileController extends Controller
{
    public function __construct(
        protected StoreProfileService $storeProfileService
    ) {}

    public function edit()
    {
        return view('setting::store-profile', [
            'profile' => $this->storeProfileService->getProfile(),
        ]);
    }

    public function update(UpdateStoreProfileRequest $request)
    {
        $payload = $this->mergeUploadedAssetPaths($request, $request->validated());

        $profile = $this->storeProfileService->updateProfile(
            $payload,
            $request->user()
        );

        return ApiResponse::success(
            new StoreProfileResource($profile),
            'Store profile settings updated.'
        );
    }

    public function show()
    {
        return ApiResponse::success(
            new StoreProfileResource($this->storeProfileService->getProfile()),
            'Store profile fetched successfully.'
        );
    }

    public function updateApi(UpdateStoreProfileRequest $request)
    {
        $payload = $this->mergeUploadedAssetPaths($request, $request->validated());

        return ApiResponse::success(
            new StoreProfileResource($this->storeProfileService->updateProfile(
                $payload,
                $request->user()
            )),
            'Store profile updated successfully.'
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergeUploadedAssetPaths(UpdateStoreProfileRequest $request, array $payload): array
    {
        $uploadMap = [
            'logo' => 'logo_path',
            'logo_dark' => 'logo_dark_path',
            'favicon' => 'favicon_path',
            'social_share_image' => 'social_share_image_path',
            'seo_og_image' => 'seo_og_image_path',
        ];

        foreach ($uploadMap as $fileField => $pathField) {
            if (! $request->hasFile($fileField)) {
                continue;
            }

            /** @var UploadedFile|null $file */
            $file = $request->file($fileField);
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $storedPath = $file->store('branding', 'public');
            $payload[$pathField] = '/storage/'.$storedPath;
        }

        return $payload;
    }
}
