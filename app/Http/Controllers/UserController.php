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
            'password' => 'nullable|string|min:8',
        ]);

        $user = User::findOrFail(auth()->id());

        $data = [
            'full_name' => $request->input('full_name'),
        ];
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        if ($request->hasFile('avatar_url')) {
            // Xoá avatar cũ nếu tồn tại
            if ($user->avatar_url) {
                $oldPath = str_replace(asset('storage/') . '/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Lưu avatar mới
            $avatar = $request->file('avatar_url');
            $path = $avatar->store('avatars', 'public');
            $data['avatar_url'] = asset('storage/' . $path);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }
}
