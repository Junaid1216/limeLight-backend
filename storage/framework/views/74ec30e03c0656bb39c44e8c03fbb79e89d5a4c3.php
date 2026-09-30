<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('content'); ?>


    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="row mb-3">
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="<?php echo e(route('asm.index')); ?>" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total ASM</h5>
                                            <h2 class="mb-3 font-18">
                                                <?php echo e($totalASM); ?>

                                            </h2>
                                            
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="<?php echo e(asset('public/admin/assets/img/banner/1.png')); ?>" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="<?php echo e(route('branch.manager.index')); ?>" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total Branch Managers</h5>
                                            <h2 class="mb-3 font-18">
                                                <?php echo e($totalBranchManagers); ?>

                                            </h2>
                                            
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="<?php echo e(asset('public/admin/assets/img/banner/1.png')); ?>" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="<?php echo e(route('sale.staff.index')); ?>" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total Sales Staff</h5>
                                            <h2 class="mb-3 font-18">
                                                <?php echo e($totalSalesStaff); ?>

                                            </h2>
                                            
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="<?php echo e(asset('public/admin/assets/img/banner/1.png')); ?>" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                
                
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/index.blade.php ENDPATH**/ ?>