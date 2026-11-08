<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <?php if (session()->get('id_usuario')) : ?>
            <li class="nav-item d-flex align-items-center mr-3">
                <span class="nav-link"><?= esc(session()->get('nombre_completo')) ?></span>
            </li>
            <li class="nav-item">
                <form action="<?= route_to('auth.logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm mt-1">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        Cerrar sesión
                    </button>
                </form>
            </li>
        <?php endif ?>
    </ul>
</nav>
