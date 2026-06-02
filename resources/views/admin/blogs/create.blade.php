@extends('admin.layout.app')
@section('title', 'Create Blog')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/blogs-index') }}">Back</a>

                <form id="edit_farmer" action="{{ route('blog.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Create Blog</h4>
                                <div class="row mx-0 px-4">

                                    <!-- Title -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="title">Title <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title"
                                                value="{{ old('title') }}" placeholder="Enter title" required autofocus>
                                            @error('title')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Slug -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="slug">Slug <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control" id="slug" name="slug"
                                                value="{{ old('slug') }}" placeholder="slug" readonly>
                                            @error('slug')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>


                                    <!-- Image -->
                                    <div class="col-sm-12 pl-sm-0 pr-sm-3 w-100">
                                        <div class="form-group">
                                            <label for="Image">Image <span style="color: red;">*</span></label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                                id="conent" name="image" placeholder="Enter Image" required autofocus>
                                            <small class="text-danger">Note: Maximum image size allowed is 2MB</small>
                                            @error('image')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>


                                    <!-- Description -->
                                    <div class="col-sm-12 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="email">Description <span style="color: red;">*</span></label>
                                            <textarea type="text" class="form-control" id="content" name="content" placeholder="Enter Description" required
                                                autofocus></textarea>
                                            <div id="content-error" class="text-danger" style="display:none;">Description is
                                                required and must contain valid text.</div>
                                            @error('description')
                                                <div class="text-danger">{{ $message }}</div>
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
    <script>
        $(document).ready(function() {
            // CKEditor Init
            if (document.getElementById('content')) {
                CKEDITOR.replace('content');

                // Hide error when CKEditor is focused
                CKEDITOR.instances['content'].on('focus', function() {
                    $('#content-error').hide();
                });
            }

            // Slugify Function
            function slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }

            // Auto-fill slug
            $('#title').on('input', function() {
                const title = $(this).val();
                $('#slug').val(slugify(title));
            });

            // Validate CKEditor content on submit
            $('#edit_farmer').on('submit', function(e) {
                let content = CKEDITOR.instances['content'].getData().trim();
                let plainText = $('<div>').html(content).text().trim();
                let onlySymbolsOrEmpty = plainText === '' || /^[\s\W_]+$/.test(plainText);

                if (onlySymbolsOrEmpty) {
                    e.preventDefault();

                    $('#content-error')
                        .text('The description field is required.')
                        .show();

                    CKEDITOR.instances['content'].focus();
                    return false;
                }
            });
        });
    </script>
@endsection
