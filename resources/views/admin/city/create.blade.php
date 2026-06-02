@extends('admin.layout.app')
@section('title', 'Create City')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ route('city.index') }}">Back</a>

                <form action="{{ route('city.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Create City</h4>
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
