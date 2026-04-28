<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Certificate::query()->with('product:id,name,sku')->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->string('product_id')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($search): void {
                $inner->where('certificate_number', 'like', $search)->orWhere('standard', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 200);
        $certificates = $query->paginate($perPage);

        return response()->json([
            'data' => $certificates->items(),
            'meta' => [
                'current_page' => $certificates->currentPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
                'last_page' => $certificates->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $certificate = Certificate::query()->create($data);

        return response()->json(['data' => $certificate], 201);
    }

    public function show(Certificate $certificate): JsonResponse
    {
        $certificate->load('product:id,name,sku,company_id');

        return response()->json(['data' => $certificate]);
    }

    public function update(Request $request, Certificate $certificate): JsonResponse
    {
        $certificate->update($this->validatedData($request, true, $certificate->id));

        return response()->json(['data' => $certificate->fresh()]);
    }

    public function destroy(Certificate $certificate): JsonResponse
    {
        $certificate->delete();

        return response()->json(['message' => 'Certificate deleted.']);
    }

    private function validatedData(Request $request, bool $isUpdate = false, ?string $certificateId = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $certUnique = $isUpdate
            ? 'unique:certificates,certificate_number,'.$certificateId.',id'
            : 'unique:certificates,certificate_number';

        return $request->validate([
            'product_id' => [$required, 'exists:products,id'],
            'certificate_number' => [$required, 'string', 'max:255', $certUnique],
            'standard' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'file_url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
