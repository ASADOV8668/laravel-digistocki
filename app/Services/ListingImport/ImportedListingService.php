<?php

namespace App\Services\ListingImport;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ListingAttributeOptionRegistrar;
use App\Services\ListingMediaService;
use App\Services\ListingRules;
use App\Support\ListingTitle;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportedListingService
{
    public function create(User $user, array $row, ?UploadedFile $upload, ?int $mediaId, ?string $date, ListingRules $rules, ImageService $imageService, ListingMediaService $mediaService): Listing
    {
        $brand = Brand::query()->whereKey($row['brand_id'] ?? null)->where('is_active', true)->where('can_post_listings', true)->firstOrFail();
        $model = PhoneModel::query()->whereKey($row['phone_model_id'] ?? null)->where('brand_id', $brand->id)->where('is_active', true)->firstOrFail();
        $attributes = (array) ($row['attributes'] ?? []);
        $title = ListingTitle::generate($brand->id, $model->id, $attributes, partNumber: $row['part_number'] ?? null)
            ?: trim($brand->name.' '.$model->name);
        $autoPublish = $user->storefront?->allowsFreeListing() ?? false;
        $listingDate = $date && Carbon::hasFormat($date, 'Y-m-d') ? Carbon::parse($date) : now();
        $createdMedia = null;

        try {
            return DB::transaction(function () use ($user, $row, $brand, $model, $attributes, $title, $autoPublish, $listingDate, $upload, $mediaId, $rules, $imageService, $mediaService, &$createdMedia): Listing {
                $listing = Listing::create([
                    'user_id' => $user->id,
                    'brand_id' => $brand->id,
                    'phone_model_id' => $model->id,
                    'province_id' => $user->storefront?->province_id,
                    'city_id' => $user->storefront?->city_id,
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
                    'description' => $row['description'] ?? null,
                    'price' => ($row['price_on_request'] ?? false) ? null : (int) ($row['price'] ?? 0),
                    'price_on_request' => (bool) ($row['price_on_request'] ?? false),
                    'status' => $autoPublish ? 'approved' : 'pending',
                    'published_at' => $autoPublish ? $listingDate : null,
                    'expires_at' => $autoPublish ? $rules->expiryDate($listingDate) : null,
                ]);
                $listing->forceFill(['created_at' => $listingDate, 'updated_at' => $listingDate])->save();
                $this->saveAttributes($listing, $model, $attributes);

                $media = null;
                if ($mediaId) {
                    $media = $mediaService->findOwned($user, $mediaId);
                }
                if (! $media && $upload) {
                    $media = $mediaService->createFromUpload($user, $upload, $imageService);
                    $createdMedia = $media;
                }
                if (! $media instanceof Media) {
                    throw new \RuntimeException('برای هر ردیف حداقل یک تصویر انتخاب کنید.');
                }
                $mediaService->attachToListing($listing, $media, true, 0);

                return $listing;
            });
        } catch (\Throwable $exception) {
            if ($createdMedia) {
                $mediaService->deleteIfUnused($createdMedia);
            }
            throw $exception;
        }
    }

    private function saveAttributes(Listing $listing, PhoneModel $model, array $values): void
    {
        $attributes = Attribute::query()->where('is_active', true)->visibleForModel($model->id)->get()->keyBy('id');
        $now = now();
        $payloads = [];
        foreach ($values as $id => $value) {
            $attribute = $attributes->get((int) $id);
            if (! $attribute || $value === null || $value === '' || (is_array($value) && $value === [])) {
                continue;
            }
            app(ListingAttributeOptionRegistrar::class)->remember($attribute, $value);
            $payload = ['listing_id' => $listing->id, 'attribute_id' => $attribute->id, 'value_string' => null, 'value_integer' => null, 'value_decimal' => null, 'value_boolean' => null, 'value_json' => null, 'created_at' => $now, 'updated_at' => $now];
            match ($attribute->type) {
                AttributeType::Integer => $payload['value_integer'] = (int) $value,
                AttributeType::Decimal => $payload['value_decimal'] = (float) $value,
                AttributeType::Boolean => $payload['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN),
                AttributeType::MultiSelect => $payload['value_json'] = json_encode((array) $value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                default => $payload['value_string'] = (string) $value,
            };
            $payloads[] = $payload;
        }
        if ($payloads !== []) {
            ListingAttributeValue::query()->insert($payloads);
        }
    }
}
