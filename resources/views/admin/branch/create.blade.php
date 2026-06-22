@extends('admin.layout.app')
@section('title', 'Create Branch')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ route('branch.index') }}">Back</a>

                <form action="{{ route('branch.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Create Branch</h4>
                                <div class="row mx-0 px-4">

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

                                                <label for="city">City <span style="color: red;">*</span></label>

                                                <select
                                                    class="form-control @error('city') is-invalid @enderror"
                                                    id="city"
                                                    name="city"
                                                    autofocus
                                                >

                                                    <option value="">Select City</option>

                                                    @foreach($cities as $city)

                                                        <option
                                                            value="{{ $city->name }}"
                                                            {{ old('city') == $city->name ? 'selected' : '' }}
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

                                    <!-- Address -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="address">Address</label>
                                            <input type="text" class="form-control @error('address') is-invalid @enderror"
                                                 id="address" name="address" value="{{ old('address') }}"
                                                placeholder="Enter Address"  autofocus>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                            <div class="form-group">

                                                <label for="region">Region <span style="color: red;">*</span></label>

                                                <select
                                                    class="form-control @error('region') is-invalid @enderror"
                                                    id="region"
                                                    name="region"
                                                    autofocus
                                                >

                                                    <option value="">Select Region</option>

                                                    @foreach($regions as $region)

                                                        <option
                                                            value="{{ $region->id }}"
                                                            {{ old('region') == $region->id ? 'selected' : '' }}
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
