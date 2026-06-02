@extends('admin.layout.app')
@section('title', 'Edit Region')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/region') }}">Back</a>

                <form id="edit_region" action="{{ route('region.update', $region->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST') <!-- Correct method for updating -->

                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Region</h4>
                                <div class="row mx-0 px-4">

                                    <!-- Name Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="name">Name <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $region->name) }}"
                                                placeholder="Enter Name" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Code Field -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="code">Code</label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                                id="code" name="code" value="{{ old('code', $region->code) }}"
                                                placeholder="Enter Code">
                                            @error('code')
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
@endsection
