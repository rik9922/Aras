@extends('layout.app', ['page_name' => 'Brands List'])
@section('content')
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
    <div class="container py-2">
        <div class="d-grid">
            <div class="h5">Brands: <span class="total-data">{{ $totalBrands ?? 0 }}</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-4 pt-3">
                    <input type="search" class="form-control shadow-none" name="serach"
                        placeholder="Search for a brand name here" id="search">
                </div>
                <div class="col-md-3 pt-3">
                    <select class="form-control shadow-none" id="brand_category_id">
                        <option value="">All Brand Categories</option>
                        @if(!empty($brandCategories) && count($brandCategories) > 0)
                            @foreach($brandCategories as $cat)
                                <option value="{{ $cat->id }}" @if(!empty($brandCategoryId) && (int)$brandCategoryId === (int)$cat->id) selected @endif>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-5 pt-3">
                    <div class="d-grid d-flex gap-2 flex-wrap justify-content-md-end">
                        @if(!empty($brandsExportRoute))
                            <a href="{{ $brandsExportRoute }}" class="brands-export-btn">
                                <button type="button" class="btn-ds btn-info-ds text-nowrap">Export</button>
                            </a>
                        @endif
                        <a href="{{ route($routePrefix.'.brands.create') }}"><button type="button" class="btn-ds btn-primary-ds text-nowrap">Add Brand</button></a>
                        <a href="{{route($routePrefix.'.brand-category')}}"><button type="button" class="btn-ds btn-secondary-ds text-nowrap">Brand Category</button></a>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Brand Name</div>
                            </th>
                            <th>
                                <div>Parent Brand</div>
                            </th>
                            <th>
                                <div>Brand Category</div>
                            </th>
                            <th>
                                <div>Active</div>
                            </th>
                            <th>
                                <div>Added</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($brands) && count($brands) > 0)
                            @foreach ($brands as $brand)
                                <tr>
                                    <td>{{ $brand->brand_name }}</td>
                                    <td>{{ $brand->parent ? $brand->parent->brand_name : '-' }}</td>
                                    <td>{{ $brand->brandCategories->pluck('name')->implode(', ') ?: '-' }}</td>
                                    <td>{{ ($brand->status == 1) ? 'Yes' : 'No' }}</td>
                                    <td>{{ $brand->created_at }}</td>
                                    <td>
                                        <a href="{{ route($routePrefix.'.edit.brand', ['id' => $brand->id]) }}">
                                            <button type="button" class="btn-ds btn-primary-ds">
                                                <span>Edit</span>
                                            </button>
                                        </a>
                                        <form method="POST" action="{{ route($routePrefix.'.delete.brand', $brand->id) }}" class="d-inline" onsubmit="return confirm('Delete this brand?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-ds btn-danger-ds"><span>Delete</span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else   
                            <tr><td class="text-center" colspan="5">Data Not Found.</td></tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $brands->appends(request()->query())->links('vendor.pagination.custom') }}
                </div>
            </div>     
        </div>
    </div>
@endsection
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script>
    $(document).ready(function() {
        const brandsExportBaseUrl = @json($brandsExportRoute ?? null);

        function updateBrandsExportButton() {
            if (!brandsExportBaseUrl) {
                return;
            }
            const params = new URLSearchParams();
            const searchTerm = $('#search').val();
            const brandCategoryId = $('#brand_category_id').val();
            if (searchTerm) {
                params.set('search', searchTerm);
            }
            if (brandCategoryId) {
                params.set('brand_category_id', brandCategoryId);
            }
            const query = params.toString();
            $('.brands-export-btn').attr('href', brandsExportBaseUrl + (query ? '?' + query : ''));
        }

        function refresh() {
            const searchTerm = $('#search').val();
            const brandCategoryId = $('#brand_category_id').val();
            loadFilteredClients(searchTerm, brandCategoryId);
            updateBrandsExportButton();
        }

        $('#search').on('input', function() {
            refresh();
        });

        $('#brand_category_id').on('change', function() {
            refresh();
        });

        updateBrandsExportButton();

        function loadFilteredClients(searchTerm, brandCategoryId) {
            $.ajax({
                url: "{{ route($routePrefix.'.brands') }}",
                type: 'GET',
                data: {
                    search: searchTerm,
                    brand_category_id: brandCategoryId
                },
                success: function(response) {
                    var rows = '';

                    $('.total-data').text(response.total ?? 0);

                    if (response.brands && response.brands.length > 0) {
                        $.each(response.brands, function(index, brand) {
                            var date = new Date(brand.created_at);
                            var formattedDate = date.getFullYear() + '-' +
                                ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                                ('0' + date.getDate()).slice(-2) + ' ' +
                                ('0' + date.getHours()).slice(-2) + ':' +
                                ('0' + date.getMinutes()).slice(-2) + ':' +
                                ('0' + date.getSeconds()).slice(-2);
                            var parentName = (brand.parent && brand.parent.brand_name) ? brand.parent.brand_name : '-';

                            var categoryNames = '-';
                            if (brand.brand_categories && brand.brand_categories.length > 0) {
                                categoryNames = brand.brand_categories.map(function(c) { return c.name; }).join(', ');
                            }
                            rows += '<tr>';
                            rows += '<td>' + brand.brand_name + '</td>';
                            rows += '<td>' + parentName + '</td>';
                            rows += '<td>' + categoryNames + '</td>';
                            rows += '<td>' + (brand.status == 1 ? 'Yes' : 'No') + '</td>';
                            rows += '<td>' + formattedDate + '</td>';
                            rows += '<td><a href="/{{ $routePrefix }}/edit-brand/' + brand.id + '">';
                            rows += '<button type="button" class="btn-ds btn-primary-ds"><span>Edit</span></button></a></td>';
                            rows += '</tr>';
                        });
                    } else {
                        rows = '<tr><td colspan="6" class="text-center">Data not found</td></tr>';
                    }

                    $('table tbody').html(rows);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }
    });


</script>