<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\SellerStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $provinces = Province::query()->orderBy('name')->get();
        $province = $provinces->firstWhere('id', $request->integer('province')) ?? $provinces->first();
        $counties = $province?->counties()->orderBy('name')->get() ?? collect();
        $cities = $province ? City::query()
            ->where('province_id', $province->id)
            ->with('county')
            ->orderBy('name')->orderBy('id')
            ->paginate(30)->withQueryString() : null;

        return view('admin.locations.index', compact('provinces', 'province', 'counties', 'cities'));
    }

    public function storeProvince(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('provinces')],
            'tel_prefix' => ['required', 'digits:3'],
        ]);

        $province = new Province;
        $province->name = $data['name'];
        $province->tel_prefix = $data['tel_prefix'];
        $province->save();

        return $this->backToProvince($province->id, 'استان اضافه شد.');
    }

    public function updateProvince(Request $request, Province $province): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('provinces')->ignore($province)],
            'tel_prefix' => ['required', 'digits:3'],
        ]);

        $province->name = $data['name'];
        $province->tel_prefix = $data['tel_prefix'];
        $province->save();

        return $this->backToProvince($province->id, 'استان به‌روزرسانی شد.');
    }

    public function destroyProvince(Province $province): RedirectResponse
    {
        return DB::transaction(function () use ($province) {
            $province = Province::query()->lockForUpdate()->findOrFail($province->id);

            if ($province->cities()->exists()
                || Listing::query()->where('province_id', $province->id)->exists()
                || SellerStore::query()->where('province_id', $province->id)->exists()) {
                return to_route('admin.locations.index', ['province' => $province->id])
                    ->withErrors(['province' => 'این استان شهر، آگهی یا فروشگاه وابسته دارد و قابل حذف نیست.']);
            }

            $province->delete();

            return to_route('admin.locations.index')->with('status', 'استان حذف شد.');
        });
    }

    public function storeCity(Request $request, Province $province): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'county' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($province, $data, $request) {
            Province::query()->lockForUpdate()->findOrFail($province->id);
            $county = County::query()->firstOrCreate(
                ['province_id' => $province->id, 'name' => $data['county']],
            );
            $request->validate([
                'name' => Rule::unique('cities')->where('county_id', $county->id),
            ]);
            City::query()->create([
                'name' => $data['name'],
                'province_id' => $province->id,
                'county_id' => $county->id,
            ]);
        });

        return $this->backToProvince($province->id, 'شهر اضافه شد.');
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        DB::transaction(function () use ($city, $request) {
            Province::query()->lockForUpdate()->findOrFail($city->province_id);
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('cities')->where('county_id', $city->county_id)->ignore($city)],
            ]);

            $city->update($data);
        });

        return $this->backToProvince($city->province_id, 'نام شهر به‌روزرسانی شد.');
    }

    private function backToProvince(int $province, string $message): RedirectResponse
    {
        return to_route('admin.locations.index', ['province' => $province])->with('status', $message);
    }
}
