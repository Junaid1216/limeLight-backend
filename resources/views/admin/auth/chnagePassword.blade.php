@extends('admin.auth.layout.app')
@section('title', 'Change Password ')
@section('content')
    <section class="section">
        <div class="container mt-5">
            <div class="row">
                <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Reset Password</h4>
                        </div>
                        <div class="card-body">
                            @if (session()->has('error_message'))
                                <p class="text-danger">The password and confirmation password do not match</p>
                            @else
                                <p class="text-muted">Enter Your New Password</p>
                            @endif
                            <form method="POST" action="{{ url('admin-reset-password') }}">
                                @csrf
                                <input value="{{ $user->email }}" type="hidden" name="email">
                                <div class="">
                                    <div class="form-group mb-2 position-relative">
                                        <label> New Password</label>
                                        <input type="password" placeholder="Enter Password" name="password" id="password"
                                            class="form-control">
                                        <span onclick="togglePasswordVisibility()" class="fa fa-eye position-absolute"
                                            style="top: 2.67rem; right:0.5rem; cursor:pointer;"
                                            id="togglePasswordIcon"></span>
                                        <div id="password-error" class="text-danger"></div>
                                        @error('password')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group mb-2 position-relative">
                                    <label>Confrim Password</label>
                                    <input type="password" placeholder="Enter Password" name="confirmPassword"
                                        id="confirmPassword" class="form-control">
                                    <span onclick="toggleConfirmPasswordVisibility()" class="fa fa-eye position-absolute"
                                        style="top: 2.67rem; right:0.5rem; cursor:pointer;"
                                        id="toggleConfirmPasswordIcon"></span>
                                    <div id="password-error" class="text-danger"></div>
                                    @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group mt-4 mb-0">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        Reset Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    @if (\Illuminate\Support\Facades\Session::has('message'))
        <script>
            toastr.success('{{ \Illuminate\Support\Facades\Session::get('message') }}');
        </script>
    @endif

    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function toggleConfirmPasswordVisibility() {
            const passwordField = document.getElementById('confirmPassword');
            const toggleIcon = document.getElementById('toggleConfirmPasswordIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
