<?php $this->load->view('partials/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-primary mb-4">
                <i class="fas fa-calculator"></i> Asistente Contable Inteligente
            </h1>
            
            <!-- Tarjetas de Resumen -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Ventas Pendientes</h5>
                            <h2 class="mb-0">$<?= number_format($summary['pending_sales'], 2) ?></h2>
                            <small><?= $pending_sales_count ?> operaciones</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Compras Pendientes</h5>
                            <h2 class="mb-0">$<?= number_format($summary['pending_purchases'], 2) ?></h2>
                            <small><?= $pending_purchases_count ?> operaciones</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Cargos</h5>
                            <h2 class="mb-0">$<?= number_format($summary['total_debits'], 2) ?></h2>
                            <small>Este mes</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Abonos</h5>
                            <h2 class="mb-0">$<?= number_format($summary['total_credits'], 2) ?></h2>
                            <small>Este mes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lotes Consolidados Pendientes -->
            <?php if ($summary['open_batches_count'] > 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-boxes"></i> 
                Tienes <strong><?= $summary['open_batches_count'] ?> lotes consolidados</strong> 
                por $<?= number_format($summary['open_batches_amount'], 2) ?> pendientes de timbrar.
                <a href="<?= site_url('accounting/consolidation_batches') ?>" class="btn btn-sm btn-light ml-2">Ver lotes</a>
            </div>
            <?php endif; ?>

            <!-- Accesos Rápidos -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                            <h5>Conciliación</h5>
                            <p class="text-muted">Revisa y decide sobre cada transacción</p>
                            <a href="<?= site_url('accounting/reconciliation_queue') ?>" class="btn btn-primary btn-block">
                                Ir a Conciliación
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-magic fa-3x text-success mb-3"></i>
                            <h5>Proceso Automático</h5>
                            <p class="text-muted">Acepta sugerencias del sistema</p>
                            <button onclick="acceptAllSuggestions()" class="btn btn-success btn-block">
                                Aceptar Sugerencias
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                            <h5>Reportes</h5>
                            <p class="text-muted">Estados financieros y balances</p>
                            <a href="<?= site_url('accounting/reports') ?>" class="btn btn-info btn-block">
                                Ver Reportes
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimos Movimientos -->
            <div class="card mt-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Últimas Transacciones Procesadas</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Estado Fiscal</th>
                                <th>UUID CFDI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $this->db->select('e.*, q.transaction_type');
                            $this->db->from('onebox_journal_entries e');
                            $this->db->join('onebox_reconciliation_queue q', 'e.reference_id = q.transaction_id AND e.reference_type = q.transaction_type', 'left');
                            $this->db->order_by('e.entry_date', 'desc');
                            $this->db->limit(10);
                            $recent_entries = $this->db->get()->result_array();
                            
                            foreach ($recent_entries as $entry): 
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($entry['entry_date'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $entry['reference_type'] === 'SALE' ? 'success' : 'warning' ?>">
                                        <?= $entry['reference_type'] ?>
                                    </span>
                                </td>
                                <td><?= $entry['description'] ?></td>
                                <td>$<?= number_format(array_sum($this->Accounting_model->get_entry_totals($entry['entry_id'])), 2) ?></td>
                                <td>
                                    <?php if ($entry['is_fiscal']): ?>
                                        <span class="badge badge-success">Facturado</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Facturado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($entry['cfdi_uuid']): ?>
                                        <small><code><?= substr($entry['cfdi_uuid'], 0, 8) ?>...</code></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function acceptAllSuggestions() {
    if (!confirm('¿Estás seguro de aceptar todas las sugerencias automáticas? Esta acción procesará múltiples transacciones.')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('accounting/accept_all_suggestions') ?>',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error de conexión con el servidor');
        }
    });
}
</script>

<?php $this->load->view('partials/footer'); ?>
