<?php $this->load->view('partials/header'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= site_url('accounting') ?>">Contabilidad</a></li>
                    <li class="breadcrumb-item active">Cola de Conciliación</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-tasks"></i> Cola de Conciliación Inteligente</h2>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                        Filtrar por Tipo
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="<?= site_url('accounting/reconciliation_queue?type=all&status=' . $filter_status) ?>">Todos</a>
                        <a class="dropdown-item" href="<?= site_url('accounting/reconciliation_queue?type=SALE&status=' . $filter_status) ?>">Ventas</a>
                        <a class="dropdown-item" href="<?= site_url('accounting/reconciliation_queue?type=PURCHASE&status=' . $filter_status) ?>">Compras</a>
                    </div>
                </div>
            </div>
            
            <p class="text-muted">
                Revisa cada transacción y decide si se factura individualmente, se incluye en un lote global, o se ignora fiscalmente.
                El sistema genera sugerencias automáticas basadas en reglas de negocio.
            </p>
        </div>
    </div>

    <?php if (empty($queue_items)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> ¡Excelente! No hay transacciones pendientes de conciliar.
    </div>
    <?php else: ?>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cliente/Proveedor</th>
                        <th>Monto</th>
                        <th>Impuestos</th>
                        <th>Sugerencia del Sistema</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue_items as $item): ?>
                    <tr id="queue-row-<?= $item['queue_id'] ?>">
                        <td><?= date('d/m/Y H:i', strtotime($item['transaction_date'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $item['transaction_type'] === 'SALE' ? 'success' : 'warning' ?>">
                                <?= $item['transaction_type'] === 'SALE' ? 'Venta' : 'Compra' ?>
                            </span>
                        </td>
                        <td>
                            <?= $item['customer_name'] ?? $item['supplier_name'] ?? 'Mostrador' ?>
                        </td>
                        <td class="font-weight-bold">$<?= number_format($item['amount'], 2) ?></td>
                        <td>$<?= number_format($item['tax_amount'], 2) ?></td>
                        <td>
                            <?php if ($item['suggestion_reason']): ?>
                                <small class="text-primary">
                                    <i class="fas fa-lightbulb"></i> <?= $item['suggestion_reason'] ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">Sin sugerencia</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button onclick="processDecision(<?= $item['queue_id'] ?>, 'FACTURED')" 
                                        class="btn btn-success" title="Facturar Individualmente">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                                <button onclick="processDecision(<?= $item['queue_id'] ?>, 'NON_FACTURED_GLOBAL')" 
                                        class="btn btn-info" title="Incluir en Lote Global">
                                    <i class="fas fa-boxes"></i>
                                </button>
                                <button onclick="processDecision(<?= $item['queue_id'] ?>, 'NON_FACTURED_IGNORED')" 
                                        class="btn btn-secondary" title="Ignorar Fiscalmente">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para ingresar UUID CFDI -->
<div class="modal fade" id="cfdiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ingresar UUID de CFDI</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="cfdi_uuid_input" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXXXXXXXXXXXXXX">
                <input type="hidden" id="current_queue_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmWithCfdi()">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script>
let pendingDecision = null;

function processDecision(queueId, decision) {
    if (decision === 'FACTURED') {
        // Mostrar modal para UUID
        $('#current_queue_id').val(queueId);
        $('#cfdiModal').modal('show');
        pendingDecision = decision;
    } else {
        // Procesar directamente sin UUID
        submitDecision(queueId, decision, null);
    }
}

function confirmWithCfdi() {
    const queueId = $('#current_queue_id').val();
    const uuid = $('#cfdi_uuid_input').val();
    submitDecision(queueId, pendingDecision, uuid);
    $('#cfdiModal').modal('hide');
}

function submitDecision(queueId, decision, cfdiUuid) {
    $.ajax({
        url: '<?= site_url('accounting/process_decision') ?>',
        type: 'POST',
        data: {
            queue_id: queueId,
            decision: decision,
            cfdi_uuid: cfdiUuid
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#queue-row-' + queueId).fadeOut();
                
                let message = '';
                switch(decision) {
                    case 'FACTURED':
                        message = 'Transacción marcada como facturada. Asiento contable generado.';
                        break;
                    case 'NON_FACTURED_GLOBAL':
                        message = 'Transacción agregada a lote global del día.';
                        break;
                    case 'NON_FACTURED_IGNORED':
                        message = 'Transacción registrada como no facturable.';
                        break;
                }
                
                // Mostrar notificación temporal
                const notification = $('<div class="alert alert-success alert-dismissible fade show">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    message + '</div>');
                $('.container-fluid').prepend(notification);
                
                setTimeout(() => notification.fadeOut(), 3000);
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
