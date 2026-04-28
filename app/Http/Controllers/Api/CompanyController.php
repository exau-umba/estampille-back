<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()->latest();

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('registration_number', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 200);
        $companies = $query->paginate($perPage);

        return response()->json([
            'data' => $companies->items(),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
                'last_page' => $companies->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $company = Company::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json(['data' => $company], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $company]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $company->update($this->validatedData($request, true));

        return response()->json(['data' => $company->fresh()]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(['message' => 'Company deleted.']);
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'province_code' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:24'],
        ]);
    }
}
