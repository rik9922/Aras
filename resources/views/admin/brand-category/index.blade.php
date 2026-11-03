@extends('layout.app', ['page_name' => 'Brands Category List'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')

    <div class="container">
        <div class="page-contents">
            <div class="d-grid">
                <div class="h5 text-uppercase">Search @if(!empty($brandCategory) && count($brandCategory) > 0){{count($brandCategory)}} @else 0 @endif Brands Category</div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-9 pt-3">
                    <input type="text" class="form-control shadow-none" name="serach"
                        placeholder="Search for a brand category name here" id="searchInput">
                </div>
                <div class="col-md-3 pt-3">
                    <div class="d-grid">
                        <a href="{{ route($routePrefix.'.brand-category.create') }}">
                            <button type="button" class="btn-ds btn-secondary-ds">Add Brand Category</button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>
                                <div>Brand Category Name</div>
                            </th>
                            <th>
                                <div>Note</div>
                            </th>
                            <th>
                                <div>Added</div>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($brandCategory) && count($brandCategory) > 0)
                            @foreach ($brandCategory as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->note }}</td>
                                    <td>{{ $category->created_at }}</td>
                                    <td>
                                        <a href="{{ route($routePrefix.'.edit.brand-category', ['id' => $category->id]) }}">
                                            <button type="button" class="btn-ds btn-primary-ds">
                                                <span>Edit</span>
                                            </button>
                                        </a>
                                        <form method="POST" action="{{ route($routePrefix.'.delete.brand-category', $category->id) }}" class="d-inline" onsubmit="return confirm('Delete this brand category?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-ds btn-danger-ds"><span>Delete</span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="text-center">Data not Found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>            
        </div>
    </div>
@endsection
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function() {
    $('#searchInput').on('input', function() {
        var searchTerm = $(this).val();
        $.ajax({
            url: "{{ route($routePrefix.'.brand-category') }}",
            type: 'GET',
            data: {
                search: searchTerm
            },
            success: function(response) {
                var rows = '';
                if(response.brandCategory.length > 0) {
                    $.each(response.brandCategory, function(index, category) {
                        var date = new Date(category.created_at);
                        var formattedDate = date.getFullYear() + '-' + 
                                            ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
                                            ('0' + date.getDate()).slice(-2) + ' ' + 
                                            ('0' + date.getHours()).slice(-2) + ':' + 
                                            ('0' + date.getMinutes()).slice(-2) + ':' + 
                                            ('0' + date.getSeconds()).slice(-2);

                        rows += '<tr>';
                        rows += '<td>' + category.name + '</td>';
                        rows += '<td>' + category.note + '</td>';
                        rows += '<td>' + formattedDate + '</td>';
                        rows += '<td><a href="/{{ $routePrefix }}/brand-category/edit/' + category.id + '">';
                        rows += '<button type="button" class="btn-ds btn-primary-ds"><span>Edit</span></button></a></td>';
                        rows += '</tr>';
                    });
                } else {
                    rows = '<tr><td colspan="4" class="text-center">Data not found</td></tr>';
                }
                $('table tbody').html(rows);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    });
});

</script>