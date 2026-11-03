@extends('layout.app', ['page_name' => 'Forgot Password'])
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
                            <input type="hidden" name="timezone" id="timezone">
                            <div class="h4 fw-bold mb-4">Forgot Password</div>
                                <div class="mb-3">
                                    <label for="emailAddress" class="form-label">Email address</label>
                                    <div class="inner-field rounded overflow-hidden" style="border: 1px solid #747D8D;">
                                        <i class="fa-solid fa-envelope"></i>
                                        <input type="email" name="email" id="emailAddress" class="form-control shadow-none @error('email') is-invalid @enderror" placeholder="Enter Email address" />
                                    </div>
                                    <!-- Error or Success Message will be appended here after AJAX call -->
                                    <div id="messageContainer"></div>
                                </div>

                            <div class="d-grid pt-3">
                                <!-- Submit Button -->
                                <button type="button" id="submitBtn" class="btn btn-design py-2 mb-2">Submit</button>
                                <!-- Exit Button -->
                                <button type="button" id="exitBtn" class="btn btn-design py-2">Exit</button>
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
    $('#submitBtn').on('click', function (e) {
        e.preventDefault();

        var email = $('#emailAddress').val().trim(); 
        if (email === "") {
            alert("Please enter your email address.");
            return;
        }

        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailPattern.test(email)) {
            alert("Please enter a valid email address.");
            return;
        }

        var formData = {
            email: email,
        };
        $('#messageContainer').html('');
        $.ajax({
            url: '/api/forgot-password', // Your route URL
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.status === "failed") {
                    var errorMessage = response.message;
                    $('#messageContainer').html('<span style="color: red; font-size: 15px; font-weight: 500">' + errorMessage + '</span>');
                } else if (response.status === "success") {
                    var successMessage = response.message;
                    $('#messageContainer').html('<span style="color: #0fb500;font-size: 15px;font-weight:500">' + successMessage + '</span>');
                    setTimeout(function () {
                       window.location.href = '/verification-code?email=' + encodeURIComponent(email);
                    }, 3000);
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX Error:', xhr);
                console.log('Status:', status);
                console.log('Error:', error);

                // Show a generic error message if AJAX fails
                $('#messageContainer').html('<span style="font-size: 15px; font-weight: 500; color: red;">An error occurred. Please try again.</span>');
            }
        });
    });

    // Handle exit button click
    $('#exitBtn').on('click', function () {
        window.location.href = "/login"; // Redirect to login page or any other desired page
    });
});
</script>
