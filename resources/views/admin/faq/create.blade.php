@extends('admin.layout.app')
@section('title', 'Create FAQ')
@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <form action="{{ url('admin/faq-store') }}" method="POST">
                    @csrf
                    <a href="{{ url('/admin/faq') }}" class="btn mb-3" style="background: #609b90;">Back</a>
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Create FAQ</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Question <span style="color: red;">*</span></label>
                                        <input name="question"
											class="form-control @error('question') is-invalid @enderror" required
											placeholder="Enter question">
										@error('question')
											<div class="invalid-feedback">{{ $message }}</div>
										@enderror

                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Description <span style="color: red;">*</span></label>
                                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" required>
                                        </textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-primary mr-1" type="submit">Save</button>
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

        //error handling 
        $(document).ready(function() {
            $('input, select, textarea').on('focus', function() {
                const $feedback = $(this).parent().find('.invalid-feedback');
                if ($feedback.length) {
                    $feedback.hide();
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
@endsection
