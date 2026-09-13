<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Domain\Catalog\Actions\CreateAttributeValueAction;
use App\Domain\Catalog\Actions\DeleteAttributeValueAction;
use App\Domain\Catalog\Actions\UpdateAttributeValueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\StoreAttributeValueRequest;
use App\Http\Requests\Admin\Catalog\UpdateAttributeValueRequest;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Http\RedirectResponse;

final class AttributeValueController extends Controller
{
    public function store(
        StoreAttributeValueRequest $request,
        ProductAttribute $attribute,
        CreateAttributeValueAction $action,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $attribute,
        );

        $action->execute(
            $attribute,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Attribute value added successfully.',
        );
    }

    public function update(
        UpdateAttributeValueRequest $request,
        ProductAttribute $attribute,
        AttributeValue $value,
        UpdateAttributeValueAction $action,
    ): RedirectResponse {
        $this->ensureValueBelongsToAttribute(
            $attribute,
            $value,
        );

        $this->authorize(
            'update',
            $attribute,
        );

        $action->execute(
            $value,
            $request->toData(),
        );

        return back()->with(
            'success',
            'Attribute value updated successfully.',
        );
    }

    public function destroy(
        ProductAttribute $attribute,
        AttributeValue $value,
        DeleteAttributeValueAction $action,
    ): RedirectResponse {
        $this->ensureValueBelongsToAttribute(
            $attribute,
            $value,
        );

        $this->authorize(
            'delete',
            $attribute,
        );

        $action->execute($value);

        return back()->with(
            'success',
            'Attribute value deleted successfully.',
        );
    }

    private function ensureValueBelongsToAttribute(
        ProductAttribute $attribute,
        AttributeValue $value,
    ): void {
        abort_unless(
            $value->attribute_id
                === $attribute->id,
            404,
        );
    }
}
