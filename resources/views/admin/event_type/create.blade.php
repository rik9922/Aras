@extends('layout.app', ['page_name' => 'Create Event Type'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Event Type Details</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <form action="{{route("admin.store.eventtypes")}}" method="POST">@csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="brandName" class="form-label text-uppercase">Name</label>
                            <input type="text" class="form-control shadow-none  @error('name') is-invalid @enderror"
                                name="name" id="brandName" placeholder="Event Type">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="recap_type" class="form-label text-uppercase">Recap Form</label>
                            <select name="recap_type" id="recap_type" class="form-select shadow-none @error('recap_type') is-invalid @enderror">
                                <option value="">Off Premise (default)</option>
                                <option value="off_premise" {{ old('recap_type') === 'off_premise' ? 'selected' : '' }}>Off Premise</option>
                                <option value="on_premise" {{ old('recap_type') === 'on_premise' ? 'selected' : '' }}>On Premise</option>
                                <option value="special_event" {{ old('recap_type') === 'special_event' ? 'selected' : '' }}>Special Event</option>
                            </select>
                            @error('recap_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="form-label text-uppercase">Description</label>
                            <textarea type="text" class="form-control shadow-none @error('description') is-invalid @enderror" name="description"
                                id="description" placeholder="Description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
                <div class="col-2">
                    <button type="submit" class="btn-ds btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
