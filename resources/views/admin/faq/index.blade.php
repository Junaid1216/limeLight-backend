@extends('admin.layout.app')
@section('title', "FAQ's")

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="col-12">
                                    <h4>FAQ's</h4>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <div class="clearfix">
                                    <div class="create-btn">
                                        @if (Auth::guard('admin')->check() ||
                                                ($sideMenuPermissions->has('Faqs') && $sideMenuPermissions['Faqs']->contains('create')))
                                            <a class="btn btn-primary mb-3 text-white"
                                                href="{{ url('admin/faq-create') }}">Create</a>
                                        @endif
                                    </div>
                                </div>

                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Sr.</th>
                                            <th>Question</th>
                                            <th>Description</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-faqs">
                                        @foreach ($faqs as $faq)
                                            <tr data-id="{{ $faq->id }}">
                                                <td class="sort-handler" style="cursor: move; text-align: center;">
                                                    <i class="fas fa-th"></i>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $faq->question }}</td>
                                                <td title="{{ strip_tags(html_entity_decode($faq->description)) }}">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($faq->description), 150, '...') }}
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Faqs') && $sideMenuPermissions['Faqs']->contains('edit')))
                                                            <a href="{{ route('faq.edit', $faq->id) }}"
                                                                class="btn btn-primary mr-2">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Faqs') && $sideMenuPermissions['Faqs']->contains('delete')))
                                                            <form id="delete-form-{{ $faq->id }}"
                                                                action="{{ route('faq.destroy', $faq->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <button class="show_confirm btn"
                                                                data-form="delete-form-{{ $faq->id }}" type="button"
                                                                style="background-color: #609b90;">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> <!-- card-body -->
                        </div> <!-- card -->
                    </div> <!-- col -->
                </div> <!-- row -->
            </div> <!-- section-body -->
        </section>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // SweetAlert2 delete confirmation
            $('.show_confirm').click(function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                Swal.fire({
                    title: "Are you sure you want to delete this record?",
                    text: "If you delete this User record, it will be gone forever.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.action,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: "Success!",
                                    text: "Record deleted successfully.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire("Error!", "Failed to delete record.",
                                    "error");
                            }
                        });
                    }
                });
            });


            // Drag and Drop Reordering (jQuery version using Sortable + AJAX)
            var sortable = new Sortable(document.getElementById('sortable-faqs'), {
                animation: 150,
                handle: '.sort-handler',
                onEnd: function() {
                    var order = [];

                    $('#sortable-faqs tr').each(function(index) {
                        order.push({
                            id: $(this).data('id'),
                            position: index + 1
                        });
                    });

                    $.ajax({
                        url: "{{ route('faq.reorder') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        contentType: 'application/json',
                        data: JSON.stringify({
                            order: order
                        }),
                        success: function() {
                            toastr.success('Order updated successfully!');
                            window.location.reload();
                        },
                        error: function() {
                            toastr.error('Reordering failed!');
                        }
                    });
                }
            });
        });
    </script>
@endsection
