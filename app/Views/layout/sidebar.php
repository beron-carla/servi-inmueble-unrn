<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= site_url('/') ?>" class="brand-link">
        <span class="brand-text font-weight-light">ServiInmueble 2026</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="<?= site_url('/') ?>" class="nav-link active">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Inicio</p>
                    </a>
                </li>

                <?php if (es_admin()) : ?>
                    <li class="nav-item">
                        <a href="<?= route_to('admin.usuarios.index') ?>" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= route_to('admin.tipos.index') ?>" class="nav-link">
                            <i class="nav-icon fas fa-list"></i>
                            <p>Tipos de servicio</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= route_to('admin.tipos.create') ?>" class="nav-link">
                            <i class="nav-icon fas fa-plus"></i>
                            <p>Alta de tipo de servicio</p>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a href="<?= route_to('catalogo.servicios') ?>" class="nav-link">
                            <i class="nav-icon fas fa-list"></i>
                            <p>Catálogo de servicios</p>
                        </a>
                    </li>
                <?php endif ?>

                <?php /* MENU: Inmuebles (HU-10 a HU-12). Completar acá. */ ?>

                <?php /* MENU: Servicios del inmueble (HU-13). Completar acá. */ ?>

                <?php /* MENU: Facturas (HU-14 y HU-15). Completar acá. */ ?>
            </ul>
        </nav>
    </div>
</aside>
