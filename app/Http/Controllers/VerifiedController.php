<?php

namespace App\Http\Controllers;

use App\Http\Resources\VerificationResource;
use App\Models\Verify;
use Illuminate\Http\Request;

class VerifiedController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'vndupr_score' => 'required|numeric|min:0|max:10',
            'certified_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'verifier_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = [
            'user_id' => $request->user_id,
            'vndupr_score' => $request->vndupr_score,
        ];
        if ($request->hasFile('certified_file')) {
            $data['certified_file'] = $request->file('certified_file')->store('certified_files', 'public');
        }
        if ($request->has('verifier_id')) {
            $data['verifier_id'] = $request->verifier_id;
        }

        // Create a new verification request
        $verification = Verify::create($data);
        if (!$verification) {
            return response()->json(['message' => 'Failed to create verification request.'], 500);
        }

        return response()->json(new VerificationResource($verification));
    }
    public function show()
    {
        $verification = Verify::where('user_id', auth()->id())
            ->with(['user', 'verifier', 'approver'])
            ->first();

        return response()->json(
            $verification ? new VerificationResource($verification) : null
        );
    }
}
