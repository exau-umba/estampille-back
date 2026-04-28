<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\ScanEvent;
use App\Services\SignedTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;

class VerificationController extends Controller
{
    public function show(string $token, Request $request, SignedTokenService $signedTokenService): JsonResponse|RedirectResponse
    {
        if (!$request->expectsJson() && $request->acceptsHtml()) {
            return redirect()->away($this->frontVerificationUrl($token));
        }

        $tokenHash = $signedTokenService->tokenHash($token);
        $payload = $signedTokenService->verify($token);

        /** @var QrCode|null $qrCode */
        $qrCode = QrCode::query()
            ->with([
                'batch:id,product_name,company_id,product_id,certificate_id',
                'batch.company:id,name,website',
                'batch.product:id,name,sku,image_url',
                'batch.certificate:id,certificate_number,standard,issued_at,expires_at,file_name,file_url',
            ])
            ->where('token_hash', $tokenHash)
            ->first();

        if ($qrCode === null) {
            $this->logScan(null, $tokenHash, 'not_found', $request);

            return response()->json([
                'status' => 'not_found',
                'message' => 'QR code not found.',
            ], 404);
        }

        if ($qrCode->status === 'revoked') {
            $this->logScan($qrCode->id, $tokenHash, 'revoked', $request);

            return response()->json([
                'status' => 'revoked',
                'message' => 'QR code revoked.',
                'data' => [
                    'code' => $qrCode->code,
                    'batch_id' => $qrCode->batch_id,
                ],
            ], 409);
        }

        if ($qrCode->expires_at !== null && Carbon::now()->greaterThan($qrCode->expires_at)) {
            $this->logScan($qrCode->id, $tokenHash, 'expired', $request);

            return response()->json([
                'status' => 'expired',
                'message' => 'QR code expired.',
                'data' => [
                    'code' => $qrCode->code,
                    'batch_id' => $qrCode->batch_id,
                ],
            ], 410);
        }

        $this->logScan($qrCode->id, $tokenHash, 'valid', $request);

        $certificate = $qrCode->batch?->certificate;
        $product = $qrCode->batch?->product;
        $company = $qrCode->batch?->company;

        return response()->json([
            'status' => 'valid',
            'message' => 'QR code is valid.',
            'data' => [
                'code' => $qrCode->code,
                'serial' => $qrCode->serial,
                'batch_id' => $qrCode->batch_id,
                'product_name' => $qrCode->batch?->product_name,
                'generated_at' => $qrCode->generated_at,
                'expires_at' => $qrCode->expires_at,
                'claims' => $payload ?? [
                    'bid' => $qrCode->batch_id,
                    'cid' => $qrCode->batch?->certificate_id,
                    'sid' => $qrCode->serial,
                    'code' => $qrCode->code,
                ],
            ],
            'verification' => [
                'status' => 'valid',
                'subtitle' => 'Ce produit est authentique et certifie conforme aux exigences.',
                'name' => $product?->name ?? $qrCode->batch?->product_name,
                'imageUrl' => $product?->image_url ?? '',
                'certificateId' => $certificate?->certificate_number ?? '',
                'sku' => $product?->sku ?? '',
                'company' => $company?->name ?? '',
                'serialNumber' => (string) $qrCode->serial,
                'labelCode' => $qrCode->code,
                'provinceCode' => '',
                'certificationStandard' => $certificate?->standard ?? '',
                'issuedAt' => $certificate?->issued_at?->toDateString(),
                'expiresAt' => $certificate?->expires_at?->toDateString(),
                'merchantWebsite' => $company?->website ?? '',
                'certificateFileName' => $certificate?->file_name ?? '',
            ],
        ]);
    }

    private function logScan(?string $qrCodeId, string $tokenHash, string $result, Request $request): void
    {
        ScanEvent::query()->create([
            'qr_code_id' => $qrCodeId,
            'token_hash' => $tokenHash,
            'result' => $result,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'scanned_at' => now(),
        ]);
    }

    private function frontVerificationUrl(string $token): string
    {
        $baseUrl = rtrim((string) env('FRONT_VERIFY_URL', 'http://localhost:5173/verify'), '/');

        return $baseUrl.'?token='.rawurlencode($token);
    }
}
