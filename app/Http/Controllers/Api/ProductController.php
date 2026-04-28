<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('company:id,name')->latest();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->string('company_id')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', $search)->orWhere('sku', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 200);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data = $this->withImageUrl($request, $data);

        $product = Product::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'published',
        ]);

        return response()->json(['data' => $product], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('company:id,name,email');

        return response()->json(['data' => $product]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validatedData($request, true, $product->id);
        $data = $this->withImageUrl($request, $data);
        $product->update($data);

        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    private function validatedData(Request $request, bool $isUpdate = false, ?string $productId = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $skuUnique = $isUpdate
            ? 'unique:products,sku,'.$productId.',id'
            : 'unique:products,sku';

        return $request->validate([
            'company_id' => [$required, 'exists:companies,id'],
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => [$required, 'string', 'max:255', $skuUnique],
            'image_url' => ['nullable', 'url', 'max:255'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'string', 'max:24'],
        ]);
    }

    private function withImageUrl(Request $request, array $data): array
    {
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('products', 'public');
            $data['image_url'] = url('/storage/'.$path);
        }

        unset($data['image_file']);

        return $data;
    }
}
