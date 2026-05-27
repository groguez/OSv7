<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/bootstrap-3.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/base-modern.css">
    <style>
        .catalogo-section { margin-bottom: 30px; }
        .table-cat { font-size: 13px; }
        .table-cat th { background-color: #007bff; color: white; }
        .nav-tabs > li.active > a { background-color: #007bff; color: white; border-color: #007bff; }
        .nav-tabs > li > a { color: #007bff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1>Catálogos del SAT - CFDI 4.0</h1>
            <p class="text-muted">Catálogos oficiales vigentes para la generación de comprobantes fiscales</p>
            
            <a href="<?php echo site_url('facturacion/facturacion_electronica'); ?>" class="btn btn-default">
                <i class="glyphicon glyphicon-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#formas_pago" aria-controls="formas_pago" role="tab" data-toggle="tab">Formas de Pago</a></li>
                <li role="presentation"><a href="#metodos_pago" aria-controls="metodos_pago" role="tab" data-toggle="tab">Métodos de Pago</a></li>
                <li role="presentation"><a href="#usos_cfdi" aria-controls="usos_cfdi" role="tab" data-toggle="tab">Usos de CFDI</a></li>
                <li role="presentation"><a href="#regimenes" aria-controls="regimenes" role="tab" data-toggle="tab">Regímenes Fiscales</a></li>
                <li role="presentation"><a href="#productos" aria-controls="productos" role="tab" data-toggle="tab">Productos y Servicios</a></li>
            </ul>

            <div class="tab-content" style="margin-top: 20px;">
                <!-- Formas de Pago -->
                <div role="tabpanel" class="tab-pane active" id="formas_pago">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>c_FormaPago</strong> - Catálogo de formas de pago</div>
                        <div class="panel-body">
                            <table class="table table-bordered table-cat">
                                <thead><tr><th>Código</th><th>Descripción</th></tr></thead>
                                <tbody>
                                    <?php foreach($catalogs['formas_pago'] as $codigo => $descripcion): ?>
                                    <tr><td><?php echo $codigo; ?></td><td><?php echo $descripcion; ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Métodos de Pago -->
                <div role="tabpanel" class="tab-pane" id="metodos_pago">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>c_MetodoPago</strong> - Catálogo de métodos de pago</div>
                        <div class="panel-body">
                            <table class="table table-bordered table-cat">
                                <thead><tr><th>Código</th><th>Descripción</th></tr></thead>
                                <tbody>
                                    <?php foreach($catalogs['metodos_pago'] as $codigo => $descripcion): ?>
                                    <tr><td><?php echo $codigo; ?></td><td><?php echo $descripcion; ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Usos de CFDI -->
                <div role="tabpanel" class="tab-pane" id="usos_cfdi">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>c_UsoCFDI</strong> - Catálogo de usos de CFDI</div>
                        <div class="panel-body">
                            <table class="table table-bordered table-cat">
                                <thead><tr><th>Código</th><th>Descripción</th></tr></thead>
                                <tbody>
                                    <?php 
                                    // Combinar morales y físicas
                                    $todos_usos = array_merge($catalogs['usos_cfdi_morales'], $catalogs['usos_cfdi_fisicas']);
                                    foreach($todos_usos as $codigo => $descripcion): ?>
                                    <tr><td><?php echo $codigo; ?></td><td><?php echo $descripcion; ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Regímenes Fiscales -->
                <div role="tabpanel" class="tab-pane" id="regimenes">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>c_RegimenFiscal</strong> - Catálogo de regímenes fiscales</div>
                        <div class="panel-body">
                            <table class="table table-bordered table-cat">
                                <thead><tr><th>Código</th><th>Descripción</th></tr></thead>
                                <tbody>
                                    <?php foreach($catalogs['regimenes_fiscales'] as $codigo => $descripcion): ?>
                                    <tr><td><?php echo $codigo; ?></td><td><?php echo $descripcion; ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Productos y Servicios -->
                <div role="tabpanel" class="tab-pane" id="productos">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>c_ClaveProdServ</strong> - Catálogo de productos y servicios (muestra parcial)</div>
                        <div class="panel-body">
                            <div class="alert alert-info">
                                Este catálogo contiene más de 1000 claves. Se muestran las principales categorías.
                            </div>
                            <table class="table table-bordered table-cat">
                                <thead><tr><th>Código</th><th>Descripción</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $contador = 0;
                                    foreach($catalogs['claves_prod_serv'] as $codigo => $descripcion): 
                                        if($contador++ >= 50) break; // Mostrar solo primeras 50
                                    ?>
                                    <tr><td><?php echo $codigo; ?></td><td><?php echo $descripcion; ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js_phppointofsale/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js_phppointofsale/bootstrap.min.js"></script>
</body>
</html>
