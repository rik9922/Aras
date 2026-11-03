@extends('layout.app',['page_name' => 'Event List'])
@section('content')
    <div class="container py-3">
        <div class="d-grid">
           
        </div>
        <div class="page-card-design">
            <form method="POST" action="">
                @csrf
        
                <div class="mb-3">
                    <label for="account_name" class="form-label">Account Name</label>
                    <input type="text" name="account_name" id="account_name" class="form-control" required>
                </div>
        
                <div class="mb-3">
                    <label for="routing_number" class="form-label">Routing Number</label>
                    <input type="text" name="routing_number" id="routing_number" class="form-control" required>
                </div>
        
                <div class="mb-3">
                    <label for="account_number" class="form-label">Account Number</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" required>
                </div>
        
                <div class="mb-3">
                    <label for="type" class="form-label">Account Type</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="checking">Checking</option>
                        <option value="savings">Savings</option>
                    </select>
                </div>
        
                <button type="submit" class="btn btn-primary btn btn-design mt-3">Add Bank Account</button>
                <button type="button" class="btn btn-design grey-color mt-3" onclick="window.history.back()">Back</button>
            </form>
        </div>
    </div>

@endsection