<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="container mt-5">
            <div class="row">
                <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-center">
                            <img src="<?php echo e(asset('public/admin/assets/img/Lime.png')); ?>" style="width: 50%; height: 50%;"
                                class="img-fluid" alt="Logo">
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?php echo e(url('login')); ?>" class="mb-0 needs-validation" novalidate>
                                <?php echo csrf_field(); ?>
                                <!-- Email Field -->
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email" class="form-control" name="email" tabindex="1"
                                        required autofocus placeholder="example@gmail.com">
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span style="color: red;">Email required</span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Password Field with jQuery Toggle -->
                                <div class="form-group position-relative" style="margin-bottom: 0.5rem;">

                                    <label for="password">Password</label>

                                    <input type="password" placeholder="Enter Password" name="password" id="password"
                                        class="form-control" style="padding-right: 2.5rem;">

                                    <span id="togglePasswordIcon" class="fa fa-eye"
                                        style="position: absolute; top: 2.67rem; right: 0.5rem; cursor: pointer;"></span>

                                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div style="color: red;"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                </div>

                                <!-- Forgot Password Link -->

                                <div class="form-group">

                                    <div class="text-end mt-3 mb-3">

                                        <a href="<?php echo e(url('admin-forgot-password')); ?>" style="font-size: small;">

                                            Forgot Password?

                                        </a>

                                    </div>

                                </div>


                                <!-- Submit Button -->
                                <div class="form-group mt-3 mb-0">
                                    <button type="submit" class="btn btn-lg btn-block btn-login" tabindex="4"
                                        style="background-color: #609b90;">
                                        Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        $(document).ready(function() {
            // Password toggle

            $('#togglePasswordIcon').on('click', function() {

                const $password = $('#password');

                const type = $password.attr('type') === 'password' ? 'text' : 'password';

                $password.attr('type', type);

                $(this).toggleClass('fa-eye fa-eye-slash');

            });

            // Hide icon on login click (optional)

            $('.btn-login').on('click', function() {

                $('#togglePasswordIcon').addClass('d-none');

            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.auth.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>