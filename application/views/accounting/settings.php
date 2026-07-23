<?php $this->load->view('partials/header'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= site_url('accounting') ?>">Contabilidad</a></li>
                    <li class="breadcrumb-item active">Configuración</li>
                </ol>
            </nav>
            
            <h2><i class="fas fa-cog"></i> Configuración del Asistente Contable</h2>
            <p class="text-muted">
                Personaliza las reglas que el sistema utiliza para generar sugerencias automáticas.
            </p>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $success_message ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Reglas de Sugerencia Automática</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <!-- Monto mínimo para sugerir facturación -->
                        <div class="form-group">
                            <label for="min_amount">
                                <strong>Monto mínimo para sugerir facturación individual</strong>
                            </label>
                            <p class="text-muted small">
                                Cuando una venta o compra supere este monto, el sistema sugerirá automáticamente generar un CFDI individual.
                            </p>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" name="min_amount" id="min_amount" 
                                       class="form-control" value="<?= $current_config['min_amount_for_invoice'] ?>">
                            </div>
                        </div>

                        <hr>

                        <!-- Ventas a crédito -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="auto_credit" class="custom-control-input" 
                                       id="auto_credit" value="1" 
                                       <?= $current_config['auto_invoice_credit_sales'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="auto_credit">
                                    <strong>Siempre sugerir facturación para ventas a crédito</strong>
                                </label>
                            </div>
                            <p class="text-muted small mt-2">
                                Las ventas a crédito siempre deben estar respaldadas por un comprobante fiscal para proteger la cartera.
                            </p>
                        </div>

                        <hr>

                        <!-- Umbral de cliente frecuente -->
                        <div class="form-group">
                            <label for="threshold">
                                <strong>Umbral de historial para clientes/proveedores frecuentes</strong>
                            </label>
                            <p class="text-muted small">
                                Cuando un cliente o proveedor acumule más de este monto en operaciones históricas, el sistema sugerirá facturación.
                            </p>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" name="threshold" id="threshold" 
                                       class="form-control" value="<?= $current_config['customer_threshold'] ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Panel de Ayuda -->
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-lightbulb text-warning"></i> ¿Cómo funciona?</h5>
                    <hr>
                    <p class="small">
                        El Asistente Contable analiza cada transacción y genera sugerencias basadas en estas reglas:
                    </p>
                    <ul class="small">
                        <li><strong>Monto alto:</strong> Operaciones significativas requieren comprobantes.</li>
                        <li><strong>Crédito:</strong> Ventas a crédito necesitan respaldo fiscal.</li>
                        <li><strong>Frecuencia:</strong> Clientes con historial merecen atención especial.</li>
                    </ul>
                    
                    <h6 class="mt-4">Tipos de Decisión:</h6>
                    <ul class="small">
                        <li><span class="badge badge-success">Facturar</span> - Genera CFDI individual y asiento contable.</li>
                        <li><span class="badge badge-info">Lote Global</span> - Agrupa con otras operaciones no facturadas.</li>
                        <li><span class="badge badge-secondary">Ignorar</span> - Solo registro interno, sin efectos fiscales.</li>
                    </ul>
                </div>
            </div>

            <!-- Estadísticas de Uso -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Estadísticas del Sistema</h5>
                    <hr>
                    <div class="text-center">
                        <h2 class="text-primary">
                            <?php 
                            $this->db->select_sum('amount');
                            $total = $this->db->get('onebox_reconciliation_queue')->row()->amount ?? 0;
                            ?>
                            $<?= number_format($total, 2) ?>
                        </h2>
                        <p class="text-muted">Total procesado históricamente</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
