@extends('admin.layout.app')
@section('title', 'Line Items')

@section('content')
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">

                                <h4 class="mb-0">Line Items</h4>

                                <div style="width:250px;">

                                    <select id="categoryFilter" class="form-control" style="border-radius: 0.2rem;">

                                        <option value="">All Categories</option>

                                        @foreach ($categories as $category)

                                            <option value="{{ $category->name }}">
                                                {{ $category->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>

                        <div class="card-body table-striped table-bordered table-responsive">

                            {{-- Sync Button --}}
                            {{-- <a class="btn btn-success mb-3" href="{{ route('lineitems.sync') }}">
                                Sync Line Items
                            </a> --}}

                            <table class="table" id="table_id_events">
                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Line Item</th>
                                        <th>Category</th>
                                        <th>Change Category</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($lineItems->sortBy(function($item) {
                                                return $item->category->name ?? 'ZZZ';
                                            }) as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $item->name ?? '-' }}</td>

                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $item->category->name ?? 'Unassigned' }}
                                                </span>
                                            </td>

                                            <td>
                                                <form method="POST"
                                                      action="{{ route('lineitem.update', $item->id) }}"
                                                      class="d-flex align-items-center"
                                                      style="gap:5px;">
                                                    @csrf

                                                    <select name="category_id" class="form-control">
                                                        <option value="">Select Category</option>

                                                        @foreach ($categories as $category)
                                                            <option value="{{ $category->id }}"
                                                                {{ $item->category_id == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <button class="btn btn-primary btn-sm">
                                                        <i class="fa fa-save"></i>
                                                    </button>
                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div> <!-- /.card-body -->
                    </div> <!-- /.card -->

                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('js')

<script>
    $(document).ready(function () {

        if ($.fn.DataTable.isDataTable('#table_id_events')) {
            $('#table_id_events').DataTable().destroy();
        }

        let table = $('#table_id_events').DataTable();

        // Category Filter
        $('#categoryFilter').on('change', function () {

            let value = $(this).val();

            table.column(2).search(value).draw();

        });

    });
</script>

@if (session('message'))
<script>
    toastr.success('{{ session('message') }}');
</script>
@endif

@endsection