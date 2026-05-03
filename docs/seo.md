SEO Fields Explained
seo_title_template

Why important: Controls page <title>, which is one of the strongest SEO signals and the main blue link in Google results.
What to fill: A template that combines page title + store brand.
Example: {page_title} | Kaya Herbs
seo_meta_description

Why important: Helps search engines and users understand the page; often shown as snippet text in results.
What to fill: Clear summary of your store value, around 140-160 chars ideally.
Example: Shop natural herbs, wellness essentials, and daily health products with fast delivery across Bangladesh.
seo_meta_keywords

Why important: Minor/low impact for Google today, but can still help internal organization and some engines/tools.
What to fill: Comma-separated relevant terms, no stuffing.
Example: herbal products, wellness store, natural supplements, organic care, Bangladesh
seo_robots

Why important: Tells crawlers whether to index pages and follow links by default.
What to fill: Usually index,follow for public storefront.
Example: index,follow
Use noindex,follow only for pages you don’t want in search (staging/private sections).
seo_canonical_base_url

Why important: Helps avoid duplicate-content issues by normalizing canonical URLs.
What to fill: Your main production domain (HTTPS).
Example: https://kayaherbs.com
Not localhost, not staging URL.
seo_og_image_path

Why important: Default social preview image for Facebook/LinkedIn/WhatsApp when a page has no custom OG image.
What to fill: Upload a clean brand image (recommended 1200x630).
Example path after upload: /storage/branding/og-default.jpg
seo_og_image (upload input)

Why important: This is how you set/update seo_og_image_path from admin panel.
What to fill: Upload your default share image file.
Example file: kaya-og-default-1200x630.jpg
Recommended Starter Values (copy-ready)
seo_title_template: {page_title} | Kaya Herbs
seo_meta_description: Shop natural herbs, wellness essentials, and trusted health products with quick nationwide delivery.
seo_meta_keywords: herbal products, wellness, natural health, supplements, Bangladesh
seo_robots: index,follow
seo_canonical_base_url: https://kayaherbs.com
seo_og_image: upload a 1200x630 brand image
resulting seo_og_image_path: /storage/branding/og-default.jpg
If you want, I can also give you page-level examples (Home, Category, Product, Blog) so your defaults and per-page SEO stay consistent.

okey give me page level example

Perfect — here are practical page-level SEO examples for your ecommerce store.

1) Home Page
Page title ({page_title}): Natural Wellness Store
Final title (with template): Natural Wellness Store | Kaya Herbs
Meta description: Discover natural herbs, wellness essentials, and daily health products with fast delivery across Bangladesh.
Meta keywords: herbal store, wellness products, natural health, Bangladesh
Canonical URL: https://kayaherbs.com/
OG image: use default seo_og_image_path (unless you have a home-specific banner)
2) Category Page (e.g., Herbal Tea)
Page title: Herbal Tea
Final title: Herbal Tea | Kaya Herbs
Meta description: Shop premium herbal tea blends for digestion, stress relief, and daily wellness. Authentic products at fair prices.
Meta keywords: herbal tea, wellness tea, natural tea
Canonical URL: https://kayaherbs.com/category/herbal-tea
OG image: category image if available; else default OG image
3) Product Page (e.g., Tulsi Detox Tea 100g)
Page title: Tulsi Detox Tea 100g
Final title: Tulsi Detox Tea 100g | Kaya Herbs
Meta description: Buy Tulsi Detox Tea 100g made from natural ingredients. Supports immunity and digestion. Order online with quick delivery.
Meta keywords: tulsi tea, detox tea, herbal immunity tea
Canonical URL: https://kayaherbs.com/product/tulsi-detox-tea-100g
OG image: product main image (best for sharing)
4) Brand Page (if you have brand listings)
Page title: Himalaya Products
Final title: Himalaya Products | Kaya Herbs
Meta description: Explore original Himalaya wellness and personal care products available at Kaya Herbs.
Meta keywords: himalaya products, herbal brand, wellness brand
Canonical URL: https://kayaherbs.com/brand/himalaya
OG image: brand banner/logo image
5) Search Results Page
Page title: Search results for "detox"
Final title: Search results for "detox" | Kaya Herbs
Meta description: Browse search results for detox products at Kaya Herbs.
Meta keywords: optional or minimal
Canonical URL: https://kayaherbs.com/search?q=detox
Robots: often noindex,follow (recommended for internal search pages)
6) Cart / Checkout / Account Pages
Page title examples: Cart, Checkout, My Account
Final title examples: Cart | Kaya Herbs
Robots: noindex,nofollow (or at least noindex,follow) for private/utility pages
Canonical URL: page URL itself
Meta description: optional, short utility text
7) Blog Article / Content Page (if CMS exists)
Page title: 5 Herbs That Support Better Sleep
Final title: 5 Herbs That Support Better Sleep | Kaya Herbs
Meta description: Learn which herbs may support better sleep and how to use them safely in your daily routine.
Meta keywords: sleep herbs, natural sleep support, wellness tips
Canonical URL: https://kayaherbs.com/blog/herbs-for-better-sleep
OG image: article featured image
Quick rule for your system
If page has custom SEO -> use custom values.
If not -> fallback to your B3.3 defaults.
For title, render with template: replace {page_title} + {store_name}.
Keep titles ~50-60 chars, descriptions ~140-160 chars where possible.
If you want, I can next provide a small Laravel helper function to apply this fallback logic consistently for all pages.