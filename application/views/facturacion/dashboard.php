<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/bootstrap-3.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/base-modern.css">
    <style>
        .dashboard-stats { margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0; font-size: 2.5em; color: #007bff; }
        .stat-card p { margin: 10px 0 0; color: #666; font-weight: bold; }
        .stat-card.success h3 { color: #28a745; }
        .stat-card.warning h3 { color: #ffc107; }
        .stat-card.danger h3 { color: #dc3545; }
        .panel-custom { border-color: #007bff; }
        .panel-custom > .panel-heading { background-color: #007bff; color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>Facturación Electrónica CFDI 4.0</h1>
            <p class="text-muted">Gestione la facturación electrónica de su empresa conforme a los requisitos del SAT</p>
        </div>
    </div>

    <div class="row dashboard-stats">
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?php echo isset($total_facturas) ? $total_facturas : '0'; ?></h3>
                <p>Facturas Emitidas</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <h3><?php echo isset($facturas_activas) ? $facturas_activas : '0'; ?></h3>
                <p>Activas este Mes</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <h3><?php echo isset($pendientes_timbrado) ? $pendientes_timbrado : '0'; ?></h3>
                <p>Pendientes de Timbrado</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3>$<?php echo isset($monto_total) ? number_format($monto_total, 2) : '0.00'; ?></h3>
                <p>Monto Total Facturado</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title">⚡ Acciones Rápidas</h3>
                </div>
                <div class="panel-body">
                    <a href="<?php echo site_url('sales'); ?>" class="btn btn-primary btn-lg">
                        <i class="glyphicon glyphicon-shopping-cart"></i> Nueva Venta
                    </a>
                    <a href="<?php echo site_url('facturacion/facturacion_electronica/catalogos'); ?>" class="btn btn-info btn-lg">
                        <i class="glyphicon glyphicon-list"></i> Ver Catálogos SAT
                    </a>
                    <a href="<?php echo site_url('config/ticket_config'); ?>" class="btn btn-default btn-lg">
                        <i class="glyphicon glyphicon-cog"></i> Configurar Tickets/Facturas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>ℹ️ Información Importante:</strong><br>
                Los archivos XML generados por este módulo están estructurados conforme a los catálogos oficiales del SAT (CFDI 4.0).
                Para que tengan validez fiscal, deben ser timbrados a través de un Proveedor Autorizado de Certificación (PAC).
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js_phppointofsale/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js_phppointofsale/bootstrap.min.js"></script>
</body>
</html>
