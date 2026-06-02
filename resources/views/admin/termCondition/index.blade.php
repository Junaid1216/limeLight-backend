@extends('admin.layout.app')
@section('title', 'Terms & Conditions')
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
                                    <h4>Terms & Conditions</h4>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">


                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Details</th>
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
                                                            ($sideMenuPermissions->has('Terms & Conditions') &&
                                                                $sideMenuPermissions['Terms & Conditions']->contains('edit')))
                                                        <a href="{{ url('admin/term-condition-edit') }}"
                                                            class="btn btn-primary"><span class="fa fa-edit"></a>
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
            $('#table_id_events').DataTable()
        })
    </script>
@endsection
