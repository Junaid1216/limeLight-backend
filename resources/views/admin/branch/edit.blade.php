@extends('admin.layout.app')
@section('title', 'Edit Branch')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/branch') }}">Back</a>

                <form id="edit_branch" action="{{ route('branch.update', $branch->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST') <!-- Correct method for updating -->

                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Branch</h4>
                                <div class="row mx-0 px-4">

                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="branch_id">Branch Id <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('branch_id') is-invalid @enderror"
                                                id="branch_id" name="branch_id" value="{{ old('branch_id', $branch->branch_id) }}"
                                                placeholder="Enter Branch Id" required>
                                            @error('branch_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Name Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="name">Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $branch->name) }}"
                                                placeholder="Enter Name" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- City Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">

                                            <label for="city">
                                                City <span style="color: red;">*</span>
                                            </label>

                                            <select
                                                class="form-control @error('city') is-invalid @enderror"
                                                id="city"
                                                name="city"
                                                required
                                            >

                                                <option value="">Select City</option>

                                                @foreach($cities as $city)

                                                    <option
                                                        value="{{ $city->name }}"
                                                        {{ old('city', $branch->city) == $city->name ? 'selected' : '' }}
                                                    >
                                                        {{ $city->name }}
                                                    </option>

                                                @endforeach

                                            </select>

                                            @error('city')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>
                                    </div>

                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="address">Address<span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('address') is-invalid @enderror"
                                                id="address" name="address" value="{{ old('address', $branch->address) }}"
                                                placeholder="Enter Address" required>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                     <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">

                                            <label for="region">
                                                Region <span style="color: red;">*</span>
                                            </label>

                                            <select
                                                class="form-control @error('region') is-invalid @enderror"
                                                id="region"
                                                name="region"
                                                required
                                            >

                                                <option value="">Select Region</option>

                                                @foreach($regions as $region)

                                                    <option
                                                         value="{{ $region->id }}"
                                                         {{ old('region_id', $branch->region_id) == $region->id ? 'selected' : '' }}
                                                    >
                                                        {{ $region->name }}
                                                    </option>

                                                @endforeach

                                            </select>

                                            @error('region')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
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
@endsection
