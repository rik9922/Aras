@extends('layout.app', ['page_name' => 'Verification Code'])
<style>
    @media screen and (max-width: 1024px) {
        body {
            background-image: none !important;
            background: rgb(245 245 245);
        }
    }
</style>
@section('content')
    <div class="page-content">
        <div class="row" style="--bs-gutter-x: 0rem;">
            <div class="col-xl-7 d-none d-xl-block">
                <div class="row justify-content-center align-items-center" style="height: 100vh;">
                    <img src="{{ asset('assets/images/logo.png') }}" style="height: fit-content; width: 440px;">
                </div>
            </div>
            <div class="col-xl-5">
                <div class="form-section">
                    <div class="form-card">
                        <form id="forgotPasswordForm" action="{{ route('login-user') }}" method="POST">
                            @csrf
                            <div class="h4 fw-bold mb-4">Verification Code</div>
                                <div class="mb-3">
                                    <label for="emailAddress" class="form-label">Enter Code</label>
                                    <div class="inner-field rounded overflow-hidden" style="border: 1px solid #747D8D;">
                                        <i class="fa-solid fa-envelope"></i>
                                        <input type="number" min="0" name="code" id="code" class="form-control shadow-none @error('code') is-invalid @enderror" placeholder="Enter OTP" />
                                    </div>
                                    <input type="hidden" name="email" value="{{$email}}" id="email"/>
                                    <div id="messageContainer"></div>
                                </div>

                            <div class="d-grid pt-3">
                                <!-- Submit Button -->
                                <button type="button" id="submitBtn" class="btn btn-design py-2 mb-2">Submit Code</button>
                                <!-- Exit Button -->
                                <button type="button" id="exitBtn" class="btn btn-design py-2">Send Again</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
    // Handle form submit button click
    $('#submitBtn').on('click', function (e) {
        e.preventDefault();

        var email = $('#email').val().trim(); 
        var code = $('#code').val();

        var formData = {
            email: email,
            code: code
        };

        $('#messageContainer').html('');
        $.ajax({
            url: '/api/verify-otp',
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.status === "failed") {
                    var errorMessage = response.message;
                    $('#messageContainer').html('<span class="text-danger" style="font-size: 14px;">' + errorMessage + '</span>');
                } else if (response.status === "success") {
                    var successMessage = response.message;
                    $('#messageContainer').html('<span class="text-success" style="font-size: 14px;">' + successMessage + '</span>');
                    setTimeout(function () {
                        window.location.href = '/reset-password?email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code);
                    }, 3000); // 3000ms = 3 seconds
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX Error:', xhr); // Log the error response
                console.log('Status:', status); // Log the status
                console.log('Error:', error); // Log the specific error

                // Show a generic error message if AJAX fails
                $('#messageContainer').html('<span class="text-danger" style="font-size: 14px;">An error occurred. Please try again.</span>');
            }
        });
    });

    // Handle exit button click
    $('#exitBtn').on('click', function (e) {
        e.preventDefault();

        var email = $('#email').val().trim(); 

        var formData = {
            email: email,
        };

        $('#messageContainer').html('');
        $.ajax({
            url: '/api/send_otp',
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.status === "failed") {
                    var errorMessage = response.message;
                    $('#messageContainer').html('<span style="font-size:15px;color:red;font-weight: 500">' + errorMessage + '</span>');
                } else if (response.status === "success") {
                    var successMessage = response.message;
                    $('#messageContainer').html('<span style="color: #0fb500;font-size: 15px;font-weight:500">' + successMessage + '</span>');
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX Error:', xhr); // Log the error response
                console.log('Status:', status); // Log the status
                console.log('Error:', error); // Log the specific error

                // Show a generic error message if AJAX fails
                $('#messageContainer').html('<span style="font-size:15px;color:red;font-weight: 500">An error occurred. Please try again.</span>');
            }
        });
    });
});
</script>
