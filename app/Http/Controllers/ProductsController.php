<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductsController extends Controller
{
    /**
     * Show the overview of all products.
     */
    public function index(): Response
    {
        return Inertia::render('products/Index', [
            'products' => Product::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): Response
    {
        return Inertia::render('products/Create', [
            'vatPercentages' => Product::VAT_PERCENTAGES,
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create([
            ...$request->productAttributes(),
            'image_path' => $request->hasFile('image') ? $this->storeImage($request) : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product aangemaakt.')]);

        return to_route('products.index');
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('products/Edit', [
            'product' => $product,
            'vatPercentages' => Product::VAT_PERCENTAGES,
        ]);
    }

    /**
     * Update a product, replacing its image when a new one is uploaded.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->productAttributes());

        $replacedImagePath = null;

        if ($request->hasFile('image')) {
            $replacedImagePath = $product->image_path;
            $product->image_path = $this->storeImage($request);
        }

        $product->save();

        if ($replacedImagePath !== null) {
            Storage::disk('public')->delete($replacedImagePath);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product bijgewerkt.')]);

        return to_route('products.index');
    }

    /**
     * Delete a product and its image.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        if ($product->image_path !== null) {
            Storage::disk('public')->delete($product->image_path);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product verwijderd.')]);

        return to_route('products.index');
    }

    /**
     * Store the uploaded product image and return its path.
     *
     * @throws ValidationException
     */
    private function storeImage(ProductRequest $request): string
    {
        $imagePath = $request->file('image')->store('products', 'public');

        if ($imagePath === false) {
            throw ValidationException::withMessages([
                'image' => 'De afbeelding kon niet worden opgeslagen. Probeer het opnieuw.',
            ]);
        }

        return $imagePath;
    }
}
