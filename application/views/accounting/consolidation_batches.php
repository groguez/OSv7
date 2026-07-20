<?php $this->load->view('partials/header'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= site_url('accounting') ?>">Contabilidad</a></li>
                    <li class="breadcrumb-item active">Lotes Consolidados</li>
                </ol>
            </nav>
            
            <h2><i class="fas fa-boxes"></i> Lotes de Ventas Globales</h2>
            <p class="text-muted">
                Estos lotes agrupan operaciones no facturadas individualmente. Puedes generar un CFDI global para todo el lote.
            </p>
        </div>
    </div>

    <?php if (empty($batches)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No hay lotes consolidados creados aún.
    </div>
    <?php else: ?>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Operaciones</th>
                        <th>Subtotal</th>
                        <th>Impuestos</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>UUID CFDI</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($batch['batch_date'])) ?></td>
                        <td>
                            <span class="badge badge-primary"><?= $batch['transaction_count'] ?> operaciones</span>
                            <?php if ($batch['transaction_ids']): ?>
                                <small class="text-muted d-block">IDs: <?= substr($batch['transaction_ids'], 0, 50) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($batch['total_amount'], 2) ?></td>
                        <td>$<?= number_format($batch['total_tax'], 2) ?></td>
                        <td class="font-weight-bold">$<?= number_format($batch['total_amount'] + $batch['total_tax'], 2) ?></td>
                        <td>
                            <?php 
                            $badge_class = [
                                'OPEN' => 'warning',
                                'CLOSED' => 'secondary',
                                'TIMBRADO' => 'success'
                            ];
                            ?>
                            <span class="badge badge-<?= $badge_class[$batch['status']] ?>">
                                <?= $batch['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($batch['cfdi_uuid']): ?>
                                <small><code><?= $batch['cfdi_uuid'] ?></code></small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($batch['status'] === 'OPEN'): ?>
                                <button onclick="generateGlobalCfdi(<?= $batch['batch_id'] ?>)" 
                                        class="btn btn-sm btn-success">
                                    <i class="fas fa-file-invoice"></i> Generar CFDI Global
                                </button>
                            <?php else: ?>
                                <span class="text-muted">Procesado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function generateGlobalCfdi(batchId) {
    if (!confirm('¿Estás seguro de generar un CFDI global para este lote? Esta acción timbrará todas las operaciones agrupadas.')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('accounting/generate_global_cfdi/') ?>' + batchId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('CFDI global generado exitosamente!\nUUID: ' + response.uuid);
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
