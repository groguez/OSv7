<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inteligencia de Compras e Inventarios</title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/variables.css">
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 20px; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; }
        .card h3 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .alert-critical { border-left: 4px solid #dc3545; }
        .alert-warning { border-left: 4px solid #ffc107; }
        .alert-success { border-left: 4px solid #28a745; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .score-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: white; }
        .score-high { background-color: #28a745; }
        .score-medium { background-color: #ffc107; color: #333; }
        .score-low { background-color: #dc3545; }
    </style>
</head>
<body>
<div class="dashboard-grid">
    
    <!-- Stock Crítico -->
    <div class="card alert-critical">
        <h3>📦 Stock Crítico</h3>
        <?php if (empty($low_stock_items)): ?>
            <p>Todo el inventario está por encima del punto de reorden.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Producto</th><th>SKU</th><th>Actual</th><th>Mínimo</th></tr></thead>
                <tbody>
                <?php foreach ($low_stock_items as $item): ?>
                    <tr>
                        <td><?php echo $item->name; ?></td>
                        <td><?php echo $item->item_number; ?></td>
                        <td style="color: red; font-weight: bold;"><?php echo $item->quantity; ?></td>
                        <td><?php echo $item->reorder_level; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Productos por Vencer -->
    <div class="card alert-warning">
        <h3>⏰ Próximos a Vencer (30 días)</h3>
        <?php if (empty($expiring_items)): ?>
            <p>No hay productos próximos a vencer.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Cant.</th></tr></thead>
                <tbody>
                <?php foreach ($expiring_items as $exp): ?>
                    <tr>
                        <td><?php echo $exp->name; ?></td>
                        <td><?php echo $exp->lot_number ?? 'N/A'; ?></td>
                        <td style="color: orange; font-weight: bold;"><?php echo $exp->expiration_date; ?></td>
                        <td><?php echo $exp->total_qty; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Ranking de Proveedores -->
    <div class="card alert-success">
        <h3>🏆 Top Proveedores del Mes</h3>
        <?php if (empty($supplier_ranking)): ?>
            <p>Sin datos suficientes para calcular scores.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Proveedor</th><th>Score</th><th>Puntualidad</th><th>Calidad</th></tr></thead>
                <tbody>
                <?php foreach ($supplier_ranking as $rank): 
                    $badge_class = $rank->score_total >= 80 ? 'score-high' : ($rank->score_total >= 60 ? 'score-medium' : 'score-low');
                ?>
                    <tr>
                        <td><?php echo $rank->supplier_name; ?></td>
                        <td><span class="score-badge <?php echo $badge_class; ?>"><?php echo $rank->score_total; ?></span></td>
                        <td><?php echo $rank->on_time_delivery_rate; ?>%</td>
                        <td><?php echo (100 - $rank->quality_rejection_rate); ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button onclick="calculateScores()" style="margin-top: 15px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Recalcular Scores</button>
        <?php endif; ?>
    </div>

</div>

<script>
function calculateScores() {
    fetch('<?php echo site_url("purchasing_intelligence/calculate_supplier_scores"); ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Scores actualizados correctamente');
                location.reload();
            }
        });
}
</script>
</body>
</html>
