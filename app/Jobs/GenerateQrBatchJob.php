<?php

namespace App\Jobs;

use App\Models\QrBatch;
use App\Services\SignedTokenService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateQrBatchJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 21600;
    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(
        public string $batchId,
        public ?string $expiresAt = null
    ) {
    }

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function handle(SignedTokenService $signedTokenService): void
    {
        $lock = Cache::lock('generate-qr-batch:'.$this->batchId, 7200);
        if (!$lock->get()) {
            return;
        }

        try {
            $this->runGeneration($signedTokenService);
        } finally {
            $lock->release();
        }
    }

    private function runGeneration(SignedTokenService $signedTokenService): void
    {
        /** @var QrBatch|null $batch */
        $batch = QrBatch::query()->find($this->batchId);

        if ($batch === null) {
            return;
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        $expiresAt = $this->expiresAt ? Carbon::parse($this->expiresAt) : null;
        $generatedAt = now();
        $chunkSize = 1000;
        $rows = [];
        $chunkCodes = [];
        $lastGeneratedSerial = (int) DB::table('qr_codes')
            ->where('batch_id', $batch->id)
            ->max('serial');
        $startSerial = $lastGeneratedSerial + 1;
        $checkExistingCodes = $startSerial > 1;

        try {
            if ($startSerial > $batch->quantity) {
                $batch->update([
                    'status' => 'completed',
                    'total_generated' => $batch->quantity,
                    'completed_at' => now(),
                ]);

                return;
            }

            for ($serial = $startSerial; $serial <= $batch->quantity; $serial++) {
                $code = $this->generateBatchUniqueCode(
                    $signedTokenService,
                    $batch->id,
                    $serial,
                    $chunkCodes,
                    $checkExistingCodes
                );
                $chunkCodes[$code] = true;
                $token = $signedTokenService->issueCompactToken();

                $rows[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'batch_id' => $batch->id,
                    'serial' => $serial,
                    'code' => $code,
                    'token_hash' => $signedTokenService->tokenHash($token),
                    'verification_token' => $token,
                    'status' => 'active',
                    'expires_at' => $expiresAt?->toDateTimeString(),
                    'generated_at' => $generatedAt->toDateTimeString(),
                    'created_at' => $generatedAt->toDateTimeString(),
                    'updated_at' => $generatedAt->toDateTimeString(),
                ];

                if (count($rows) >= $chunkSize) {
                    DB::table('qr_codes')->insert($rows);
                    $rows = [];
                    $chunkCodes = [];
                }
            }

            if ($rows !== []) {
                DB::table('qr_codes')->insert($rows);
            }

            $totalGenerated = (int) DB::table('qr_codes')
                ->where('batch_id', $batch->id)
                ->count();

            $batch->update([
                'status' => 'completed',
                'total_generated' => $totalGenerated,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function generateBatchUniqueCode(
        SignedTokenService $signedTokenService,
        string $batchId,
        int $serial,
        array &$chunkCodes,
        bool $checkExistingCodes
    ): string {
        $space = 1048576; // 32^4
        $base = $serial - 1;

        for ($offset = 0; $offset < 2048; $offset++) {
            $candidateSerial = (($base + $offset) % $space) + 1;
            $code = $signedTokenService->codeFromSerial($batchId, $candidateSerial);
            if (isset($chunkCodes[$code])) {
                continue;
            }

            if (!$checkExistingCodes) {
                return $code;
            }

            $exists = DB::table('qr_codes')
                ->where('batch_id', $batchId)
                ->where('code', $code)
                ->exists();
            if (!$exists) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to reserve unique code in current batch.');
    }
}
