<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateProductAction;
use App\Domain\Catalog\Actions\DeleteProductAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Queries\ProductFormOptionsQuery;
use App\Domain\Catalog\Queries\ProductIndexQuery;
use App\Domain\Catalog\Queries\ProductVariantOptionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Http\Requests\Admin\Catalog\UpdateProductRequest;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function index(
        Request $request,
        ProductIndexQuery $query,
    ): Response {
        $this->authorize(
            'viewAny',
            Product::class,
        );

        $search = $request
            ->string('search')
            ->trim()
            ->toString();

        $statusValue = $request
            ->string('status')
            ->trim()
            ->toString();

        $status = $statusValue === ''
            ? null
            : ProductStatus::tryFrom(
                $statusValue,
            );

        $brandId = $request->filled('brand_id')
            ? $request->integer('brand_id')
            : null;

        $featured = match (
            $request->query('featured')
        ) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $products = $query->paginate(
            search: $search === ''
                ? null
                : $search,
            status: $status,
            brandId: $brandId,
            isFeatured: $featured,
        );

        return Inertia::render(
            'Admin/Catalog/Products/Index',
            [
                'products' => $products->through(
                    static fn (
                        Product $product,
                    ): array => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'status' => $product->status->value,
                        'is_featured' => $product->is_featured,
                        'position' => $product->position,
                        'published_at' => $product
                            ->published_at
                            ?->toIso8601String(),

                        'brand' => $product->brand === null
                                ? null
                                : [
                                    'id' => $product
                                        ->brand
                                        ->id,
                                    'name' => $product
                                        ->brand
                                        ->name,
                                ],

                        'categories_count' => $product
                            ->categories_count,

                        'variants_count' => $product
                            ->variants_count,
                    ],
                ),

                'filters' => [
                    'search' => $search,
                    'status' => $statusValue,
                    'brand_id' => $brandId,

                    'featured' => $request->query(
                        'featured',
                        '',
                    ),
                ],

                'statuses' => $this->statuses(),
            ],
        );
    }

    public function create(
        ProductFormOptionsQuery $options,
    ): Response {
        $this->authorize(
            'create',
            Product::class,
        );

        return Inertia::render(
            'Admin/Catalog/Products/Create',
            [
                ...$options->get(),

                'statuses' => $this->statuses(),
            ],
        );
    }

    public function store(
        StoreProductRequest $request,
        CreateProductAction $action,
    ): RedirectResponse {
        $this->authorize(
            'create',
            Product::class,
        );

        $product = $action->execute(
            $request->toData(),
        );

        return redirect()
            ->route(
                'admin.products.edit',
                $product,
            )
            ->with(
                'success',
                'Product created successfully.',
            );
    }

    public function edit(
        Product $product,
        ProductVariantOptionsQuery $variantOptions,
        ProductFormOptionsQuery $options,
    ): Response {
        $this->authorize(
            'update',
            $product,
        );

        $product->load([
            'brand:id,name',
            'categories:id,name',

            'images' => static function ($query): void {
                $query
                    ->orderBy('position')
                    ->orderBy('id');
            },

            'video',

            'variants' => static function ($query): void {
                $query
                    ->with([
                        'attributeValues.attribute:id,name',
                    ])
                    ->orderBy('position')
                    ->orderBy('id');
            },
        ]);

        $attributes = $variantOptions->get();

        return Inertia::render(
            'Admin/Catalog/Products/Edit',
            [
                'product' => [
                    'id' => $product->id,
                    'brand_id' => $product->brand_id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'short_description' => $product->short_description,
                    'description' => $product->description,
                    'status' => $product->status->value,
                    'is_featured' => $product->is_featured,
                    'position' => $product->position,
                    'published_at' => $product
                        ->published_at
                        ?->format(
                            'Y-m-d\TH:i',
                        ),
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,

                    'category_ids' => $product
                        ->categories
                        ->pluck('id')
                        ->map(
                            static fn (
                                mixed $id,
                            ): int => (int) $id,
                        )
                        ->values()
                        ->all(),
                ],
                'images' => $product
                    ->images
                    ->map(
                        static fn (
                            ProductImage $image,
                        ): array => [
                            'id' => $image->id,

                            'path' => $image->path,

                            'url' => asset(
                                'storage/'
                                .$image->path,
                            ),

                            'original_name' => $image->original_name,

                            'mime_type' => $image->mime_type,

                            'file_size' => $image->file_size,

                            'width' => $image->width,

                            'height' => $image->height,

                            'alt_text' => $image->alt_text,

                            'position' => $image->position,

                            'is_primary' => $image->is_primary,
                        ],
                    )
                    ->values()
                    ->all(),

                'video' => $product->video === null
                        ? null
                        : [
                            'id' => $product->video->id,

                            'type' => $product
                                ->video
                                ->type
                                ->value,

                            'path' => $product
                                ->video
                                ->path,

                            'url' => $product
                                ->video
                                ->url,

                            'file_url' => $product
                                ->video
                                ->path !== null
                                    ? asset(
                                        'storage/'
                                        .$product
                                            ->video
                                            ->path,
                                    )
                                    : null,

                            'title' => $product
                                ->video
                                ->title,

                            'original_name' => $product
                                ->video
                                ->original_name,

                            'mime_type' => $product
                                ->video
                                ->mime_type,

                            'file_size' => $product
                                ->video
                                ->file_size,
                        ],

                'variants' => $product
                    ->variants
                    ->map(
                        static fn (
                            ProductVariant $variant,
                        ): array => [
                            'id' => $variant->id,

                            'sku' => $variant->sku,

                            'name' => $variant->name,

                            'price' => $variant->price,

                            'compare_at_price' => $variant
                                ->compare_at_price,

                            'cost_price' => $variant
                                ->cost_price,

                            'barcode' => $variant->barcode,

                            'position' => $variant
                                ->position,

                            'is_active' => $variant
                                ->is_active,

                            'is_default' => $variant
                                ->is_default,

                            'weight' => $variant->weight,

                            'attribute_values' => $variant
                                ->attributeValues
                                ->map(
                                    static fn (
                                        AttributeValue $value,
                                    ): array => [
                                        'id' => $value->id,

                                        'name' => $value->name,

                                        'attribute_id' => $value
                                            ->attribute_id,

                                        'attribute_name' => $value
                                            ->attribute
                                            ->name,
                                    ],
                                )
                                ->values()
                                ->all(),
                        ],
                    )
                    ->values()
                    ->all(),

                'variantAttributes' => $attributes
                    ->map(
                        static fn (
                            ProductAttribute $attribute,
                        ): array => [
                            'id' => $attribute->id,

                            'name' => $attribute->name,

                            'is_active' => $attribute
                                ->is_active,

                            'values' => $attribute
                                ->values
                                ->map(
                                    static fn (
                                        AttributeValue $value,
                                    ): array => [
                                        'id' => $value->id,

                                        'name' => $value->name,

                                        'is_active' => $value
                                            ->is_active,
                                    ],
                                )
                                ->values()
                                ->all(),
                        ],
                    )
                    ->values()
                    ->all(),

                ...$options->get(),

                'statuses' => $this->statuses(),
            ],
        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        UpdateProductAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $product,
        );

        $action->execute(
            $product,
            $request->toData(),
        );

        return redirect()
            ->route(
                'admin.products.edit',
                $product,
            )
            ->with(
                'success',
                'Product updated successfully.',
            );
    }

    public function destroy(
        Product $product,
        DeleteProductAction $action,
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $product,
        );

        $action->execute(
            $product,
        );

        return redirect()
            ->route(
                'admin.products.index',
            )
            ->with(
                'success',
                'Product deleted successfully.',
            );
    }

    /**
     * @return list<array{
     *     value: string,
     *     label: string
     * }>
     */
    private function statuses(): array
    {
        return array_map(
            static fn (
                ProductStatus $status,
            ): array => [
                'value' => $status->value,

                'label' => match ($status) {
                    ProductStatus::Draft => 'Draft',

                    ProductStatus::Published => 'Published',

                    ProductStatus::Archived => 'Archived',
                },
            ],
            ProductStatus::cases(),
        );
    }
}
