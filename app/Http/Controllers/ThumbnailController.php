<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThumbnailRequest;
use App\Models\BambooBrand;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ThumbnailController extends Controller
{
    /**
     * Serve a resized brand logo.
     *
     * The image URL is read from the catalog, never from the request, so callers
     * cannot make the server fetch arbitrary URLs.
     */
    public function __invoke(ThumbnailRequest $request, int $brandId)
    {
        $url = BambooBrand::query()->where('brand_id', $brandId)->value('image');
        abort_if(blank($url), 404);

        $filePath = 'images/cache/'.md5(base64_encode($url)).'.png';

        if (Storage::exists($filePath)) {
            $fileBinary = Storage::get($filePath);
        } else {
            try {
                $response = Http::timeout(10)->get($url);
            } catch (ConnectionException) {
                abort(404);
            }
            abort_unless($response->successful(), 404);

            $fileBinary = $response->body();
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
            ->header('Cache-Control', 'private, max-age=604800');
    }
}
