@extends('admin.layout.app')
@section('title', 'Edit Privacy Policy')
@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <form id="privacyPolicyForm" action="{{ url('admin/privacy-policy-update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Edit Privacy Policy</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Description <span style="color: red;">*</span></label>
                                        <textarea name="description" id="description" class="form-control">{{ old('description', $data->description ?? '') }}</textarea>
                                        <div id="description-error" class="invalid-feedback"
                                            style="display: none; font-size:14px; color:red;"></div>
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
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('description');

        $(document).ready(function() {
            $('#privacyPolicyForm').on('submit', function(e) {
                // Get CKEditor content
                for (instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }

                const desc = CKEDITOR.instances.description.getData().trim();

                // Remove old error
                $('#description').removeClass('is-invalid');
                $('#description-error').hide();

                // Check if empty
                if (!desc || desc.replace(/&nbsp;|<[^>]*>/g, '').trim() === '') {
                    e.preventDefault();
                    $('#description').addClass('is-invalid');
                    $('#description-error').text('Description is required.').show();
                }

            });

            CKEDITOR.instances.description.on('focus', function() {
                $('#description').removeClass('is-invalid');
                $('#description-error').hide();
            });

            // Hide error on click
            $('#description').on('focus', function() {
                $('#description').removeClass('is-invalid');
                $('#description-error').hide();
            });
        });
    </script>
@endsection
