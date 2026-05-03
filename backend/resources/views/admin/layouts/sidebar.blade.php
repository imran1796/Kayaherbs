<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <img
                src="{{ asset('vendor/adminlte/assets/img/AdminLTELogo.png') }}"
                alt="Admin Logo"
                class="brand-image opacity-75 shadow"
            >
            <span class="brand-text fw-light">Ecommerce Admin</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul
                class="nav sidebar-menu flex-column"
                data-lte-toggle="treeview"
                role="navigation"
                aria-label="Main navigation"
                data-accordion="false"
            >
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon app-icon" aria-hidden="true">D</span>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <span class="nav-icon app-icon" aria-hidden="true">U</span>
                        <p>User Management</p>
                    </a>
                </li>
                @can('customers.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">K</span>
                            <p>Customers</p>
                        </a>
                    </li>
                @endcan
                @can('categories.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">C</span>
                            <p>Categories</p>
                        </a>
                    </li>
                @endcan
                @can('products.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">P</span>
                            <p>Products</p>
                        </a>
                    </li>
                @endcan
                @can('orders.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">O</span>
                            <p>Orders</p>
                        </a>
                    </li>
                @endcan
                @can('inventory.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.inventory.index') }}" class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">I</span>
                            <p>Inventory</p>
                        </a>
                    </li>
                @endcan
                @can('shipping.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.shipping.index') }}" class="nav-link {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">S</span>
                            <p>Shipping</p>
                        </a>
                    </li>
                @endcan
                @can('reports.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.sales') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">R</span>
                            <p>Reports</p>
                        </a>
                    </li>
                @endcan
                @can('settings.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.store-profile.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">S</span>
                            <p>Store Settings</p>
                        </a>
                    </li>
                @endcan
                @can('modules.view')
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.module-toggles.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.module-toggles.*') ? 'active' : '' }}">
                            <span class="nav-icon app-icon" aria-hidden="true">M</span>
                            <p>Module Toggles</p>
                        </a>
                    </li>
                @endcan
            </ul>
        </nav>
    </div>
</aside>
