<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
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

        return view('admin.media.index', compact('media', 'perPageOptions'));
    }
}
