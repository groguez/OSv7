<?php $this->load->view('partials/header'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= site_url('accounting') ?>">Contabilidad</a></li>
                    <li class="breadcrumb-item active">Reportes Financieros</li>
                </ol>
            </nav>
            
            <h2><i class="fas fa-chart-line"></i> Reportes Contables</h2>
            
            <!-- Filtros de Fecha -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="form-inline">
                        <label class="mr-2">Período:</label>
                        <input type="date" name="from" value="<?= $date_from ?>" class="form-control mr-2">
                        <span class="mr-2">a</span>
                        <input type="date" name="to" value="<?= $date_to ?>" class="form-control mr-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por Cuenta -->
    <div class="row">
        <div class="col-md-12">
            <h4 class="mb-3">Balance de Comprobación Simplificado</h4>
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Cuenta</th>
                                <th>Nombre</th>
                                <th class="text-right">Cargos</th>
                                <th class="text-right">Abonos</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_debits = 0;
                            $total_credits = 0;
                            
                            foreach ($account_summary as $account): 
                                $balance = $account['total_debit'] - $account['total_credit'];
                                $total_debits += $account['total_debit'];
                                $total_credits += $account['total_credit'];
                            ?>
                            <tr>
                                <td><strong><?= $account['account_code'] ?></strong></td>
                                <td><?= $account['account_name'] ?></td>
                                <td class="text-right">$<?= number_format($account['total_debit'], 2) ?></td>
                                <td class="text-right">$<?= number_format($account['total_credit'], 2) ?></td>
                                <td class="text-right font-weight-bold <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format(abs($balance), 2) ?>
                                    <small class="text-muted">(<?= $balance >= 0 ? 'C' : 'A' ?>)</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="thead-dark">
                            <tr>
                                <th colspan="2">TOTALES</th>
                                <th class="text-right">$<?= number_format($total_debits, 2) ?></th>
                                <th class="text-right">$<?= number_format($total_credits, 2) ?></th>
                                <th class="text-right">$<?= number_format(abs($total_debits - $total_credits), 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <?php if (abs($total_debits - $total_credits) < 0.01): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle"></i> El balance está cuadrado correctamente.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> El balance no está cuadrado. Diferencia: $<?= number_format(abs($total_debits - $total_credits), 2) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Libro Diario -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4 class="mb-3">Libro Diario del Período</h4>
            <div class="card">
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Asiento</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th class="text-right">Cargo</th>
                                <th class="text-right">Abono</th>
                                <th>Fiscal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($journal_entries as $entry): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($entry['entry_date'])) ?></td>
                                <td><strong>#<?= $entry['entry_id'] ?></strong></td>
                                <td><?= $entry['description'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $entry['reference_type'] === 'SALE' ? 'success' : ($entry['reference_type'] === 'PURCHASE' ? 'warning' : 'info') ?>">
                                        <?= $entry['reference_type'] ?>
                                    </span>
                                </td>
                                <td class="text-right">$<?= number_format($entry['total_debit'], 2) ?></td>
                                <td class="text-right">$<?= number_format($entry['total_credit'], 2) ?></td>
                                <td>
                                    <?php if ($entry['is_fiscal']): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
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

    <!-- Utilidad del Período -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calculator"></i> Resultado del Ejercicio</h5>
                    <hr>
                    
                    <?php
                    // Calcular ingresos y gastos
                    $ingresos = 0;
                    $gastos = 0;
                    
                    foreach ($account_summary as $account) {
                        if (strpos($account['account_code'], '4') === 0) { // Cuentas de ingreso
                            $ingresos += $account['total_credit'];
                        }
                        if (strpos($account['account_code'], '5') === 0) { // Cuentas de gasto
                            $gastos += $account['total_debit'];
                        }
                    }
                    
                    $utilidad = $ingresos - $gastos;
                    ?>
                    
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-muted">Ingresos Totales</h6>
                            <h3 class="text-success">$<?= number_format($ingresos, 2) ?></h3>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">Gastos Totales</h6>
                            <h3 class="text-danger">$<?= number_format($gastos, 2) ?></h3>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <h5>Utilidad Neta</h5>
                        <h2 class="<?= $utilidad >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format($utilidad, 2) ?>
                        </h2>
                        <p class="text-muted">
                            <?= $utilidad >= 0 ? '¡El negocio tiene ganancias!' : 'El negocio tiene pérdidas.' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle"></i> Información Fiscal</h5>
                    <hr>
                    
                    <?php
                    $iva_acreditable = 0;
                    $iva_trasladado = 0;
                    
                    foreach ($account_summary as $account) {
                        if ($account['account_code'] === '2200') { // IVA por pagar
                            $iva_trasladado = $account['total_credit'];
                        }
                        if ($account['account_code'] === '5200') { // IVA acreditable (en gastos)
                            $iva_acreditable = $account['total_debit'];
                        }
                    }
                    
                    $iva_a_pagar = $iva_trasladado - $iva_acreditable;
                    ?>
                    
                    <div class="row">
                        <div class="col-6">
                            <h6>IVA Trasladado (Ventas)</h6>
                            <h4>$<?= number_format($iva_trasladado, 2) ?></h4>
                        </div>
                        <div class="col-6">
                            <h6>IVA Acreditable (Compras)</h6>
                            <h4>$<?= number_format($iva_acreditable, 2) ?></h4>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center">
                        <h5>IVA a Pagar al SAT</h5>
                        <h3 class="bg-white text-dark p-2 rounded">
                            $<?= number_format(max(0, $iva_a_pagar), 2) ?>
                        </h3>
                        <?php if ($iva_a_pagar < 0): ?>
                            <small class="text-warning">Saldo a favor: $<?= number_format(abs($iva_a_pagar), 2) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
