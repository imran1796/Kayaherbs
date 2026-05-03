<?php

namespace App\Modules\Setting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'legal_name' => $this->resource['legal_name'],
            'support_email' => $this->resource['support_email'],
            'support_phone' => $this->resource['support_phone'],
            'address_line_1' => $this->resource['address_line_1'],
            'address_line_2' => $this->resource['address_line_2'],
            'city' => $this->resource['city'],
            'state' => $this->resource['state'],
            'postal_code' => $this->resource['postal_code'],
            'country' => $this->resource['country'],
            'currency' => $this->resource['currency'],
            'timezone' => $this->resource['timezone'],
            'locale' => $this->resource['locale'],
            'website_url' => $this->resource['website_url'],
            'primary_color' => $this->resource['primary_color'],
            'secondary_color' => $this->resource['secondary_color'],
            'logo_path' => $this->resource['logo_path'],
            'logo_dark_path' => $this->resource['logo_dark_path'],
            'favicon_path' => $this->resource['favicon_path'],
            'social_share_image_path' => $this->resource['social_share_image_path'],
            'seo_title_template' => $this->resource['seo_title_template'],
            'seo_meta_description' => $this->resource['seo_meta_description'],
            'seo_meta_keywords' => $this->resource['seo_meta_keywords'],
            'seo_robots' => $this->resource['seo_robots'],
            'seo_canonical_base_url' => $this->resource['seo_canonical_base_url'],
            'seo_og_image_path' => $this->resource['seo_og_image_path'],
            'privacy_policy_title' => $this->resource['privacy_policy_title'],
            'privacy_policy_content' => $this->resource['privacy_policy_content'],
            'terms_conditions_title' => $this->resource['terms_conditions_title'],
            'terms_conditions_content' => $this->resource['terms_conditions_content'],
            'refund_policy_title' => $this->resource['refund_policy_title'],
            'refund_policy_content' => $this->resource['refund_policy_content'],
            'shipping_policy_title' => $this->resource['shipping_policy_title'],
            'shipping_policy_content' => $this->resource['shipping_policy_content'],
        ];
    }
}
