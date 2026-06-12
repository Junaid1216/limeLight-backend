@extends('admin.layout.app')
@section('title', 'Create Branch Manager')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ route('branch.manager.index') }}">Back</a>

                <form action="{{ route('branch.manager.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Create Branch Manager</h4>
                                <div class="row mx-0 px-4">
                                     <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="employee_id">Employee Id <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror"
                                                required id="employee_id" name="employee_id" value="{{ old('employee_id') }}"
                                                placeholder="Enter Employee Id" required autofocus>
                                            @error('employee_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Name -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="name">Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                required id="name" name="name" value="{{ old('name') }}"
                                                placeholder="Enter Name" required autofocus>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- City -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="email">Email <span style="color: red;">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                 id="email" name="email" value="{{ old('email') }}"
                                                placeholder="Enter Email"  autofocus>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="branch_id">Select Branch <span style="color: red;">*</span></label>
                                            <select class="form-control @error('branch_id') is-invalid @enderror" 
                                                    id="branch_id"
                                                    name="branch_id" required>

                                                <option value="" disabled selected>-- Select branch --</option>

                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach

                                            </select>

                                            @error('branch_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                     <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="designation_id">Select Designation <span style="color: red;">*</span></label>
                                            <select class="form-control @error('designation_id') is-invalid @enderror" 
                                                    id="designation_id"
                                                    name="designation_id" required>

                                                <option value="" disabled selected>-- Select designation --</option>

                                                @foreach ($designations as $designation)
                                                    <option value="{{ $designation->id }}"
                                                        {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
                                                        {{ $designation->name }}
                                                    </option>
                                                @endforeach

                                            </select>

                                            @error('designation_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                     <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="image">Image <span style="color: red;">*</span></label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                                id="image" name="image" >
                                            <small class="text-danger">Note: Maximum image size allowed is 2MB</small>
                                            @error('image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                </div>

                                <!-- Submit Button -->
                                <div class="card-footer text-center row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary mr-1 btn-bg">Save</button>
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
    @if (session('success'))
        <script>
            toastr.success('{{ session('success') }}');
        </script>
    @endif
@endsection
