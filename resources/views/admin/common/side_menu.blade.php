<div class="main-sidebar sidebar-style-2">
    <aside" id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ url('/admin/dashboard') }}">
                <img alt="image" src="{{ asset('public/admin/assets/img/Lime.png') }}" class="header-logo" />
                {{-- <span class="logo-name">Crop Secure</span> --}}
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <a href="{{ url('/admin/dashboard') }}" class="nav-link"><i
                        data-feather="home"></i><span>Dashboard</span></a>
            </li>



            {{--  Roles --}}

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('view')))
                {{-- FAQS --}}
                <li class="dropdown {{ request()->is('admin/roles*') ? 'active' : '' }}">
                    <a href="{{ url('admin/roles') }}" class="nav-link"><i
                            data-feather="user"></i><span>Roles</span></a>
                </li>
            @endif



            {{--  SubAdmin --}}

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sub Admins') && $sideMenuPermissions['Sub Admins']->contains('view')))
                {{-- FAQS --}}
                <li class="dropdown {{ request()->is('admin/subadmin*') ? 'active' : '' }}">
                    <a href="{{ url('admin/subadmin') }}" class="nav-link"><i data-feather="user"></i><span>Sub
                            Admins</span></a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Cities') && $sideMenuPermissions['Cities']->contains('view')))
                <li class="dropdown {{ request()->is('admin/cities') || request()->is('admin/cities/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/cities') }}" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Cities</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Designations') && $sideMenuPermissions['Designations']->contains('view')))
                <li class="dropdown {{ request()->is('admin/designations') || request()->is('admin/designations/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/designations') }}" class="nav-link">
                        <i data-feather="briefcase"></i>
                        <span>Designations</span>
                    </a>
                </li>
            @endif

            {{--  Users --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Users') && $sideMenuPermissions['Users']->contains('view')))
                <li class="dropdown {{ request()->is('admin/user*') ? 'active' : '' }}">
                    <a href="{{ url('admin/user') }}" class="nav-link">
                        <i data-feather="users"></i>
                        <span>Users</span>
                    </a>
                </li>
            @endif --}}

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Regions') && $sideMenuPermissions['Regions']->contains('view')))
                <li class="dropdown {{ request()->is('admin/region*') ? 'active' : '' }}">
                    <a href="{{ url('admin/region') }}" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Regions</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Area Sale Managers') && $sideMenuPermissions['Area Sale Managers']->contains('view')))
                <li class="dropdown {{ request()->is('admin/asm*') ? 'active' : '' }}">
                    <a href="{{ url('admin/asm') }}" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Area Sales Managers</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Branches') && $sideMenuPermissions['Branches']->contains('view')))
                <li class="dropdown {{ request()->is('admin/branch') || request()->is('admin/branch/*') ? 'active' : '' }}">
                    <a href="{{ url('admin/branch') }}" class="nav-link">
                        <i data-feather="map-pin"></i>
                        <span>Branches</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Branch Managers') && $sideMenuPermissions['Branch Managers']->contains('view')))
                <li class="dropdown {{ request()->is('admin/branch-manager') || request()->is('admin/branch-manager/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/branch-manager') }}" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Branch Managers</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Sale Staff') && $sideMenuPermissions['Sale Staff']->contains('view')))
                <li class="dropdown {{ request()->is('admin/sale-staff') || request()->is('admin/sale-staff/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/sale-staff') }}" class="nav-link">
                        <i data-feather="user-check"></i>
                        <span>Sales Staff</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Hierarchy') && $sideMenuPermissions['Hierarchy']->contains('view')))
                <li class="dropdown {{ request()->is('admin/hierarchy') || request()->is('admin/hierarchy/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/hierarchy') }}" class="nav-link">
                        <i data-feather="git-branch"></i>
                        <span>Hierarchy</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Line Items') && $sideMenuPermissions['Line Items']->contains('view')))
                <li class="dropdown {{ request()->is('admin/category') || request()->is('admin/category/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/category') }}" class="nav-link">
                        <i data-feather="list"></i>
                        <span>Line Items</span>
                    </a>
                </li>
            @endif

             @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Targets') && $sideMenuPermissions['Targets']->contains('view')))
                <li class="dropdown {{ request()->is('admin/target') || request()->is('admin/target/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/target') }}" class="nav-link">
                        <i data-feather="target"></i>
                        <span>Monthly Targets</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Commissions') && $sideMenuPermissions['Commissions']->contains('view')))
                <li class="dropdown {{ request()->is('admin/commission') || request()->is('admin/commission/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/commission') }}" class="nav-link">
                        <i data-feather="dollar-sign"></i>
                        <span>Commissions</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Slip Bound Incentives') && $sideMenuPermissions['Slip Bound Incentives']->contains('view')))
                <li class="dropdown {{ request()->is('admin/slab') || request()->is('admin/slab/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/slab') }}" class="nav-link">
                        <i data-feather="award"></i>
                        <span>Slip Bound Incentives</span>
                    </a>
                </li>
            @endif

             @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('view')))
                <li class="dropdown {{ request()->is('admin/training_module') || request()->is('admin/training_module/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/training_module') }}" class="nav-link">
                        <i data-feather="video"></i>
                        <span>Training Modules</span>
                    </a>
                </li>
            @endif

             @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Surveys') && $sideMenuPermissions['Surveys']->contains('view')))
                <li class="dropdown {{ request()->is('admin/surveys') || request()->is('admin/surveys/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/surveys') }}" class="nav-link">
                        <i data-feather="clipboard"></i>
                        <span>Surveys</span>
                    </a>
                </li>
            @endif

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Reportings') && $sideMenuPermissions['Reportings']->contains('view')))
                <li class="dropdown {{ request()->is('admin/reporting') || request()->is('admin/reporting/*') ? 'active' : ''}}">
                    <a href="{{ url('admin/reporting') }}" class="nav-link">
                        <i data-feather="bar-chart-2"></i>
                        <span>Reportings</span>
                    </a>
                </li>
            @endif


            {{--  Blogs --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Blogs') && $sideMenuPermissions['Blogs']->contains('view')))
                
                <li class="dropdown {{ request()->is('admin/blogs*') ? 'active' : '' }}">
                    <a href="{{ url('admin/blogs-index') }}" class="nav-link"><i
                            data-feather="book-open"></i><span>Blogs</span></a>
                </li>
            @endif --}}

             {{--  SEO --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['seo']->contains('seo')))
                
                <li class="dropdown {{ request()->is('admin/seo*') ? 'active' : '' }}">
                    <a href="{{ url('admin/seo') }}" class="nav-link"><i
                            data-feather="trending-up"></i><span>SEO</span></a>
                </li>
            @endif --}}
            
             {{-- Notification --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('view')))
                Notification
                <li class="dropdown {{ request()->is('admin/notification*') ? 'active' : '' }}">
                    <a href="
                {{ route('notification.index') }}
                " class="nav-link">
                        <i data-feather="bell"></i><span>Notifications</span>
                    </a>
                </li>
            @endif --}}

            {{--  FAQS --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Faqs') && $sideMenuPermissions['Faqs']->contains('view')))
                <li class="dropdown {{ request()->is('admin/faq*') ? 'active' : '' }}">
                    <a href="{{ url('admin/faq') }}" class="nav-link">
                        <i data-feather="settings"></i>
                        <span>FAQ's</span>
                    </a>
                </li>
            @endif --}}
            
             {{-- Contact Us  --}}


            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Contact us') && $sideMenuPermissions['Contact us']->contains('view')))
                {{-- Contact Us --}}
                <li class="dropdown {{ request()->is('admin/admin/contact-us*') ? 'active' : '' }}">
                    <a href="{{ url('admin/admin/contact-us') }}" class="nav-link"><i
                            data-feather="mail"></i><span>Contact
                            Us</span></a>
                </li>
            @endif


            {{--  About Us --}}

            {{-- @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('About us') && $sideMenuPermissions['About us']->contains('view')))
                About Us
                <li class="dropdown {{ request()->is('admin/about-us*') ? 'active' : '' }}">
                    <a href="{{ url('admin/about-us') }}" class="nav-link"><i
                            data-feather="help-circle"></i><span>About
                            Us</span></a>
                </li>
            @endif --}}

            


            {{--  Privacy Policy --}}

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Privacy & Policy') && $sideMenuPermissions['Privacy & Policy']->contains('view')))
                {{--  Privacy Policy --}}
                <li class="dropdown {{ request()->is('admin/privacy-policy*') ? 'active' : '' }}">
                    <a href="{{ url('admin/privacy-policy') }}" class="nav-link"><i
                            data-feather="shield"></i><span>Privacy
                            & Policy</span></a>
                </li>
            @endif




            {{--  Terms & Conditions --}}

            @if (Auth::guard('admin')->check() ||
                    ($sideMenuPermissions->has('Terms & Conditions') &&
                        $sideMenuPermissions['Terms & Conditions']->contains('view')))
                <li class="dropdown {{ request()->is('admin/term-condition*') ? 'active' : '' }}">
                    <a href="{{ url('admin/term-condition') }}" class="nav-link"><i
                            data-feather="file-text"></i><span>Terms
                            & Conditions</span></a>
                </li>
            @endif



        </ul>
        </aside>
</div>
