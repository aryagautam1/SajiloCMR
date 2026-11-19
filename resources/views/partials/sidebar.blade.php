<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sidebar-header">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="Sajilo CRM dashboard">
            <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand-mark" width="38" height="38">
            <span class="brand-text">
                <span class="brand-name">Sajilo</span>
                <span class="brand-tag">CRM</span>
            </span>
        </a>
        <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sidebar-scroll">
        <nav class="sidebar-nav">
            <span class="nav-section">Workspace</span>

            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                <span class="nav-label">Dashboard</span>
            </a>

            <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}" title="Clients">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span class="nav-label">Clients</span>
            </a>

            <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}" title="Projects">
                <i class="bi bi-kanban" aria-hidden="true"></i>
                <span class="nav-label">Projects</span>
            </a>

            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" title="Reports">
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                <span class="nav-label">Reports</span>
            </a>

            @if (auth()->user()->isAdmin())
                <span class="nav-section">Administration</span>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Team members">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <span class="nav-label">Team members</span>
                </a>
            @endif

            <span class="nav-section">Account</span>
            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}" title="Settings">
                <i class="bi bi-gear" aria-hidden="true"></i>
                <span class="nav-label">Settings</span>
            </a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <a class="sidebar-user" href="{{ route('settings.index') }}" title="Open profile settings">
            <x-user-avatar :user="auth()->user()" />
            <span class="user-meta">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="user-role">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Staff' }}</span>
            </span>
        </a>
    </div>
</aside>
