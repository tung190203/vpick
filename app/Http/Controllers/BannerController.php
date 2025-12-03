<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Services\ImageOptimizationService;
use App\Models\Banner; // Giả sử bạn có model Banner

class BannerController extends Controller
{
    protected $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'image_url' => 'required|image|max:5120'
        ]);

        $imagePath = null;
        $file = $request->file('image_url');
        $uploadFolder = 'banners';

        if ($file) {
            $paths = $this->imageService->optimizeThumbnail(
                $file, 
                $uploadFolder,
            );
            $imagePath = $paths;
        }
        $banner = Banner::create([
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'image_url' => $imagePath, 
        ]);

        return ResponseHelper::success($banner, 'Tạo banner thành công');
    }
}