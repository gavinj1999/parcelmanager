<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'activity_date' => 'required|date_format:Y-m-d',
        ]);

        $activity = Activity::whereDate('activity_date', $request->activity_date)->first();
        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }

        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));
        $imageName = 'activity_' . time() . '.jpg';
        $path = 'public/images/' . $imageName;

        Storage::put($path, $imageData);

        $image = ActivityImage::create([
            'activity_id' => $activity->id,
            'image_path' => 'images/' . $imageName,
        ]);

        // Invalidate activities cache to reflect new image
        Cache::forget('activities_with_images');

        return response()->json(['message' => 'Image uploaded', 'image_id' => $image->id], 200);
    }
}
