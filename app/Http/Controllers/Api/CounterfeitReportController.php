<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CounterfeitReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CounterfeitReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'contact' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('counterfeit-reports', 'public');

        $report = CounterfeitReport::query()->create([
            'location' => $data['location'],
            'description' => $data['description'],
            'contact' => $data['contact'] ?? null,
            'image_path' => $path,
            'image_url' => url('/storage/'.$path),
            'status' => 'pending',
            'reported_at' => now(),
        ]);

        return response()->json([
            'message' => 'Signalement enregistre.',
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'reported_at' => $report->reported_at,
                'image_url' => $report->image_url,
            ],
        ], 201);
    }
}
