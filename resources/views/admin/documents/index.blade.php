@extends('layout.app', ['page_name' => 'Aras Documents'])
@section('content')
<style>
.dm-uploader{
    cursor:default;
    -webkit-touch-callout:none;
    -webkit-user-select:none;
    -khtml-user-select:none;
    -moz-user-select:none;
    -ms-user-select:none;
    user-select:none;
}
.dm-uploader .btn{
    position:relative;
    overflow:hidden;
}
.dm-uploader .btn input[type=file]{
    position:absolute;
    top:0;
    right:0;
    margin:0;
    border:solid transparent;
    width:100%;
    opacity:0;
    cursor:pointer;
} 
.row {
	margin-bottom: 1rem;
}
[class*="col-"] {
	padding-top: 1rem;
	padding-bottom: 1rem;
}
hr {
	margin-top: 2rem;
	margin-bottom: 2rem;
}
#files {
    overflow-y: scroll !important;
    min-height: 320px;
}
@media (min-width: 768px) {
	#files {
		min-height: 0;
	}
}
#debug {
	overflow-y: scroll !important;
	height: 180px;	
}

.dm-uploader {
	border: 0.25rem dashed #A5A5C7;
	text-align: center;
}
.dm-uploader.active {
	border-color: red;

	border-style: solid;
}
</style>
<div class="container py-2">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="h5 mb-0">Aras Documents : <span class="total-data">{{ $documents->total() }}</span></div>
            <form method="GET" action="{{ route('admin.documents') }}" class="d-flex align-items-center gap-2" id="search-form">
                <div class="input-group" style="width:280px;">
                    <span class="input-group-text"><i class="bi bi-search"></i>🔍</span>
                    <input type="text" class="form-control" name="search" id="search-input"
                           value="{{ $search ?? '' }}"
                           placeholder="Search by file name…"
                           autocomplete="off">
                    @if(!empty($search))
                        <a href="{{ route('admin.documents') }}" class="btn-ds btn-secondary-outline">✕</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-6 col-sm-12">
                  <!-- Our markup, the important part here! -->
                  <div id="drag-and-drop-zone" class="dm-uploader p-5">
                    <h3 class="mb-5 mt-5 text-muted">Drag &amp; drop files here</h3>
        
                    <div class="btn-ds btn-primary-ds btn-block mb-5">
                        <span>Open the file Browser</span>
                        <input type="file" title='Click to add Files' />
                        @csrf
                    </div>
                  </div><!-- /uploader -->
        
                </div>
                <div class="col-md-6 col-sm-12">
                  <div class="card h-100">
                    <div class="card-header">
                      File List
                    </div>
        
                    <ul class="list-unstyled p-2 d-flex flex-column col" id="files">
                      <li class="text-muted text-center empty">No files uploaded.</li>
                    </ul>
                  </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><div class="text-nowrap">File Name</div></th>
                            <th><div>Type</div></th>
                            <th><div class="text-nowrap">File Size</div></th>
                            <th><div class="text-nowrap">Date Uploaded</div></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                     <tbody id="ambassador-table">
                        @if (!empty($documents) && count($documents) > 0)
                            @foreach ($documents as $document)
                                <tr>
                                    <td>{{ $document->file_name }}</td>
                                    <td>{{ strtoupper(pathinfo($document->url, PATHINFO_EXTENSION))}}</td>
                                    <td class="text-nowrap">
                                        @if($document->file_size)
                                            @php
                                                $bytes = $document->file_size;
                                                if ($bytes >= 1048576)      { $size = number_format($bytes/1048576,2).' MB'; }
                                                elseif ($bytes >= 1024)    { $size = number_format($bytes/1024,2).' KB'; }
                                                else                        { $size = $bytes.' B'; }
                                            @endphp
                                            {{ $size }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $document->created_at ? $document->created_at->format('M d, Y H:i') : '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="{{$document->url}}" target="_blank">
                                                <button type="button" class="btn-ds btn-primary-ds btn-sm">
                                                    <span>View</span>
                                                </button>
                                            </a>
                                            <button type="button"
                                                class="btn-ds btn-warning-ds btn-sm edit-doc-btn"
                                                data-id="{{ $document->id }}"
                                                data-name="{{ e($document->file_name) }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('admin.documents.destroy', $document->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to permanently delete this document? This action cannot be undone.');"
                                                  style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ds btn-danger-ds btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="text-center" colspan="5">Data not found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3" id="pagination-wrapper">
                    {{ $documents->links('vendor.pagination.custom') }}
                </div>
                <div class="d-flex justify-content-end mt-3">
                </div>
            </div>
        </div>
    </div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDocumentForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editDocumentModalLabel">Edit Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_file_name" class="form-label">File Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_file_name" name="file_name"
                               required maxlength="255" placeholder="Enter document name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_file" class="form-label">Replace File <span class="text-muted">(optional)</span></label>
                        <input type="file" class="form-control" id="edit_file" name="file">
                        <div class="form-text">Leave blank to keep the existing file. Uploading a new file will replace the current one.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ds btn-secondary-ds" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ds btn-primary-ds">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')

<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{asset('assets/js/jquery.dm-uploader.min.js')}}"></script>
<script src="{{asset('assets/js/demo-ui.js')}}"></script>
<script src="{{asset('assets/js/demo-config.js')}}"></script>
<script type="text/html" id="files-template">
  <li class="media">
    <div class="media-body mb-1">
      <p class="mb-2">
        <strong>%%filename%%</strong> - Status: <span class="text-muted">Waiting</span>
      </p>
      <div class="progress mb-2">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
          role="progressbar"
          style="width: 0%" 
          aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
        </div>
      </div>
      <hr class="mt-1 mb-1" />
    </div>
  </li>
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live search: debounce + auto-submit on typing
    var searchInput = document.getElementById('search-input');
    var searchForm  = document.getElementById('search-form');
    var searchTimer;
    if (searchInput && searchForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchForm.submit();
            }, 400);
        });
    }

    document.querySelectorAll('.edit-doc-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id   = this.dataset.id;
            var name = this.dataset.name;
            document.getElementById('edit_file_name').value = name;
            // Clear any previously selected file
            document.getElementById('edit_file').value = '';
            document.getElementById('editDocumentForm').action = '/admin/documents/' + id;
            var modalEl = document.getElementById('editDocumentModal');
            var modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    });
});
</script>
@endpush