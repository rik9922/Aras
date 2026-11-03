@extends('layout.app', ['page_name' => 'Reset Password'])
<style>
    .for-eye {
        position: relative;
    }
    
    .eye-btn {
       position: absolute;
        right: 6px;
        bottom: 4px;
    }
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
                            <div class="h4 fw-bold mb-4">Reset Password</div>
                            <input type="hidden" name="email" value="{{$email}}" id="email" />
                            <input type="hidden" name="code" value="{{$code}}" id="code" />
                            
                            <!-- New Password Field -->
                            <div class="mb-3 for-eye">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" id="password" placeholder="Enter Password" />
                                <button class="btn eye-btn" type="button" id="toggle-password"> <i class="fa-solid fa-eye-slash"></i> </button>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="mb-3 for-eye">
                                <label for="passwordConfirm" class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" id="passwordConfirm" placeholder="Confirm Password" />
                                <button class="btn eye-btn" type="button" id="toggle-password-confirm"> <i class="fa-solid fa-eye-slash"></i> </button>
                            </div>

                            <div class="d-grid pt-3">
                                <button type="submit" id="submitBtn" class="btn btn-design py-2 mb-2">Reset Password</button>
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
    // Functionality to toggle password visibility
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle New Password Visibility
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            const passwordIcon = togglePassword.querySelector('i');
            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.classList.remove('fa-eye-slash');
                    passwordIcon.classList.add('fa-eye');
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.classList.remove('fa-eye');
                    passwordIcon.classList.add('fa-eye-slash');
                }
            });
        }

        // Toggle Confirm Password Visibility
        const passwordConfirmInput = document.getElementById('passwordConfirm');
        const togglePasswordConfirm = document.getElementById('toggle-password-confirm');
        if (togglePasswordConfirm) {
            const passwordConfirmIcon = togglePasswordConfirm.querySelector('i');
            togglePasswordConfirm.addEventListener('click', function() {
                if (passwordConfirmInput.type === 'password') {
                    passwordConfirmInput.type = 'text';
                    passwordConfirmIcon.classList.remove('fa-eye-slash');
                    passwordConfirmIcon.classList.add('fa-eye');
                } else {
                    passwordConfirmInput.type = 'password';
                    passwordConfirmIcon.classList.remove('fa-eye');
                    passwordConfirmIcon.classList.add('fa-eye-slash');
                }
            });
        }
    });

    // Handle the form submission using AJAX
    $(document).ready(function () {
        $('#submitBtn').on('click', function (e) {
            e.preventDefault();

            var email = $('#email').val().trim(); 
            var code = $('#code').val();
            var password = $('#password').val();
            var passwordConfirm = $('#passwordConfirm').val();

            var formData = {
                email: email,
                code: code,
                password: password,
                password_confirmation: passwordConfirm,
            };

            $('#messageContainer').html('');
            $.ajax({
                url: '/api/reset-password',
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === "failed") {
                        var errorMessage = response.message;
                        $('#messageContainer').html('<span style="font-size:15px;color:red;font-weight: 500">' + errorMessage + '</span>');
                    } else if (response.status === "success") {
                        var successMessage = response.message;
                        $('#messageContainer').html('<span class="text-success" style="font-size: 15px;color:#0fb500;font-weight:500">' + successMessage + '</span>');
                        window.location.href = '/login';
                    }
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', xhr);
                    console.log('Status:', status);
                    console.log('Error:', error);

                    // Show a generic error message if AJAX fails
                    $('#messageContainer').html('<span style="font-size:15px;color:red;font-weight: 500">An error occurred. Please try again.</span>');
                }
            });
        });
    });
</script>
