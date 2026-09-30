<div class="main-sidebar sidebar-style-2">
    <aside" id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="<?php echo e(url('/admin/dashboard')); ?>">
                <img alt="image" src="<?php echo e(asset('public/admin/assets/img/Lime.png')); ?>" class="header-logo" />
                
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown <?php echo e(request()->is('admin/dashboard') ? 'active' : ''); ?>">
                <a href="<?php echo e(url('/admin/dashboard')); ?>" class="nav-link"><i
                        data-feather="home"></i><span>Dashboard</span></a>
            </li>



            

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('view'))): ?>
                
                <li class="dropdown <?php echo e(request()->is('admin/roles*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/roles')); ?>" class="nav-link"><i
                            data-feather="user"></i><span>Roles</span></a>
                </li>
            <?php endif; ?>



            

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('view'))): ?>
                
                <li class="dropdown <?php echo e(request()->is('admin/subadmin*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/subadmin')); ?>" class="nav-link"><i data-feather="user"></i><span>Sub
                            Admins</span></a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Cities') && $sideMenuPermissions['Cities']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/cities') || request()->is('admin/cities/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/cities')); ?>" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Cities</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Designations') && $sideMenuPermissions['Designations']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/designations') || request()->is('admin/designations/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/designations')); ?>" class="nav-link">
                        <i data-feather="briefcase"></i>
                        <span>Designations</span>
                    </a>
                </li>
            <?php endif; ?>

            

            

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Regions') && $sideMenuPermissions['Regions']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/region*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/region')); ?>" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Regions</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Area Sale Managers') && $sideMenuPermissions['Area Sale Managers']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/asm*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/asm')); ?>" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Area Sales Managers</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Branches') && $sideMenuPermissions['Branches']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/branch') || request()->is('admin/branch/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/branch')); ?>" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Branches</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Branch Managers') && $sideMenuPermissions['Branch Managers']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/branch-manager') || request()->is('admin/branch-manager/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/branch-manager')); ?>" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Branch Managers</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sale Staff') && $sideMenuPermissions['Sale Staff']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/sale-staff') || request()->is('admin/sale-staff/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/sale-staff')); ?>" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Sales Staff</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Hierarchy') && $sideMenuPermissions['Hierarchy']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/hierarchy') || request()->is('admin/hierarchy/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/hierarchy')); ?>" class="nav-link">
                        <i data-feather="git-branch"></i>
                        <span>Hierarchy</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Line Items') && $sideMenuPermissions['Line Items']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/category') || request()->is('admin/category/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/category')); ?>" class="nav-link">
                        <i data-feather="list"></i>
                        <span>Line Items</span>
                    </a>
                </li>
            <?php endif; ?>

             <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Targets') && $sideMenuPermissions['Targets']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/target') || request()->is('admin/target/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/target')); ?>" class="nav-link">
                        <i data-feather="target"></i>
                        <span>Monthly Targets</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Commissions') && $sideMenuPermissions['Commissions']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/commission') || request()->is('admin/commission/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/commission')); ?>" class="nav-link">
                        <i data-feather="dollar-sign"></i>
                        <span>Commissions</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Slip Bound Incentives') && $sideMenuPermissions['Slip Bound Incentives']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/slab') || request()->is('admin/slab/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/slab')); ?>" class="nav-link">
                        <i data-feather="award"></i>
                        <span>Slip Bound Incentives</span>
                    </a>
                </li>
            <?php endif; ?>

             <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/training_module') || request()->is('admin/training_module/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/training_module')); ?>" class="nav-link">
                        <i data-feather="video"></i>
                        <span>Training Modules</span>
                    </a>
                </li>
            <?php endif; ?>

             <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Display Categories') && $sideMenuPermissions['Display Categories']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/display-categories') || request()->is('admin/display-categories/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/display-categories')); ?>" class="nav-link">
                        <i data-feather="grid"></i>
                        <span>Display Categories</span>
                    </a>
                </li>
            <?php endif; ?>

             <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Surveys') && $sideMenuPermissions['Surveys']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/surveys') || request()->is('admin/surveys/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/surveys')); ?>" class="nav-link">
                        <i data-feather="clipboard"></i>
                        <span>Surveys</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Reportings') && $sideMenuPermissions['Reportings']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/reporting') || request()->is('admin/reporting/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/reporting')); ?>" class="nav-link">
                        <i data-feather="bar-chart-2"></i>
                        <span>Reportings</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sales History') && $sideMenuPermissions['Sales History']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/sales-history') || request()->is('admin/sales-history/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/sales-history')); ?>" class="nav-link">
                        <i data-feather="clock"></i>
                        <span>Sales History</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Peer Branch Conversion') && $sideMenuPermissions['Peer Branch Conversion']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/peer-branch-conversion') || request()->is('admin/peer-branch-conversion/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/peer-branch-conversion')); ?>" class="nav-link">
                        <i data-feather="git-merge"></i>
                        <span>Peer Branch Conversion</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Announcements') && $sideMenuPermissions['Announcements']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/announcements') || request()->is('admin/announcements/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/announcements')); ?>" class="nav-link">
                        <i data-feather="volume-2"></i>
                        <span>Announcements</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Feedback') && $sideMenuPermissions['Feedback']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/feedback') || request()->is('admin/feedback/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/feedback')); ?>" class="nav-link">
                        <i data-feather="message-square"></i>
                        <span>Feedback</span>
                        <span id="feedback-unread-badge" class="feedback-menu-badge">
                            <?php echo e(($feedbackUnreadCount ?? 0) > 10 ? '10+' : ($feedbackUnreadCount ?? 0)); ?>

                        </span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Assigned Targets') && $sideMenuPermissions['Assigned Targets']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/assigned-targets') || request()->is('admin/assigned-targets/*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/assigned-targets')); ?>" class="nav-link">
                        <i data-feather="check-square"></i>
                        <span>Assigned Targets</span>
                    </a>
                </li>
            <?php endif; ?>


            

            

             

            
            
             

            

            

            
            
             


            


            

            

            


            

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Privacy & Policy') && $sideMenuPermissions['Privacy & Policy']->contains('view'))): ?>
                
                <li class="dropdown <?php echo e(request()->is('admin/privacy-policy*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/privacy-policy')); ?>" class="nav-link"><i
                            data-feather="shield"></i><span>Privacy
                            & Policy</span></a>
                </li>
            <?php endif; ?>




            

            <?php if(Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Terms & Conditions') &&
                        $sideMenuPermissions['Terms & Conditions']->contains('view'))): ?>
                <li class="dropdown <?php echo e(request()->is('admin/term-condition*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(url('admin/term-condition')); ?>" class="nav-link"><i
                            data-feather="file-text"></i><span>Terms
                            & Conditions</span></a>
                </li>
            <?php endif; ?>



        </ul>
        </aside>
</div>
<?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/common/side_menu.blade.php ENDPATH**/ ?>