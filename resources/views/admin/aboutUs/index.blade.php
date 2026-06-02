@extends('admin.layout.app')
@section('title', 'About Us')
@section('content')

    @php
        use Illuminate\Support\Str;
    @endphp
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="col-12">
                                    <h4>About Us</h4>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Detail</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td title="{{ $data && $data->description ? strip_tags(html_entity_decode($data->description)) : 'No description available' }}">
												@if ($data && $data->description)
													{!! Str::limit(strip_tags($data->description), 200, '...') !!}
												@else
													<p>No description available.</p>
												@endif
											</td>

                                            <td>
                                                <div class="d-flex gap-4">

                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('About us') && $sideMenuPermissions['About us']->contains('edit')))
                                                        <a href="{{ url('admin/about-us-edit') }}" class="btn btn-primary">
                                                            <span class="fa fa-edit"></span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('js')

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#table_id_events').DataTable();

            // SweetAlert Delete Confirmation
            $('.show_confirm').click(function(event) {
                event.preventDefault();
                var form = $(this).closest("form");
                var name = $(this).data("name");

                swal({
                    title: "Are you sure you want to delete this record?",
                    text: "If you delete this, it will be gone forever.",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
