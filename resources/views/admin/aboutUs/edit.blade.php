@extends('admin.layout.app')
@section('title', 'Edit About Us')
@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <form id="aboutUsForm" action="{{ url('admin/about-us-update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Edit About Us</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Description <span style="color: red;">*</span></label>
                                        <textarea name="description" id="description" class="form-control">{{ old('description', $data->description ?? '') }}</textarea>
                                        <div id="description-error" class="invalid-feedback"
                                            style="display: none; font-size: 14px; color: red;"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-primary mr-1">Save Changes</button>
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
        CKEDITOR.replace('description');

        $(document).ready(function() {
            $('#aboutUsForm').on('submit', function(e) {
                // Sync CKEditor content
                for (instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }

                const desc = CKEDITOR.instances.description.getData().trim();

                // Remove old errors
                $('#description').removeClass('is-invalid');
                $('#description-error').hide();

                // Validation: check if blank or only tags
                if (!desc || desc.replace(/&nbsp;|<[^>]*>/g, '').trim() === '') {
                    e.preventDefault();
                    $('#description').addClass('is-invalid');
                    $('#description-error').text('Description is required.').css('font-size', '14px')
                        .show();
                }
            });

            // Optional: clear error when focused
            CKEDITOR.instances.description.on('focus', function() {
                $('#description').removeClass('is-invalid');
                $('#description-error').hide();
            });
        });
    </script>
@endsection
