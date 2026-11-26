<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ProvinceDeletionRequest;
use App\Models\SellerStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Sadegh19b\LaravelIranCities\Models\Province;

class ProvinceDeletionRequestController extends Controller
{
    public function store(Request $request, Province $province): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $created = DB::transaction(function () use ($request, $province, $data) {
            $province = Province::query()->lockForUpdate()->findOrFail($province->id);

            if (ProvinceDeletionRequest::query()->where('province_id', $province->id)->where('status', 'pending')->exists()) {
                return false;
            }

            ProvinceDeletionRequest::query()->create([
                'province_id' => $province->id,
                'province_name' => $province->name,
                'requested_by' => $request->user()->id,
                'reason' => $data['reason'],
            ]);

            return true;
        });

        $response = to_route('admin.locations.index', ['province' => $province->id]);

        return $created
            ? $response->with('status', 'درخواست حذف استان برای بررسی ثبت شد.')
            : $response->withErrors(['province' => 'برای این استان یک درخواست در انتظار تأیید وجود دارد.']);
    }

    public function index(Request $request): View
    {
        $requests = ProvinceDeletionRequest::query()
            ->when(! $request->user()->isSeniorAdmin(), fn ($query) => $query->where('requested_by', $request->user()->id))
            ->with(['requester', 'reviewer', 'province' => fn ($query) => $query
                ->withCount('cities')
                ->addSelect([
                    'listings_count' => Listing::query()->selectRaw('count(*)')->whereColumn('listings.province_id', 'provinces.id'),
                    'stores_count' => SellerStore::query()->selectRaw('count(*)')->whereColumn('seller_stores.province_id', 'provinces.id'),
                ])])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest('id')->paginate(25);

        return view('admin.locations.deletion-requests', compact('requests'));
    }

    public function review(Request $request, ProvinceDeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($request->user()->isSeniorAdmin(), 403);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        $message = DB::transaction(function () use ($request, $deletionRequest, $data) {
            $province = Province::query()->lockForUpdate()->find($deletionRequest->province_id);
            $pending = ProvinceDeletionRequest::query()->lockForUpdate()->findOrFail($deletionRequest->id);
            abort_unless($pending->status === 'pending', 409);
            abort_if((int) $pending->requested_by === (int) $request->user()->id, 403);

            $status = 'rejected';
            if ($data['decision'] === 'approve') {
                $status = 'blocked';
                if ($province && $province->name === $pending->province_name
                    && ! $province->cities()->exists()
                    && ! Listing::query()->where('province_id', $province->id)->exists()
                    && ! SellerStore::query()->where('province_id', $province->id)->exists()) {
                    $province->delete();
                    $status = 'approved';
                }
            }

            $pending->update([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return match ($status) {
                'approved' => 'درخواست تأیید شد و استان حذف شد.',
                'rejected' => 'درخواست حذف استان رد شد.',
                default => 'استان وابستگی دارد، تغییر نام داده یا دیگر موجود نیست. حذف انجام نشد.',
            };
        }, 3);

        return to_route('admin.locations.deletion-requests.index')->with('status', $message);
    }
}
