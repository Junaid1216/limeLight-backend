@extends('admin.layout.app')
@section('title', 'Contact Us')
@section('content')

    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="col-12">
                                    <h4>Contact Us</h4>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                {{-- @if (Auth::guard('admin')->check() || $sideMenuPermissions->contains(fn($permission) => $permission['side_menu_name'] === 'ContactUs' && $permission['permissions']->contains('create')))
                                    <a class="btn btn-primary mb-3 text-white"
                                        href="{{ route('interest.createview') }}">Create</a>
                                @endif --}}

                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Email</th>
                                            <th>Phone</th>



                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contacts as $contact)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>



                                                <td>{{ $contact->email }}</td>
                                                <td>{{ $contact->phone }}</td>


                                                <td>
                                                    <div class="d-flex gap-4">



                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Contact us') && $sideMenuPermissions['Contact us']->contains('edit')))
                                                            <a href="{{ route('contact.updateview', $contact->id) }}"
                                                                class="btn btn-primary me-2"
                                                                style="float: left; margin-right: 8px;"><span><i
                                                                        class="fa fa-edit"></i></span></a>
                                                        @endif

                                                </td>
                            </div>
                            </td>
                            </tr>
                            @endforeach
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
            $('#table_id_events').DataTable()
        })
    </script>


@endsection
