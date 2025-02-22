<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\VariationType;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService
{

    private ?array $cachedCartItems = null;
    protected const COOKIE_NAME = 'cartItems';
    protected const COOKIE_LIFETIME = 60 * 24 * 30;

    public function addItemToCart(Product $product, int $quantity = 1, $optionIds = null)
    {
        if ($optionIds === null) {
            $optionIds = $product->variationTypes
                ->mapWithKeys(fn(VariationType $type) => [$type->id => $type->options[0]?->id])
                ->toArray();
        }

        $price = $product->getPriceForOptions($optionIds);

        if (Auth::check()) {
            $this->saveItemToDatabase($product->id, $quantity, $price, $optionIds);
        } else {
            $this->saveItemToCookies($product->id, $quantity, $price, $optionIds);
        }
    }

    public function updateItemQuantity(int $productId, int $quantity, $optionIds = null)
    {
        if (Auth::check()) {
            $this->updateItemQuantityInDatabase($productId, $quantity, $optionIds);
        } else {
            $this->updateItemQuantityInCookies($productId, $quantity, $optionIds);
        }
    }

    public function getCartItems()
    {
        try {
            if ($this->cachedCartItems === null) {
                if (Auth::check()) {
                    $cartItems = $this->getCartItemsFromDatabase();
                } else {
                    $cartItems = $this->getCartItemsFromCookies();
                }

                if (empty($cartItems)) {
                    Log::error("Cart items are empty.");
                    return [];
                }

                $productIds = collect($cartItems)->pluck('product_id')->toArray();

                if (empty($productIds)) {
                    Log::error("Product IDs are empty.");
                    return [];
                }

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->with('user.vendor')
                    ->forWebsite()
                    ->get()
                    ->keyBy('id');

                if ($products->isEmpty()) {
                    Log::error("No products found for given IDs: " . json_encode($productIds));
                    return [];
                }

                $cartItemData = [];
                foreach ($cartItems as $cartItem) {
                    if (!isset($products[$cartItem['product_id']])) {
                        Log::error("Product not found: " . $cartItem['product_id']);
                        continue;
                    }

                    $product = $products[$cartItem['product_id']];

                    if (!isset($cartItem['option_ids']) || !is_array($cartItem['option_ids'])) {
                        Log::error("Invalid option IDs: " . json_encode($cartItem['option_ids']));
                        continue;
                    }

                    $options = VariationTypeOption::with('variationType')
                        ->whereIn('id', $cartItem['option_ids'])
                        ->get()
                        ->keyBy('id');

                    $imageUrl = null;
                    $optionInfo = [];

                    foreach ($cartItem['option_ids'] as $option_id) {
                        if (!isset($options[$option_id])) {
                            Log::error("Option not found: " . $option_id);
                            continue;
                        }

                        $option = $options[$option_id];

                        if (!$imageUrl) {
                            $imageUrl = $option->getFirstMediaUrl('images', 'small');
                        }

                        $optionInfo[] = [
                            'id' => $option_id,
                            'name' => $option->name,
                            'type' => [
                                'id' => $option->variationType->id,
                                'name' => $option->variationType->name,
                            ]
                        ];
                    }

                    $cartItemData[] = [
                        'id' => $cartItem['id'],
                        'product_id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'price' => $cartItem['price'],
                        'quantity' => $cartItem['quantity'],
                        'option_ids' => $cartItem['option_ids'],
                        'options' => $optionInfo,
                        'image' => $imageUrl ?: $product->getFirstMediaUrl('images', 'small'),
                        'user' => [
                            'id' => $product->created_by,
                            'name' => $product->user->vendor->store_name ?? 'Unknown Vendor',
                        ]
                    ];
                }

                $this->cachedCartItems = $cartItemData;
            }
            return $this->cachedCartItems;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
        }

        return [];
    }


    public function removeItemFromCart(int $productId, $optionIds = null)
    {
        if (Auth::check()) {
            $this->removeItemFromDatabase($productId, $optionIds);
        } else {
            $this->removeItemFromCookies($productId, $optionIds);
        }
    }

    public function getTotalQuantity()
    {
        $totalQuantity = 0;
        if (Auth::check()) {
            foreach ($this->getCartItemsFromDatabase() as $item) {
                $totalQuantity += $item['quantity'];
            }
        } else {
            foreach ($this->getCartItemsFromCookies() as $cartItem) {
                $totalQuantity += $cartItem['quantity'];
            }
        }
        return $totalQuantity;
    }

    public function getTotalPrice()
    {
        $totalPrice = 0;
        foreach ($this->getCartItems() as $cartItem) {
            $totalPrice += $cartItem['quantity'] * $cartItem['price'];
        }

        return $totalPrice;
    }

    protected function updateItemQuantityInDatabase(int $productId, int $quantity, array $optionIds)
    {
        $userId = Auth::id();

        $cartItem = CartItem::all()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', $optionIds)
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $quantity
            ]);
        }
    }

    protected function updateItemQuantityInCookies(int $productId, int $quantity, array $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies();

        ksort($optionIds);

        $itemKey = $productId . '_' . md5(serialize($optionIds));

        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] = $quantity;
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    protected function saveItemToDatabase(int $productId, int $quantity, $price, array $optionIds)
    {
        $userId = Auth::id();
        ksort($optionIds);

        $cartItem = CartItem::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', json_encode($optionIds))
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => DB::raw('quantity + ' . $quantity),
            ]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'variation_type_option_ids' => $optionIds
            ]);
        }
    }

    protected function saveItemToCookies(int $productId, int $quantity, $price, array $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies() ?? [];

        ksort($optionIds);
        $itemKey = $productId . '_' . md5(serialize($optionIds));

        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] += $quantity;
        } else {
            $cartItems[$itemKey] = [
                'id' => \Str::uuid(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'option_ids' => $optionIds
            ];
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems, JSON_UNESCAPED_UNICODE), self::COOKIE_LIFETIME);
    }

    protected function removeItemFromDatabase(int $productId, array $optionIds)
    {
        $userId = Auth::id();
        ksort($optionIds);

        $cartItem = CartItem::all()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', $optionIds)
            ->first();

        $cartItem->delete();
    }

    protected function removeItemFromCookies(int $productId, array $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies();

        ksort($optionIds);

        $cartKey = $productId . '_' . md5(serialize($optionIds));

        unset($cartItems[$cartKey]);

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    protected function getCartItemsFromCookies()
    {
        return json_decode(Cookie::get(self::COOKIE_NAME, '[]'), true);
    }

    protected function getCartItemsFromDatabase()
    {
        $userId = Auth::id();

        $cartItems = CartItem::query()
            ->where('user_id', $userId)
            ->get()
            ->map(function ($cartItem) {
                return [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'option_ids' => $cartItem->variation_type_option_ids,
                ];
            })
            ->toArray();

        return $cartItems;
    }

    public function getCartItemsGrouped()
    {
        $cartItems = $this->getCartItems();

        return collect($cartItems)
            ->groupBy(fn($item) => $item['user']['id'])
            ->map(fn($items, $userId) => [
                'user' => $items->first()['user'],
                'items' => $items->toArray(),
                'totalQuantity' => $items->sum('quantity'),
                'totalPrice' => $items->sum(fn($item) => $item['price'] * $item['quantity']),
            ])
            ->toArray();
    }

    public function moveCartItemsToDatabase($userId)
    {
        $cartItems = $this->getCartItemsFromCookies();

        foreach ($cartItems as $itemKey => $cartItem) {
            $existingCartItem = CartItem::query()
                ->where('user_id', $userId)
                ->where('product_id', $cartItem['product_id'])
                ->where('variation_type_option_ids', json_encode($cartItem['option_ids']))
                ->first();

            if ($existingCartItem) {
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $cartItem['quantity'],
                    'price' => $cartItem['price'],
                ]);
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'variation_type_option_ids' => $cartItem['option_ids'],
                ]);
            }
        }

        Cookie::queue(self::COOKIE_NAME, '', -1);
    }
}
