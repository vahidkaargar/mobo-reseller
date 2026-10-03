<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThumbnailRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

class ThumbnailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ThumbnailRequest $request)
    {
        $url = $request->get('url');
        $fileName = md5(base64_encode($url)) . ".png";
        $filePath = 'images/cache/' . $fileName;

        if (Storage::exists($filePath)) {
            $fileBinary = Storage::get($filePath);
        } else {
            $fileBinary = Http::get($url)->body();
            Storage::put($filePath, $fileBinary);
        }


        $manager = new ImageManager(Driver::class);
        $image = $manager->read($fileBinary);

        if ($request->get('fit') === 'cover') {
            $image->coverDown($request->integer('w'), $request->integer('h'));
        } else {
            $image->resize($request->integer('w'), $request->integer('h'));
        }

        $encoded = $image->toWebp($request->integer('q', 80));

        return response($encoded)
            ->header('Content-Type', 'image/webp')
            ->header('Cache-Control', 'public, max-age=604800');
    }
}
