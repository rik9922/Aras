<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $brandCategoryId = $request->filled('brand_category_id')
            ? (int) $request->input('brand_category_id')
            : null;

        $filteredQuery = $this->filteredBrandsQuery(
            $request->input('search'),
            $brandCategoryId
        );

        if ($request->ajax()) {
            $totalBrands = (clone $filteredQuery)->count();

            $brands = $filteredQuery
                ->with(['user', 'parent', 'brandCategories'])
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'brands' => $brands,
                'total' => $totalBrands,
            ]);
        }

        $totalBrands = (clone $filteredQuery)->count();

        $brands = $filteredQuery
            ->with(['user', 'parent', 'brandCategories'])
            ->orderBy('id', 'DESC')
            ->paginate(20);

        $brandCategories = BrandCategory::query()->orderBy('name')->get();

        $routeName = $request->route()?->getName() ?? '';
        $brandsExportRoute = match (true) {
            str_starts_with($routeName, 'manager.') => route('manager.export.brands'),
            str_starts_with($routeName, 'admin.') => route('admin.export.brands'),
            default => null,
        };

        return view('admin.brand.index', compact('brands', 'brandCategories', 'brandCategoryId', 'brandsExportRoute', 'totalBrands'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Brand>
     */
    private function filteredBrandsQuery(?string $search, ?int $brandCategoryId)
    {
        return Brand::query()
            ->when($search, function ($query, $search) {
                $query->where('brand_name', 'like', '%' . $search . '%');
            })
            ->when($brandCategoryId, function ($query) use ($brandCategoryId) {
                $query->whereRaw('FIND_IN_SET(?, brand_category_id)', [$brandCategoryId]);
            });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brandCategory = BrandCategory::get();
        $parentBrands = Brand::query()->orderBy('brand_name')->get();
        $billToClients = User::query()
            ->select(['id', 'name'])
            ->where('role_id', 3)
            ->orderBy('name')
            ->get();

        return view('admin.brand.create',compact('brandCategory', 'parentBrands', 'billToClients'));
    }

    /**
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate input fields
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
            'parent_brand_id' => 'nullable|integer|exists:brands,id',
            'notes' => 'nullable|string',
            'brand_category_id' => 'required|array|min:1',
        ], [
            'brand_category_id.required' => 'Please select at least one brand category.',
            'brand_category_id.array'    => 'Please select at least one brand category.',
            'brand_category_id.min'      => 'Please select at least one brand category.',
        ]);

        $brand = new Brand();
        $brand->user_id = null;
        $brand->parent_brand_id = $request->input('parent_brand_id') ?: null;
        $brand->brand_name = $request->brand_name;
        $brand->notes = $request->notes;
        $brand->status = 1;
        $categoryIds = collect($request->brand_category_id)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()->values()->all();

        $brand->brand_category_id = $categoryIds[0] ?? null; // store first for backward compat
        $brand->save();

        // Sync pivot table (new many-to-many)
        $brand->brandCategories()->sync($categoryIds);

        $ids = collect($request->input('bill_to_client_ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
        $brand->allowedBillToClients()->sync($ids);

        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.brands')->with('success', 'Brand added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $brandCategory = BrandCategory::get();
        $brand = Brand::with(['user', 'allowedBillToClients', 'brandCategories'])->where('id', $id)->first();
        $parentBrands = Brand::query()
            ->where('id', '!=', $brand->id)
            ->orderBy('brand_name')
            ->get();

        $billToClients = User::query()
            ->select(['id', 'name'])
            ->where('role_id', 3)
            ->orderBy('name')
            ->get();

        return view('admin.brand.edit', compact('brand','brandCategory', 'parentBrands', 'billToClients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBrand(Request $request)
    {
        // Validate input fields
        $brandId = $request->id;
        $brand = Brand::query()->where('id', $brandId)->firstOrFail();
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brandId,
            'parent_brand_id' => 'nullable|integer|exists:brands,id|not_in:'.$brandId,
            'notes' => 'nullable|string',
            'brand_category_id' => 'required|array|min:1',
            'budget_alert_email' => 'nullable|email|max:255',
            'status' => 'required|in:0,1',
        ], [
            'brand_category_id.required' => 'Please select at least one brand category.',
            'brand_category_id.array'    => 'Please select at least one brand category.',
            'brand_category_id.min'      => 'Please select at least one brand category.',
        ]);

        $parentId = $request->input('parent_brand_id') ?: null;
        if ($parentId) {
            $descendants = \App\Models\Brand::descendantIdsIncludingSelfById((int) $brandId);
            // Disallow cycles (can't set parent to itself or its descendants).
            if (in_array((int) $parentId, array_diff($descendants, [(int) $brandId]), true)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_brand_id' => 'Invalid parent brand selection.']);
            }
        }

        $brand->brand_name = $request->brand_name;
        $brand->parent_brand_id = $parentId;
        $brand->notes = $request->notes;
        $brand->status = $request->status;
        $categoryIds = collect($request->brand_category_id)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()->values()->all();

        $brand->brand_category_id = $categoryIds[0] ?? null; // store first for backward compat
        $brand->budget_alert_email = $request->input('budget_alert_email') ?: null;
        $brand->save();

        // Sync pivot table (new many-to-many)
        $brand->brandCategories()->sync($categoryIds);

        $ids = collect($request->input('bill_to_client_ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
        $brand->allowedBillToClients()->sync($ids);

        $routePrefix = (int) (auth()->user()->role_id ?? 0) === 6 ? 'manager' : 'admin';
        return redirect()->route($routePrefix.'.brands')->with('success', 'Brand updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->brandCategories()->detach();
        $brand->allowedBillToClients()->detach();
        $brand->delete();

        $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($prefix.'.brands')->with('success', 'Brand deleted successfully!');
    }

    /**
     * Return Bill To clients allowed for a given brand.
     * - If brand has explicit mapping, return it.
     * - Else, if parent brand has mapping, inherit it.
     * - Else return all clients (role_id = 3).
     */
    public function billToOptions(Brand $brand): JsonResponse
    {
        $brand->load(['allowedBillToClients:id,name', 'parent.allowedBillToClients:id,name']);

        $clients = $brand->allowedBillToClients;
        if ($clients->isEmpty() && $brand->parent) {
            $clients = $brand->parent->allowedBillToClients;
        }

        if ($clients->isEmpty()) {
            $clients = User::query()
                ->select(['id', 'name'])
                ->where('role_id', 3)
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'brand_id' => (int) $brand->id,
            'clients' => $clients->values()->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->name]),
        ]);
    }
}
