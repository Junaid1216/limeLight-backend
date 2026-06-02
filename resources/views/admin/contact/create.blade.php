{{-- @extends('admin.layout.app')

@section('title', 'Create Contact')

@section('content') --}}
{{-- <div class="main-content">
        <section class="section">
            <div class="section-body">
                <a class="btn btn-primary mb-3" href="{{ url()->previous() }}">Back</a>
                <form id="add_event" action="{{ route('contact.create') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <h4 class="text-center my-4">Create Contact Details</h4>
                                <div class="row mx-0 px-4">
                                    <!-- Email -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" name="email" required
                                                placeholder="Enter email">
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-sm-6 pl-sm-0 pr-sm-3">
                                        <div class="form-group">
                                            <label for="phone">Phone</label>
                                            <input type="number" class="form-control" name="phone" required
                                                placeholder="Enter phone number">
                                        </div>
                                    </div>



                                    <!-- Submit Button -->
                                    <div class="card-footer text-center row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary btn-bg">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </form>
            </div>
        </section>
    </div> --}}
{{-- @endsection --}}

{{-- @section('js') --}}
{{-- Toastr CDN --}}


<script>
    // document.getElementById('add_event').addEventListener('submit', function(e) {
    //     e.preventDefault(); // Prevent default form submission
    //     const form = e.target;
    //     const formData = new FormData(form);

    //     toastr.options = {
    //         "closeButton": true,
    //         "progressBar": true,
    //         "positionClass": "toast-top-right",
    //         "timeOut": "3000",
    //     };

    //     // Show loading message
    //     toastr.info('Creating contact...');

    //     fetch(form.action, {
    //             method: 'POST',
    //             body: formData,
    //             headers: {
    //                 'X-Requested-With': 'XMLHttpRequest',
    //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
    //                     'content'),
    //             },
    //         })
    //         .then(response => {
    //             // Check if response is not okay (like 404, 500 etc.)
    //             if (!response.ok) {
    //                 return response.json().then(err => {
    //                     throw err; // Handle errors
    //                 });
    //             }
    //             return response.json(); // Parse JSON if the response is okay
    //         })
    //         .then(data => {
    //             if (data.success) {
    //                 toastr.success(data.message);
    //                 // Redirect after a delay
    //                 setTimeout(() => {
    //                     window.location.href = "{{ route('contact.index') }}";
    //                 }, 2000);
    //             } else {
    //                 // Handle validation errors
    //                 if (data.errors) {
    //                     const errors = data.errors;
    //                     for (const field in errors) {
    //                         toastr.error(errors[field][0]);
    //                     }
    //                 } else {
    //                     toastr.error(data.message);
    //                 }
    //             }
    //         })
    //         .catch(error => {
    //             console.error('Error:', error);
    //             toastr.error(error.message || 'Something went wrong. Please try again.');
    //         });
    // });
    // 
</script>
{{-- @endsection --}}
