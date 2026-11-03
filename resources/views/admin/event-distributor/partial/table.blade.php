@if(!empty($eventDistributor) && count($eventDistributor) > 0)
    @foreach ($eventDistributor as $distributor)
        <tr>
            <td>{{ $distributor->name }}</td>
            {{-- <td>{{ $distributor->email }}</td>
            <td>{{ $distributor->phone }}</td>
            <td>{{ $distributor->company_name }}</td>
            <td>{{ $distributor->address }}</td> --}}
            <td>
                <div>
                    <button type="button" class="btn-ds btn-secondary-ds btn-edit-distributor"
                        data-id="{{ $distributor->id }}"
                        data-name="{{ $distributor->name }}">
                        <span>Edit</span>
                    </button>
                    <button type="button" class="btn-ds btn-danger-ds btn-delete-distributor ms-1"
                        data-id="{{ $distributor->id }}"
                        data-url="{{ route((request()->routeIs('manager.*') ? 'manager' : 'admin').'.delete.event-destributor', $distributor->id) }}">
                        <span>Delete</span>
                    </button>
                </div>
            </td>
        </tr>
    @endforeach
@else 
<tr><td colspan="6" class="text-center">Data not found</td></tr>
@endif
