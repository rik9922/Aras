@extends('layout.app', ['page_name' => 'Edit Brand'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Edit Brand</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{ route($routePrefix.'.update.brand') }}" method="POST">@csrf
                <input type="hidden" name="id" value="{{ $brand->id }}">
                <div class="row p-3">
                    <div class="6">
                        <div class="mb-3">
                            <label for="brandName" class="form-label text-uppercase">Brand name</label>
                            <input type="text" class="form-control shadow-none  @error('brand_name') is-invalid @enderror"
                                value="{{ $brand->brand_name }}" name="brand_name" id="brandName" placeholder="Brand name">
                            @error('brand_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="6">
                        <div class="mb-3">
                            <label for="note" class="form-label text-uppercase">Notes</label>
                            <textarea type="text" class="form-control shadow-none @error('notes') is-invalid @enderror" name="notes"
                                id="note" placeholder="Brand notes">{{ $brand->notes }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label text-uppercase">Budget alert email <span class="small text-muted">(optional)</span></label>
                            <input type="email" class="form-control shadow-none" name="budget_alert_email"
                                value="{{ old('budget_alert_email', $brand->budget_alert_email) }}"
                                placeholder="Extra recipient for budget emails for this brand">
                            @error('budget_alert_email')
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
                                    <option value="{{ $parent->id }}"
                                        {{ (string) old('parent_brand_id', $brand->parent_brand_id) === (string) $parent->id ? 'selected' : '' }}>
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
                            @php
                                $selectedBillToIds = old('bill_to_client_ids', $brand->allowedBillToClients?->pluck('id')->all() ?? []);
                            @endphp
                            <select class="form-select shadow-none multiple-select2 @error('bill_to_client_ids') is-invalid @enderror"
                                name="bill_to_client_ids[]" id="billToClients" multiple>
                                @foreach (($billToClients ?? []) as $client)
                                    <option value="{{ $client->id }}"
                                        {{ is_array($selectedBillToIds) && in_array($client->id, $selectedBillToIds) ? 'selected' : '' }}>
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

                    <div class="6">
                        <div class="text-uppercase fw-bold">Brand Categories</div>
                        <div class="d-flex flex-wrap align-items-center">
                            @php
                                // Use old() on validation error, otherwise load from pivot relationship
                                $selectedCategoryIds = old('brand_category_id')
                                    ? array_map('intval', (array) old('brand_category_id'))
                                    : $brand->brandCategories->pluck('id')->map(fn($id) => (int)$id)->all();
                            @endphp
                            @foreach ($brandCategory as $id => $category)
                                <div class="form-check m-2">
                                    <input
                                        class="form-check-input shadow-none cursor-pointer @error('brand_category_id') is-invalid @enderror"
                                        name="brand_category_id[]" type="checkbox" value="{{ $category->id }}"
                                        {{ in_array((int) $category->id, $selectedCategoryIds, true) ? 'checked' : '' }}
                                        id="category{{ $category->id }}">
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

                    <div class="6">
                        <div class="text-uppercase fw-bold">Active</div>
                        <div class="d-flex flex-wrap align-items-center">
                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none cursor-pointer" name="status" type="radio"
                                    value="1" @if($brand->status =='1') checked @endif>
                                <label class="form-check-label text-uppercase">
                                    Yes
                                </label>
                            </div>

                            <div class="form-check m-2">
                                <input class="form-check-input shadow-none cursor-pointer" name="status" type="radio"
                                    value="0" @if($brand->status =='0') checked @endif>
                                <label class="form-check-label text-uppercase">
                                    No
                                </label>
                            </div>

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
