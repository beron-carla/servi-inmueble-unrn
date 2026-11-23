<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$tarjetas = $metricas['tarjetas'];
$enlaces = $metricas['enlaces'];
$serie = $metricas['serie_mensual'];
$distribucion = $metricas['distribucion_estados'];
?>

<div class="row">
    <?php if (isset($tarjetas['usuarios_activos'])) : ?>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= (int) $tarjetas['usuarios_activos']['total'] ?></h3>
                    <p>Usuarios activos (<?= (int) $tarjetas['usuarios_activos']['admin'] ?> admin / <?= (int) $tarjetas['usuarios_activos']['propietario'] ?> propietarios)</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a class="small-box-footer" href="<?= route_to('admin.usuarios.index') ?>">Ver usuarios <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    <?php endif ?>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= (int) $tarjetas['inmuebles_activos'] ?></h3>
                <p>Inmuebles activos</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            <a class="small-box-footer" href="<?= es_admin() ? route_to('admin.inmuebles.index') : route_to('inmuebles.index') ?>">Ver inmuebles <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= (int) $tarjetas['facturas_pendientes']['cantidad'] ?></h3>
                <p>Facturas pendientes · <?= formato_moneda((float) $tarjetas['facturas_pendientes']['monto']) ?></p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
            <a class="small-box-footer" href="<?= esc($enlaces['facturas_pendientes']) ?>">Ver facturas <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= (int) $tarjetas['facturas_vencidas']['cantidad'] ?></h3>
                <p>Facturas vencidas · <?= formato_moneda((float) $tarjetas['facturas_vencidas']['monto']) ?></p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            <a class="small-box-footer" href="<?= esc($enlaces['facturas_vencidas']) ?>">Ver facturas <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= (int) $tarjetas['pagos_pendientes']['cantidad'] ?></h3>
                <p>Pagos pendientes de validación</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a class="small-box-footer" href="<?= esc($enlaces['pagos_pendientes']) ?>">Ver pagos <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= formato_moneda((float) $tarjetas['recaudacion_mes']['monto']) ?></h3>
                <p>Recaudación del mes</p>
            </div>
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            <a class="small-box-footer" href="<?= es_admin() ? route_to('admin.pagos.index') : route_to('pagos.index') ?>">Ver pagos <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <?php if (isset($tarjetas['notificaciones_no_leidas'])) : ?>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3><?= (int) $tarjetas['notificaciones_no_leidas']['cantidad'] ?></h3>
                    <p>Notificaciones sin leer</p>
                </div>
                <div class="icon"><i class="fas fa-bell"></i></div>
                <a class="small-box-footer" href="<?= esc($enlaces['notificaciones']) ?>">Ver notificaciones <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    <?php endif ?>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Evolución de los últimos 12 meses</h3></div>
            <div class="card-body">
                <?php if ($serie === []) : ?>
                    <p class="text-muted mb-0">Sin datos</p>
                <?php else : ?>
                    <canvas id="graficoMensual" height="150"></canvas>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Facturas del año actual</h3></div>
            <div class="card-body">
                <?php if (array_sum($distribucion) === 0) : ?>
                    <p class="text-muted mb-0">Sin datos</p>
                <?php else : ?>
                    <canvas id="graficoEstados" height="150"></canvas>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
<?php if ($serie !== []) : ?>
new Chart(document.getElementById('graficoMensual'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(static fn(array $fila): string => $fila['mes'], $serie), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [
            { label: 'Facturación emitida', data: <?= json_encode(array_map(static fn(array $fila): float => $fila['emitido'], $serie)) ?>, borderColor: '#007bff', tension: 0.2 },
            { label: 'Cobranza', data: <?= json_encode(array_map(static fn(array $fila): float => $fila['cobrado'], $serie)) ?>, borderColor: '#28a745', tension: 0.2 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
<?php endif ?>
<?php if (array_sum($distribucion) > 0) : ?>
new Chart(document.getElementById('graficoEstados'), {
    type: 'doughnut',
    data: {
        labels: ['Pendiente', 'Vencida', 'Pagada'],
        datasets: [{ data: <?= json_encode([$distribucion['pendiente'], $distribucion['vencida'], $distribucion['pagada']]) ?>, backgroundColor: ['#ffc107', '#dc3545', '#28a745'] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
<?php endif ?>
</script>
<?= $this->endSection() ?>
