<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Sistema Inventario TI - Grupo Coyahue')</title>

    <!-- Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- jQuery UI Autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <!-- Ícono -->
    <link rel="icon" href="{{ asset('images/logo-coyahue.png') }}" type="image/png">

    <!-- Estilos personalizados -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Librería QR -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<div class="container-fluid">
    <div class="layout-wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="position-sticky pt-4">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('registro_equipo.create') ? 'active' : '' }}" href="{{ route('registro_equipo.create') }}">
                            <i class="fas fa-plus-circle me-2"></i>Registros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inventario') ? 'active' : '' }}" href="{{ route('inventario') }}">
                            <i class="fas fa-laptop me-2"></i>Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('gestion_proveedores') ? 'active' : '' }}" href="{{ route('gestion_proveedores') }}">
                            <i class="fas fa-truck me-2"></i>Proveedores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('gestion_usuarios') ? 'active' : '' }}" href="{{ route('gestion_usuarios') }}">
                            <i class="fas fa-users me-2"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('gestion_sucursales') ? 'active' : '' }}" href="{{ route('gestion_sucursales') }}">
                            <i class="fas fa-building me-2"></i>Sucursales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog me-2"></i>Ajustes
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenido principal -->
        <main id="mainContent" class="main-content transition-all">
            <!-- Header -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary me-2">
                        <i id="sidebarIcon" class="fas fa-angle-double-left"></i>
                    </button>

                    <!-- Logo -->
                    <div class="navbar-brand d-flex align-items-center">
                        <img src="{{ asset('images/logo-coyahue.png') }}" alt="Grupo Coyahue" class="logo-header">
                    </div>

                    <div class="d-flex">
                        <!-- Notificaciones -->
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle position-relative" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                @if(isset($notificaciones) && count($notificaciones) > 0)
                                    <span class="badge bg-danger">{{ count($notificaciones) }}</span>
                                @endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" style="min-width: 400px; max-height: 500px; overflow-y: auto;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <strong>Notificaciones</strong>
                                    @if(isset($notificaciones) && count($notificaciones) > 0)
                                        <form action="{{ route('notificaciones.limpiar') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0">
                                                <small>Limpiar</small>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="dropdown-divider"></div>
                                
                                @if(isset($notificaciones) && count($notificaciones) > 0)
                                    @foreach($notificaciones as $notif)
                                        @if(isset($notif['url']))
                                            <a href="{{ $notif['url'] }}" class="dropdown-item p-3 border-start 
                                                @if($notif['prioridad'] == 'urgente') border-danger
                                                @elseif($notif['prioridad'] == 'alta') border-warning  
                                                @elseif($notif['prioridad'] == 'media') border-info
                                                @else border-secondary @endif border-3 text-decoration-none" 
                                                style="cursor: pointer;">
                                        @else
                                            <div class="dropdown-item p-3 border-start 
                                                @if($notif['prioridad'] == 'urgente') border-danger
                                                @elseif($notif['prioridad'] == 'alta') border-warning  
                                                @elseif($notif['prioridad'] == 'media') border-info
                                                @else border-secondary @endif border-3">
                                        @endif
                                        
                                            <div class="d-flex align-items-start">
                                                <i class="fas 
                                                    @if($notif['prioridad'] == 'urgente') fa-exclamation-triangle text-danger
                                                    @elseif($notif['prioridad'] == 'alta') fa-exclamation-circle text-warning
                                                    @elseif(str_contains($notif['mensaje'], 'ASIGNACIÓN') || str_contains($notif['mensaje'], '✅')) fa-users text-success
                                                    @elseif(str_contains($notif['mensaje'], 'SUCURSAL') || str_contains($notif['mensaje'], '🏢')) fa-building text-primary
                                                    @elseif(str_contains($notif['mensaje'], 'DESASIGNACIÓN') || str_contains($notif['mensaje'], '❌')) fa-times-circle text-secondary
                                                    @else fa-info-circle text-info @endif 
                                                    mt-1 me-2"></i>
                                                <div class="flex-grow-1">
                                                    <p class="mb-1 small">{{ $notif['mensaje'] }}</p>
                                                    <small class="text-muted">{{ $notif['fecha'] ?? now()->format('d/m/Y H:i') }}</small>
                                                </div>
                                            </div>
                                        
                                        @if(isset($notif['url']))
                                            </a>
                                        @else
                                            </div>
                                        @endif
                                        
                                        @if(!$loop->last)
                                            <div class="dropdown-divider"></div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="dropdown-item text-center py-4 text-muted">
                                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                        <p class="mb-0">No hay notificaciones</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Usuario -->
                        <div class="dropdown ms-3">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>{{ explode(' ', Auth::user()->nombre ?? 'Usuario')[0] }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Contenido de la página -->
            <div class="container-fluid py-4">
                @yield('contenido')
            </div>

<!-- Popup QR global -->
<div id="popupQR" class="position-fixed top-0 start-0 w-100 h-100 d-none" 
     style="background: rgba(0,0,0,0.6); z-index: 1050;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="card shadow mb-4" style="width: 90%; max-width: 500px;">
            <div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Escanear Código QR</h5>
                <button id="cerrarQR" class="btn btn-sm text-white" style="font-size: 1.5rem; line-height: 1;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body text-center">
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-result" class="mt-3 text-muted text-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery UI Autocomplete -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Sidebar toggle con localStorage e ícono dinámico -->
<script>
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebarIcon = document.getElementById('sidebarIcon');

    // Restaurar estado desde localStorage
    const estadoGuardado = localStorage.getItem('sidebarEstado');
    if (estadoGuardado === 'collapsed') {
        sidebar.classList.add('collapsed');
        main.classList.add('expanded');
        sidebarIcon.classList.remove('fa-angle-double-left');
        sidebarIcon.classList.add('fa-angle-double-right');
    }

    // Alternar y guardar estado
    toggleBtn?.addEventListener('click', () => {
        const estaColapsado = sidebar.classList.toggle('collapsed');
        if (estaColapsado) {
            main.classList.add('expanded');
            sidebarIcon.classList.remove('fa-angle-double-left');
            sidebarIcon.classList.add('fa-angle-double-right');
            localStorage.setItem('sidebarEstado', 'collapsed');
        } else {
            main.classList.remove('expanded');
            sidebarIcon.classList.remove('fa-angle-double-right');
            sidebarIcon.classList.add('fa-angle-double-left');
            localStorage.setItem('sidebarEstado', 'expanded');
        }
    });

    // Cerrar popup QR
    document.getElementById('cerrarQR')?.addEventListener('click', () => {
        document.getElementById('popupQR')?.classList.add('d-none');
    });
</script>

@stack('scripts')
</body>
</html>