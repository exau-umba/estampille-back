<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevokeQrCodeRequest;
use App\Models\QrCode;
use Illuminate\Http\JsonResponse;

class QrCodeController extends Controller
{
    public function revoke(RevokeQrCodeRequest $request, QrCode $qrCode): JsonResponse
    {
        if ($qrCode->status === 'revoked') {
            return response()->json([
                'message' => 'QR code already revoked.',
                'data' => [
                    'id' => $qrCode->id,
                    'status' => $qrCode->status,
                    'revoked_at' => $qrCode->revoked_at,
                    'revocation_reason' => $qrCode->revocation_reason,
                ],
            ]);
        }

        $qrCode->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $request->input('reason', 'Revoked by admin'),
        ]);

        return response()->json([
            'message' => 'QR code revoked successfully.',
            'data' => [
                'id' => $qrCode->id,
                'status' => $qrCode->status,
                'revoked_at' => $qrCode->revoked_at,
                'revocation_reason' => $qrCode->revocation_reason,
            ],
        ]);
    }
}
