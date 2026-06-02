@extends('admin.layout.app')
@section('title', 'Edit Blog')
@section('content')

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url('admin/blogs-index') }}">Back</a>

                <form id="edit_blog" action="{{ route('blog.update', $data->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST') {{-- Important for PUT request --}}

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <h4 class="text-center my-4">Edit Blog</h4>
                                <div class="row mx-0 px-4">

                                    {{-- Title --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="title">Title <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title"
                                                value="{{ $data->title }}" placeholder="Enter title" required autofocus>
                                        </div>
                                    </div>

                                    {{-- Slug --}}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="slug">Slug <span style="color: red;">*</span></label>
                                            <input type="text" class="form-control" id="slug" name="slug"
                                                value="{{ $data->slug }}" placeholder="Slug" readonly>
                                        </div>
                                    </div>

                                    {{-- Image --}}
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="image">Image <span style="color: red;">*</span></label>
                                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                                id="image" name="image">

                                            <!-- 2MB size note in red -->
                                            <small class="text-danger" style="display: block;">Note: Maximum image size
                                                allowed is 2MB</small>

                                            @error('image')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror

                                            @if ($data->image)
                                                <img src="{{ asset('public/' . $data->image) }}" alt="Blog Image"
                                                    style="width: 100px; height: 100px; margin-left:5px;border: 1px solid #ddd;" class="mt-3">
                                            @endif
                                        </div>
                                    </div>
                                    {{-- Description --}}
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="content">Description <span style="color: red;">*</span></label>
                                            <textarea class="form-control" id="content" name="content" rows="5" required>{{ $data->content }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
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
@section('js')
    {{-- Include CKEditor --}}

    {{-- Include jQuery if not already --}}

    <script>
        $(document).ready(function() {

            // ✅ 1. Initialize CKEditor
            CKEDITOR.replace('content');

            // ✅ 2. Error hide on CKEditor focus
            CKEDITOR.instances['content'].on('focus', function() {
                $('#content-error').hide();
            });

            // ✅ 3. Slug generation from title
            function slugify(text) {
                return text.toString().toLowerCase().trim()
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/[^\w\-]+/g, '') // Remove all non-word chars
                    .replace(/\-\-+/g, '-') // Replace multiple - with single -
                    .replace(/^-+/, '') // Trim - from start
                    .replace(/-+$/, ''); // Trim - from end
            }

            $('#title').on('input', function() {
                $('#slug').val(slugify($(this).val()));
            });

            // ✅ 4. Add error placeholder after textarea
            if ($('#content-error').length === 0) {
                $('<div id="content-error" class="text-danger mt-2" style="display:none;"></div>').insertAfter(
                    '#content');
            }

            // ✅ 5. On submit validation
            $('#edit_blog').on('submit', function(e) {
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
