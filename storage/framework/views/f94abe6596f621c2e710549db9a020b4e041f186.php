<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title'); ?></title>
    <!-- Developed By Ranglerz -->
    <link rel="stylesheet" href="https://www.ranglerz.com/cost-to-make-a-web-ios-or-android-app-and-how-long-does-it-take.php">

    <!-- ========== Favicon ========== -->
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('public/admin/assets/img/favicon2.png')); ?>" />

    <!-- ========== External CSS Libraries (CDN) ========== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- ========== Vendor CSS (Public Assets) ========== -->
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/css/app.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/css/components.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/bundles/datatables/datatables.min.css')); ?>">
    <link rel="stylesheet"
        href="<?php echo e(asset('public/admin/assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/toastr/toastr.css')); ?>">

    <!-- ========== Custom Project CSS ========== -->
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/assets/css/custom.css')); ?>">

    <?php echo $__env->yieldContent('style'); ?>
</head>

<body>
    <div class="loader"></div>

    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <!-- ========== External JS Libraries (CDN) ========== -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.0/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>



    <!-- ========== Vendor JS (Public Assets) ========== -->
    <script src="<?php echo e(asset('public/admin/assets/js/app.min.js')); ?>"></script>
    <script src="<?php echo e(asset('public/admin/assets/js/scripts.js')); ?>"></script>
    <script src="<?php echo e(asset('public/admin/assets/bundles/datatables/datatables.min.js')); ?>"></script>
    <script src="<?php echo e(asset('public/admin/assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js')); ?>">
    </script>
    <script src="<?php echo e(asset('public/admin/assets/js/page/datatables.js')); ?>"></script>
    <script src="<?php echo e(asset('public/admin/assets/bundles/apexcharts/apexcharts.min.js')); ?>"></script>
    <script src="<?php echo e(asset('public/admin/assets/toastr/toastr.js')); ?>"></script>

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

        <?php if(Session::has('success')): ?>
            toastr.success("<?php echo e(Session::get('success')); ?>");
        <?php endif; ?>

        <?php if(Session::has('error')): ?>
            toastr.error("<?php echo e(Session::get('error')); ?>");
        <?php endif; ?>

        <?php if(Session::has('info')): ?>
            toastr.info("<?php echo e(Session::get('info')); ?>");
        <?php endif; ?>

        <?php if(Session::has('warning')): ?>
            toastr.warning("<?php echo e(Session::get('warning')); ?>");
        <?php endif; ?>
    </script>

    <!-- ========== Custom Project JS ========== -->
    <script src="<?php echo e(asset('public/admin/assets/js/custom.js')); ?>"></script>

    <!-- ========== Blade Page-Specific JS ========== -->
    <?php echo $__env->yieldContent('js'); ?>

</body>

</html>
<?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/auth/layout/app.blade.php ENDPATH**/ ?>