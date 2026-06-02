<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <!-- Developed By Ranglerz -->
    <link rel="stylesheet" href="https://www.ranglerz.com/cost-to-make-a-web-ios-or-android-app-and-how-long-does-it-take.php">

    <!-- ========== Favicon ========== -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('public/admin/assets/img/favicon2.png') }}" />

    <!-- ========== External CSS Libraries (CDN) ========== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- ========== Vendor CSS (Public Assets) ========== -->
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('public/admin/assets/bundles/datatables/datatables.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('public/admin/assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/admin/assets/toastr/toastr.css') }}">

    <!-- ========== Custom Project CSS ========== -->
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/admin/assets/css/custom.css') }}">

    @yield('style')
</head>

<body>
    <div class="loader"></div>

    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            @include('admin.common.header')
            @include('admin.common.side_menu')
            @yield('content')
            @include('admin.common.footer')
        </div>
    </div>

    <!-- ========== Core JS Libraries ========== -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <!-- ========== Project JS Files ========== -->
    <script src="{{ asset('public/admin/assets/js/app.min.js') }}"></script>
    <script src="{{ asset('public/admin/assets/js/scripts.js') }}"></script>
    <script src="{{ asset('public/admin/assets/toastr/toastr.js') }}"></script>

    <!-- ========== DataTables ========== -->
    <script src="{{ asset('public/admin/assets/bundles/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('public/admin/assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}">
    </script>
    <script src="{{ asset('public/admin/assets/js/page/datatables.js') }}"></script>

    <!-- ========== Charts ========== -->
    <script src="{{ asset('public/admin/assets/bundles/apexcharts/apexcharts.min.js') }}"></script>

    <!-- ========== External JS Plugins ========== -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.0/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>

    <!-- ========== Toastr Flash Messages ========== -->
    <script>
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": 300,
            "hideDuration": 1000,
            "timeOut": 3000,
            "extendedTimeOut": 1000,
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        @if (Session::has('success'))
            toastr.success("{{ Session::get('success') }}");
        @endif

        @if (Session::has('error'))
            toastr.error("{{ Session::get('error') }}");
        @endif

        @if (Session::has('info'))
            toastr.info("{{ Session::get('info') }}");
        @endif

        @if (Session::has('warning'))
            toastr.warning("{{ Session::get('warning') }}");
        @endif
    </script>

    <!-- ========== Custom Project JS ========== -->
    <script src="{{ asset('public/admin/assets/js/custom.js') }}"></script>

    <!-- ========== Blade Page-Specific JS ========== -->
    @yield('js')

</body>

</html>
