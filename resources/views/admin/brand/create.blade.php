@extends('layout.app', ['page_name' => 'Create Brand'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Brand Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route($routePrefix.'.store.brand')}}" method="POST">@csrf

                <div class="row p-3">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="brandName" class="form-label text-uppercase">Brand name</label>
                            <input type="text" class="form-control shadow-none  @error('brand_name') is-invalid @enderror" name="brand_name" id="brandName"
                                placeholder="Brand name">
                            @error('brand_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="note" class="form-label text-uppercase">Notes</label>
                            <textarea type="text" class="form-control shadow-none @error('notes') is-invalid @enderror" name="notes" id="note" placeholder="Brand notes"></textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="parentBrand" class="form-label text-uppercase">Parent brand <span class="small text-muted">(optional)</span></label>
                            <select class="form-select shadow-none multiple-select2 @error('parent_brand_id') is-invalid @enderror" name="parent_brand_id" id="parentBrand">
                                <option value="">— None —</option>
                                @foreach (($parentBrands ?? []) as $parent)
                                    <option value="{{ $parent->id }}" {{ (string) old('parent_brand_id') === (string) $parent->id ? 'selected' : '' }}>
                                        {{ $parent->brand_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_brand_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="billToClients" class="form-label text-uppercase">Allowed Bill To clients <span class="small text-muted">(optional)</span></label>
                            <select class="form-select shadow-none multiple-select2 @error('bill_to_client_ids') is-invalid @enderror"
                                name="bill_to_client_ids[]" id="billToClients" multiple>
                                @foreach (($billToClients ?? []) as $client)
                                    <option value="{{ $client->id }}"
                                        {{ is_array(old('bill_to_client_ids')) && in_array($client->id, old('bill_to_client_ids')) ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bill_to_client_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">If empty, all Bill To clients will be available when creating/editing events for this brand.</div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="text-uppercase fw-bold">Brand Categories</div>
                        <div class="d-flex flex-wrap align-items-center">
                            @foreach ($brandCategory as $id => $category)
                                <div class="form-check m-2">
                                    <input class="form-check-input shadow-none cursor-pointer @error('brand_category_id') is-invalid @enderror"
                                        name="brand_category_id[]" type="checkbox" value="{{ $category->id }}"
                                        id="category{{ $category->id }}" 
                                        {{ in_array($category->id, old('brand_category_id', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label text-uppercase" for="category{{ $category->id }}">
                                        {{ $category->name }}
                                    </label>
                                </div>
                            @endforeach
            
                            @error('brand_category_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        
                        </div>
                    </div>

                </div>

                <div class="col-2">
                    <button type="submit" class="btn-ds btn-primary-ds">Save</button>
                    <button type="button" class="btn-ds btn-secondary-ds" onclick="window.history.back()">Back</button>
                </div>
            </form>
        </div>
    </div>
    
@endsection

