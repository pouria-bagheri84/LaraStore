<?php

namespace App\Models;

use App\Enums\ProductStatusEnum;
use App\Enums\VendorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100);

        $this->addMediaConversion('small')
            ->width(480);

        $this->addMediaConversion('large')
            ->width(1200);
    }

    public function scopeForVendor(Builder $query): Builder
    {
        return $query->where('created_by', auth()->id());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('products.status', ProductStatusEnum::PUBLISHED->value);
    }

    public function scopeForWebsite(Builder $query): Builder
    {
        return $query->published()->vendorApproved();
    }

    public function scopeVendorApproved(Builder $query): Builder
    {
        return $query->join('vendors', 'vendors.user_id', '=', 'products.created_by')
            ->where('vendors.status', VendorStatusEnum::APPROVED->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variationTypes(): HasMany
    {
        return $this->hasMany(VariationType::class);
    }

    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(
            VariationTypeOption::class, // Target Model
            VariationType::class, // Intermediate Model
            'product_id', // Foreign Key on VariationType Table
            'variation_type_id', // Foreign Key on Option Table
            'id', // Local Key on Product Table
            'id' // Local Key on VariationType Table
        );
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
    }

    public function getPriceForOptions($optionIds = [])
    {
        $optionIds = array_values($optionIds);
        sort($optionIds);

        foreach ($this->variations as $variation) {
            $a = $variation->variation_type_option_ids;
            sort($a);

            if ($optionIds == $a){
                return $variation->price !== null ? $variation->price : $this->price;
            }
        }
        return $this->price;
    }

    public function getImageForOptions(array $optionIds = null)
    {
        if ($optionIds){
            $optionIds = array_values($optionIds);
            sort($optionIds);
            $options = VariationTypeOption::query()->whereIn('id', $optionIds)->get();

            foreach ($options as $option) {
                $image = $option->getFirstMediaUrl('images', 'small');
                if ($image) {
                    return $image;
                }
            }
        }
        return $this->getFirstMediaUrl('images', 'small');
    }

    public function getPriceForFirstOptions(): float
    {
        $firstOptions = $this->getFirstOptionsMap();

        if ($firstOptions){
            return $this->getPriceForOptions($firstOptions);
        }
        return $this->price;
    }

    public function getFirstImageUrl($collectionName = 'images', $conversion = 'small'): string
    {
        if ($this->options->count() > 0){
            foreach ($this->options as $option) {
                $imageUrl = $option->getFirstMediaUrl($collectionName, $conversion);
                if ($imageUrl) {
                    return $imageUrl;
                }
            }
        }
        return $this->getFirstMediaUrl($collectionName, $conversion);
    }

    public function getImages(): \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection
    {
        if ($this->options->count() > 0){
            foreach ($this->options as $option) {
                /** @var VariationTypeOption $option */
                $images = $option->getMedia('images');
                if ($images){
                    return $images;
                }
            }
        }
        return $this->getMedia('images');
    }

    public function getFirstOptionsMap(): array
    {
        return $this->variationTypes
            ->mapWithKeys(fn($type) => [$type->id => $type->options[0]?->id])
            ->toArray();
    }
}
