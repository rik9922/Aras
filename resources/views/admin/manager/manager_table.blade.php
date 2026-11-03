@if (!empty($managers) && count($managers) > 0)
    @foreach ($managers as $manager)
        <tr>
            <td>{{ $manager->first_name }}</td>
            <td>{{ $manager->last_name }}</td>
            <td>{{ $manager->email }}</td>
            <td>{{ $manager->phone }}</td>
            <td>
                <span class="span-text @if($manager->status == '1') success @else danger @endif">{{ ($manager->status == '1') ? 'Active' : 'Inactive' }}</span>
            </td>
            <td>
                @if ($manager->managerMarkets->isNotEmpty())
                    {{ $manager->managerMarkets->pluck('name')->join(', ') }}
                @else
                    —
                @endif
            </td>
            <td>
                <div>
                    <a href="{{ route('admin.edit.manager', ['id' => $manager->id]) }}">
                        <button type="button" class="btn-ds btn-primary-ds">
                            <span>Edit</span>
                        </button>
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td class="text-center" colspan="7">Data not found</td>
    </tr>
@endif
