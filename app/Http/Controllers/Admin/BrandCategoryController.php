<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandCategory;
use Illuminate\Http\Request;

class BrandCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $brandCategory = BrandCategory::orderBy('id','DESC')->get();
    
        if($request->search) {
            $search = $request->search;
            $brandCategory = BrandCategory::when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })->get();
        
            return response()->json(['brandCategory' => $brandCategory]);
        }
        return view('admin.brand-category.index',compact('brandCategory'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.brand-category.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        BrandCategory::create([
            'name' => $request->name,
            'notes' =>  $request->note,
        ]);
        $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($prefix.'.brand-category')->with('success', 'Brand Category added successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $brandCategory = BrandCategory::find($id);
        return view('admin.brand-category.edit',compact('brandCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBrandCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);
        
        $id = $request->id;
        $brandCategory = BrandCategory::find($id);
        $brandCategory->name = $request->name;
        $brandCategory->notes = $request->note;
        $brandCategory->save();
        $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($prefix.'.brand-category')->with('success', 'Brand Category updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        BrandCategory::findOrFail($id)->delete();

        $prefix = ((int)(auth()->user()->role_id ?? 0) === 6) ? 'manager' : 'admin';
        return redirect()->route($prefix.'.brand-category')->with('success', 'Brand Category deleted successfully!');
    }
}
