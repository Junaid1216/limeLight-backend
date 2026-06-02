@extends('admin.layout.app')
@section('title', 'Edit Sub Admin')

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/subadmin') }}">Back</a>
                <form action="{{ route('subadmin.update', $subAdmin->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST')

                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Sub Admin</h4>
                                <div class="row mx-0 px-4">
                                    {{-- Name --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="name">Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                name="name" id="name" value="{{ old('name', $subAdmin->name) }}"
                                                placeholder="Enter Name">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Email --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="email">Email <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('email') is-invalid @enderror"
                                                name="email" id="email" value="{{ old('email', $subAdmin->email) }}"
                                                placeholder="Enter Email">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Role --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="role">Role <span style="color: red;">*</span></label>
                                            <select name="role" id="role"
                                                class="form-control @error('role') is-invalid @enderror">
                                                <option value="" disabled>-- Select Role --</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                        {{ old('role', $currentRoleId) == $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Image --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="image">Image (Optional)</label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                                name="image" id="image">
                                            <small class="text-danger">Note: Maximum image size allowed is 2MB</small>
                                            @if ($subAdmin->image)
                                                <div class="mt-2">
                                                    <img src="{{ asset($subAdmin->image) }}" width="100">
                                                </div>
                                            @endif
                                            @error('image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@section('js')
    @if (session('success'))
        <script>
            toastr.success('{{ session('success') }}');
        </script>
    @endif

    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.toggle-password').on('click', function() {
                const $passwordInput = $('#password');
                const $icon = $(this);

                if ($passwordInput.attr('type') === 'password') {
                    $passwordInput.attr('type', 'text');
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $passwordInput.attr('type', 'password');
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Hide validation errors on focus
            $('input, select, textarea').on('focus', function() {
                const $feedback = $(this).siblings('.invalid-feedback');
                if ($feedback.length) {
                    $feedback.hide();
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
@endsection
