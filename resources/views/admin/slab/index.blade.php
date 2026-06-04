@extends('admin.layout.app')
@section('title', 'Slabs')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12">

                    {{-- Create / Update Slabs --}}
                    <div class="card">
                        <div class="card-header">
                            <h4>Slabs<small class="font-weight-bold text-danger"> (Slip Bound Incentive is awarded to Sales Staff based on their sales achievement. Incentives are earned according to the slab reached.)</small></h4>
                        </div>

                        <form action="{{ route('slip.incentive.store') }}" method="POST">
                            @csrf

                            <div class="card-body table-responsive pb-1">

                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Slab</th>
                                            <th>From Amount</th>
                                            <th>To Amount</th>
                                            <th>Incentive (PKR)</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        @foreach(['A','B','C','D','E'] as $slab)

                                            @php
                                                $existing = $incentives->where('slab_name',$slab)->first();
                                            @endphp

                                            <tr>

                                                <td>
                                                    {{ $slab }}

                                                    <input type="hidden"
                                                        name="slab_name[]"
                                                        value="{{ $slab }}">
                                                </td>

                                                <td>
                                                    <input type="number"
                                                        name="from_amount[]"
                                                        class="form-control"
                                                        value="{{ $existing->from_amount ?? '' }}"
                                                        required>
                                                </td>

                                                <td>
                                                    <input type="number"
                                                        name="to_amount[]"
                                                        class="form-control"
                                                        value="{{ $existing->to_amount ?? '' }}"
                                                        required>
                                                </td>

                                                <td>
                                                    <input type="number"
                                                        name="incentive_amount[]"
                                                        class="form-control"
                                                        value="{{ $existing->incentive_amount ?? '' }}"
                                                        required>
                                                </td>

                                            </tr>

                                        @endforeach

                                    </tbody>
                                </table>

                            </div>

                            <div class="card-footer text-center py-2">
                                <button type="submit" class="btn btn-primary mb-4">
                                    Save
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection

@section('js')

<script>
$(document).ready(function(){

    if ($.fn.DataTable.isDataTable('#table_id_events')) {
        $('#table_id_events').DataTable().destroy();
    }

    $('#table_id_events').DataTable();

});
</script>

@endsection