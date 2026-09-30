<style>
.notification-dropdown {
    width: 350px;
    max-width: 90vw;
    max-height: 500px;
    overflow-y: auto;
}

.notification-item {
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.notification-title,
.notification-description {
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}
</style>

<?php
$notifications = \App\Models\Notification::where('user_type', 'admin')
    ->latest()
    ->take(10)
    ->get();

$unreadCount = \App\Models\Notification::where('user_type', 'admin')
    ->where('is_read', 0)
    ->count();
?>
<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar sticky">
    <div class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg
									collapse-btn"> <i
                        data-feather="align-justify"></i></a></li>
            
        </ul>
    </div>
    <ul class="navbar-nav navbar-right">
        
         <li class="nav-item dropdown">
    <a class="nav-link position-relative"
       href="#"
       data-bs-toggle="dropdown">

        <i data-feather="bell"></i>

        <?php if($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo e($unreadCount); ?>

            </span>
        <?php endif; ?>

    </a>

    <div class="dropdown-menu dropdown-menu-end p-0 notification-dropdown">

        <div class="p-3 border-bottom">
            <strong>Notifications</strong>
        </div>

        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

            <a href="<?php echo e(route('admin.notifications.read', $notification->id)); ?>"
               class="dropdown-item notification-item">

                <strong class="notification-title">
                    <?php echo e($notification->title); ?>

                </strong>

                <br>

                <small class="notification-description">
                    <?php echo e($notification->description); ?>

                </small>

                <br>

                <small class="text-muted">
                    <?php echo e($notification->created_at->diffForHumans()); ?>

                </small>

            </a>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

            <div class="p-3 text-center">
                No Notifications
            </div>

        <?php endif; ?>

    </div>
</li>
        <li class="dropdown"><a href="#" data-toggle="dropdown"
                class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <?php
                    $user = Auth::guard('admin')->check()
                        ? Auth::guard('admin')->user()
                        : (Auth::guard('subadmin')->check()
                            ? Auth::guard('subadmin')->user()
                            : null);
                ?>

                <?php if($user && $user->image): ?>
                    <img alt="image" src="<?php echo e(asset($user->image)); ?>" class="user-img-radious-style">
                <?php else: ?>
                    <img alt="image" src="<?php echo e(asset('public/admin/assets/img/logo.png')); ?>"
                        class="user-img-radious-style">
                <?php endif; ?>
                <span class="d-sm-none d-lg-inline-block"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right pullDown">
                <div class="dropdown-title">
                    Hello,
                    
                    <?php echo e(Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('subadmin')->check() ? Auth::guard('subadmin')->user()->name : 'Guest')); ?>

                </div>

                <a href="<?php echo e(url('admin/profile')); ?>" class="dropdown-item has-icon"> <i class="fas fa-cog   "></i> Settings
                </a>
                
                <div class="dropdown-divider"></div>
                <a href="<?php echo e(url('admin/logout')); ?>" class="dropdown-item has-icon text-danger"> <i
                        class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </li>
       
    </ul>
</nav>
<?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/common/header.blade.php ENDPATH**/ ?>