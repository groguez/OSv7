<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/bootstrap-3.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/base-modern.css">
    <style>
        .cfdi-preview { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .json-viewer { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 12px; }
        .xml-viewer { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap; word-wrap: break-word; }
        .btn-group-custom { margin-bottom: 20px; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status-success { background: #28a745; color: white; }
        .status-warning { background: #ffc107; color: #333; }
        .panel-cfdi { border-color: #007bff; }
        .panel-cfdi > .panel-heading { background-color: #007bff; color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $page_title; ?></h1>
            
            <div class="btn-group btn-group-custom">
                <a href="<?php echo site_url('facturacion/facturacion_electronica/descargar_xml/' . $sale_id); ?>" class="btn btn-success">
                    <i class="glyphicon glyphicon-download"></i> Descargar XML
                </a>
                <a href="<?php echo site_url('facturacion/facturacion_electronica/descargar_json/' . $sale_id); ?>" class="btn btn-info">
                    <i class="glyphicon glyphicon-file"></i> Descargar JSON
                </a>
                <a href="<?php echo site_url('sales/receipt/' . $sale_id); ?>" class="btn btn-default">
                    <i class="glyphicon glyphicon-arrow-left"></i> Volver a Venta
                </a>
            </div>

            <div class="alert alert-info">
                <strong>CFDI 4.0 Generado Exitosamente</strong><br>
                Este archivo XML está estructurado conforme a los catálogos del SAT y está listo para ser timbrado por un PAC autorizado.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-cfdi">
                        <div class="panel-heading">
                            <h3 class="panel-title">📄 Estructura JSON</h3>
                        </div>
                        <div class="panel-body">
                            <pre class="json-viewer"><?php echo $cfdi_json; ?></pre>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">📋 Vista XML</h3>
                        </div>
                        <div class="panel-body">
                            <pre class="xml-viewer"><?php echo htmlspecialchars($cfdi_xml); ?></pre>
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
