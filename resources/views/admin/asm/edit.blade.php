@extends('admin.layout.app')
@section('title', 'Edit Area Sales Manager')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/asm') }}">Back</a>

                <form id="edit_farmer" action="{{ route('asm.update', $asm->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST') <!-- Correct method for updating -->

                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Area Sales Manager</h4>
                                <div class="row mx-0 px-4">
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="employee_id">Employee Id <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror"
                                                required id="employee_id" name="employee_id" value="{{ old('employee_id', $asm->employee_id) }}"
                                                placeholder="Enter Employee Id" required autofocus>
                                            @error('employee_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- Name Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="name">Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $asm->name) }}"
                                                placeholder="Enter Name" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Email Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="email">Email <span style="color: red;">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                id="email" name="email" value="{{ old('email', $asm->email) }}"
                                                placeholder="Enter Email" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                        <!-- Region Field -->
                                        <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                            <div class="form-group">
                                                <label for="region_id">Select Region <span style="color: red;">*</span></label>
                                                <select class="form-control @error('region_id') is-invalid @enderror"
                                                    id="region_id" name="region_id" required>
                                                    <option value="" disabled>-- Select Region --</option>
                                                    @foreach ($regions as $region)
                                                        <option value="{{ $region->id }}"
                                                            {{ old('region_id', $asm->region_id) == $region->id ? 'selected' : '' }}>
                                                            {{ $region->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('region_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                       <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                            <div class="form-group">
                                                <label for="designation_id">Select Designation <span style="color: red;">*</span></label>
                                                <select class="form-control @error('designation_id') is-invalid @enderror"
                                                    id="designation_id" name="designation_id" required>
                                                    <option value="" disabled>-- Select Designation --</option>
                                                    @foreach ($designations as $designation)
                                                        <option value="{{ $designation->id }}"
                                                            {{ old('designation_id', $asm->designation_id) == $designation->id ? 'selected' : '' }}>
                                                            {{ $designation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('designation_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                                            


                                    <!-- Password Field (fixed) -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group position-relative">
                                            <label for="password">Password (Optional)</label>
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror" id="password"
                                                name="password" placeholder="Password">
                                            <span class="fa fa-eye toggle-password position-absolute"
                                                style="top: 42px; right: 15px; cursor: pointer;"></span>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>


                                    <!-- Submit Button -->
                                    <div class="card-footer text-center row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary mr-1 btn-bg" id="submit">Save
                                                Changes</button>
                                        </div>
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
    @if (session('message'))
        <script>
            toastr.success('{{ session('message') }}');
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
                const $feedback = $(this).parent().find('.invalid-feedback');
                if ($feedback.length) {
                    $feedback.hide();
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
@endsection
