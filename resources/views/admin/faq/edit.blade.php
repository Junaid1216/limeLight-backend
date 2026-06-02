@extends('admin.layout.app')
@section('title', 'Edit FAQ')

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <form action="{{ url('admin/faq-update', $data->id) }}" method="POST" id="faq-form">
                    @csrf
                    @method('POST') <!-- Use POST for updates in this context -->

                    <a href="{{ url('/admin/faq') }}" class="btn mb-3" style="background: #609b90;">Back</a>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Edit FAQ</h4>
                                </div>

                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Question <span class="text-danger">*</span></label>
                                        <input name="question"
                                            class="form-control @error('questions') is-invalid @enderror"
                                            value="{{ old('questions', $data->question) }}" required
                                            placeholder="Enter question">

                                        @error('questions')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label>Description <span class="text-danger">*</span></label>
                                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror">
                                        {{ old('description', $data->description) }}
                                    </textarea>

                                        @error('description')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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
    <script>
        $(document).ready(function() {
            // Init CKEditor
            CKEDITOR.replace('description');

            // Hide validation error when CKEditor gains focus
            CKEDITOR.on('instanceReady', function(evt) {
                const editor = evt.editor;
                editor.on('focus', function() {
                    const $textarea = $('#' + editor.name);
                    const $feedback = $textarea.parent().find('.invalid-feedback');
                    $feedback.hide();
                    $textarea.removeClass('is-invalid');
                });
            });

            // Hide validation errors on normal inputs
            $('input, textarea').on('focus', function() {
                const $feedback = $(this).parent().find('.invalid-feedback');
                if ($feedback.length) {
                    $feedback.hide();
                    $(this).removeClass('is-invalid');
                }
            });

            // Sync CKEditor data on submit
            $('#faq-form').on('submit', function() {
                for (let inst in CKEDITOR.instances) {
                    CKEDITOR.instances[inst].updateElement();
                }
            });
        });
    </script>

@endsection
