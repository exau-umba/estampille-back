<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQrBatchRequest;
use App\Jobs\GenerateQrBatchJob;
use App\Models\Certificate;
use App\Models\Product;
use App\Models\QrBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrBatchController extends Controller
{
    private const FRONT_VERIFY_URL_DEFAULT = 'http://localhost:5173/verify';

    public function index(Request $request): JsonResponse
    {
        $query = QrBatch::query()
            ->with(['product:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($search): void {
                $inner->where('id', 'like', $search)->orWhere('product_name', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 200);
        $batches = $query->paginate($perPage);

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    public function store(StoreQrBatchRequest $request): JsonResponse
    {
        /** @var Product $product */
        $product = Product::query()
            ->where('id', $request->string('product_id')->toString())
            ->where('company_id', $request->string('company_id')->toString())
            ->firstOrFail();

        /** @var Certificate $certificate */
        $certificate = Certificate::query()
            ->where('id', $request->string('certificate_id')->toString())
            ->where('product_id', $product->id)
            ->firstOrFail();

        $batch = QrBatch::query()->create([
            'product_name' => $request->string('product_name')->toString() ?: $product->name,
            'company_id' => $request->string('company_id')->toString(),
            'product_id' => $request->string('product_id')->toString(),
            'certificate_id' => $request->string('certificate_id')->toString(),
            'prefix' => 'AUTO',
            'quantity' => $request->integer('quantity'),
            'status' => 'pending',
        ]);

        GenerateQrBatchJob::dispatch(
            batchId: $batch->id,
            expiresAt: $request->input('expires_at')
        )->onQueue('default');

        return response()->json([
            'message' => 'Batch queued for generation.',
            'data' => [
                'id' => $batch->id,
                'product_name' => $batch->product_name,
                'prefix' => $batch->prefix,
                'quantity' => $batch->quantity,
                'status' => $batch->status,
                'created_at' => $batch->created_at,
            ],
        ], 202);
    }

    public function show(QrBatch $batch): JsonResponse
    {
        $batch->load(['company:id,name', 'product:id,name,sku', 'certificate:id,certificate_number,standard,expires_at']);

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'product_name' => $batch->product_name,
                'company' => $batch->company,
                'product' => $batch->product,
                'certificate' => $batch->certificate,
                'prefix' => $batch->prefix,
                'quantity' => $batch->quantity,
                'total_generated' => $batch->total_generated,
                'status' => $batch->status,
                'started_at' => $batch->started_at,
                'completed_at' => $batch->completed_at,
                'failed_at' => $batch->failed_at,
                'failure_reason' => $batch->failure_reason,
                'created_at' => $batch->created_at,
            ],
        ]);
    }

    public function codes(QrBatch $batch, Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 50), 1), 500);
        $query = $batch->codes()->orderBy('serial');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($search): void {
                $inner->where('code', 'like', $search)->orWhere('id', 'like', $search);
            });
        }

        if ($request->filled('serial_from')) {
            $query->where('serial', '>=', (int) $request->input('serial_from'));
        }

        if ($request->filled('serial_to')) {
            $query->where('serial', '<=', (int) $request->input('serial_to'));
        }

        $codes = $query->paginate($perPage, ['id', 'serial', 'code', 'verification_token', 'status', 'expires_at', 'revoked_at', 'revocation_reason'])
            ->through(function ($code): array {
                return [
                    'id' => $code->id,
                    'serial' => $code->serial,
                    'code' => $code->code,
                    'status' => $code->status,
                    'expires_at' => $code->expires_at,
                    'revoked_at' => $code->revoked_at,
                    'revocation_reason' => $code->revocation_reason,
                    'verification_token' => $code->verification_token,
                    'verification_url' => $this->verificationUrl((string) $code->verification_token),
                ];
            });

        return response()->json([
            'data' => $codes->items(),
            'meta' => [
                'current_page' => $codes->currentPage(),
                'per_page' => $codes->perPage(),
                'total' => $codes->total(),
                'last_page' => $codes->lastPage(),
            ],
        ]);
    }

    public function update(Request $request, QrBatch $batch): JsonResponse
    {
        $data = $request->validate([
            'product_name' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1048576'],
            'status' => ['sometimes', 'string', 'max:24'],
        ]);

        $batch->update($data);
        $batch->load(['company:id,name', 'product:id,name,sku', 'certificate:id,certificate_number,standard,expires_at']);

        return response()->json([
            'data' => [
                'id' => $batch->id,
                'product_name' => $batch->product_name,
                'company' => $batch->company,
                'product' => $batch->product,
                'certificate' => $batch->certificate,
                'prefix' => $batch->prefix,
                'quantity' => $batch->quantity,
                'total_generated' => $batch->total_generated,
                'status' => $batch->status,
                'started_at' => $batch->started_at,
                'completed_at' => $batch->completed_at,
                'failed_at' => $batch->failed_at,
                'failure_reason' => $batch->failure_reason,
                'created_at' => $batch->created_at,
            ],
        ]);
    }

    public function destroy(QrBatch $batch): JsonResponse
    {
        $batch->delete();

        return response()->json(['message' => 'Batch deleted.']);
    }

    private function verificationUrl(string $token): string
    {
        $frontBase = rtrim((string) env('FRONT_VERIFY_URL', self::FRONT_VERIFY_URL_DEFAULT), '/');

        return $frontBase.'?token='.rawurlencode($token);
    }
}
