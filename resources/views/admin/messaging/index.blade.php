@extends('layout.app', ['page_name' => 'Messaging'])
<link href="{{ asset('assets/css/quill.snow.css') }}" rel="stylesheet" />
@section('content')
    <div class="container py-3">
        <div class="page-card-design">
            <div class="row mt-3 px-2">
                <div class="col-12 pb-3 px-0 border-bottom">
                    <div class="h5 fw-bold text-uppercase">Group Notifications by Market</div>
                </div>
            </div>
            <form action="{{route('admin.send-message')}}" method="POST">@csrf
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="selectMarket" class="form-label text-uppercase">Market</label>
                            <select class="form-select shadow-none multiple-select2 @error('market_id') is-invalid @enderror" id="selectMarket" name="market_id[]" multiple="multiple" data-placeholder="Select Market(s)">
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}">{{ $market->name }}</option>
                                @endforeach
                            </select>
                            @error('market_id')
                                <div class="text-danger w-100">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div id="marketMessageBoxes">
                    @foreach ($markets as $market)
                        <div class="row market-message-box" data-market-id="{{ $market->id }}" style="display: none;">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label text-uppercase">Notification Content for {{ $market->name }}</label>
                                    <div id="editor-{{ $market->id }}" class="quill-editor" style="height: 200px;"></div>
                                    <input type="hidden" name="content[{{ $market->id }}]" id="content-{{ $market->id }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <button type="submit" class="btn-ds btn-primary-ds text-uppercase">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>
    <script src="{{ asset('assets/js/quill.js') }}"></script>
    <script>
        $(document).ready(function() {
            var editors = {};
            
            $('.multiple-select2').select2({
                placeholder: $(this).data('placeholder'),
                allowClear: true
            });

            // Initialize Quill editors for each market
            $('.market-message-box').each(function() {
                var marketId = $(this).data('market-id');
                var editorDiv = document.getElementById('editor-' + marketId);
                var hiddenInput = document.getElementById('content-' + marketId);

                var quill = new Quill(editorDiv, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ font: [] }, { size: [] }],
                            ["bold", "italic", "underline", "strike"],
                            [{ color: [] }, { background: [] }],
                            [{ script: "super" }, { script: "sub" }],
                            [{ header: "1" }, { header: "2" }, "blockquote", "code-block"],
                            [{ list: "ordered" }, { list: "bullet" }, { indent: "-1" }, { indent: "+1" }],
                            [{ direction: "rtl" }],
                            ["link", "image", "video", "formula"],
                            ["clean"]
                        ]
                    }
                });

                editors[marketId] = quill;

                quill.on('text-change', function() {
                    hiddenInput.value = quill.root.innerHTML;
                });
            });

            // Show/hide message boxes based on selected markets
            $('#selectMarket').on('change', function() {
                var selected = $(this).val();
                $('.market-message-box').each(function() {
                    var marketId = $(this).data('market-id').toString();
                    if (selected.includes(marketId)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
@endsection