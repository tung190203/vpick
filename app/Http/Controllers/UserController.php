<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'location_id' => 'nullable|exists:locations,id',
            'vndupr_score' => 'nullable|numeric|min:0|max:100',
            'about' => 'nullable|string|max:300',
            'password' => 'nullable|string|min:8',
            'is_profile_completed' => 'nullable|boolean',
        ]);

        $user = User::findOrFail(auth()->id());

        $data = [
            'full_name' => $request->input('full_name'),
        ];
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        if ($request->hasFile('avatar_url')) {
            if ($user->avatar_url) {
                $oldPath = str_replace(asset('storage/') . '/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $avatar = $request->file('avatar_url');
            $path = $avatar->store('avatars', 'public');
            $data['avatar_url'] = asset('storage/' . $path);
        }
        if ($request->filled('location_id')) {
            $data['location_id'] = $request->input('location_id');
        }
        if ($request->filled('about')) {
            $data['about'] = $request->input('about');
        }
        if ($request->filled('vndupr_score')) {
            $data['vndupr_score'] = $request->input('vndupr_score');
        }

        if ($request->boolean('is_profile_completed') && !$user->is_profile_completed) {
            $data['is_profile_completed'] = true;
        }        

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }
}
