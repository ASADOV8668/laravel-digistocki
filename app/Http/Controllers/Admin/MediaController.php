<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ListingMediaService;
use App\Services\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request, SystemOptions $options)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $query = trim((string) $request->input('q', ''));

        $media = Media::query()
            ->where('collection_name', 'listing-images')
            ->where('model_type', User::class)
            ->with('model')
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($search) use ($query): void {
                    $search->where('name', 'like', "%{$query}%")
                        ->orWhere('file_name', 'like', "%{$query}%")
                        ->orWhereHasMorph('model', [User::class], function ($user) use ($query): void {
                            $user->where('name', 'like', "%{$query}%")
                                ->orWhere('mobile', 'like', "%{$query}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.media.index', [
            'media' => $media,
            'perPageOptions' => $perPageOptions,
            'adminUploadMaxMb' => $options->maxImageUploadMb() * 5,
        ]);
    }

    public function store(Request $request, SystemOptions $options, ImageService $imageService, ListingMediaService $mediaService): RedirectResponse
    {
        $maxMb = $options->maxImageUploadMb() * 5;
        $validated = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:'.($maxMb * 1024)],
        ], [
            'image.required' => 'انتخاب تصویر رسانه الزامی است.',
            'image.file' => 'فایل انتخاب‌شده معتبر نیست.',
            'image.mimes' => 'فرمت تصویر باید jpg، jpeg، png، webp، heic یا heif باشد.',
            'image.max' => 'حجم رسانه مدیر نمی‌تواند بیشتر از '.$maxMb.' مگابایت باشد.',
        ]);

        try {
            $mediaService->createFromUpload($request->user(), $validated['image'], $imageService);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'آپلود و پردازش رسانه انجام نشد. لطفاً دوباره تلاش کنید.');
        }

        return back()->with('status', 'رسانه با موفقیت آپلود و به کتابخانه اضافه شد.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        abort_unless(
            $media->collection_name === 'listing-images' && $media->model_type === User::class,
            404,
        );

        if (ListingImage::query()->where('media_id', $media->getKey())->exists()) {
            return back()->with('error', 'این رسانه در یک یا چند آگهی استفاده می‌شود و قابل حذف نیست.');
        }

        $media->delete();

        return back()->with('status', 'رسانه با موفقیت حذف شد.');
    }
}
