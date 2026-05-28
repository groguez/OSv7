<?php $this->load->view("common/header"); ?>

<div class="row manage-table">
    <div class="col-lg-12">
        <h1 class="page-title"><?php echo $page_title; ?></h1>
        
        <!-- Score de Salud de Inventario -->
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <span class="panel-subtitle">Salud General del Inventario</span>
            </div>
            <div class="panel-body text-center">
                <div class="health-score-circle" style="width: 150px; height: 150px; margin: 20px auto; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold; color: white; background: <?php 
                    echo $health_metrics['health_score'] >= 80 ? '#27ae60' : ($health_metrics['health_score'] >= 60 ? '#f39c12' : ($health_metrics['health_score'] >= 40 ? '#e67e22' : '#e74c3c')); 
                ?>;">
                    <?php echo $health_metrics['health_score']; ?>
                </div>
                <h3><?php echo strtoupper($health_metrics['health_status']); ?></h3>
                
                <div class="row" style="margin-top: 30px;">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-box">
                            <i class="ti-package"></i>
                            <span class="stat-number"><?php echo $health_metrics['total_items']; ?></span>
                            <span class="stat-label">Total Items</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-box warning">
                            <i class="ti-arrow-down"></i>
                            <span class="stat-number"><?php echo $health_metrics['low_stock_items']; ?></span>
                            <span class="stat-label">Stock Bajo</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-box danger">
                            <i class="ti-alert"></i>
                            <span class="stat-number"><?php echo $health_metrics['out_of_stock_items']; ?></span>
                            <span class="stat-label">Sin Stock</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-box info">
                            <i class="ti-time"></i>
                            <span class="stat-number"><?php echo $health_metrics['orphan_items']; ?></span>
                            <span class="stat-label">Sin Movimiento</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas Activas -->
        <?php if (!empty($alerts)): ?>
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <span class="panel-subtitle">Alertas y Notificaciones</span>
            </div>
            <div class="panel-body">
                <?php foreach ($alerts as $alert): ?>
                <div class="alert alert-<?php echo $alert['type'] == 'critical' ? 'danger' : $alert['type']; ?>" role="alert">
                    <i class="<?php echo $alert['icon']; ?>"></i>
                    <strong><?php echo $alert['title']; ?>:</strong> <?php echo $alert['message']; ?>
                    <a href="<?php echo $alert['action_url']; ?>" class="btn btn-sm btn-primary pull-right">Ver Detalles</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reabastecimiento Urgente -->
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <h3><i class="ti-shopping-cart"></i> Reabastecimiento Urgente</h3>
                <span class="panel-subtitle">Productos que necesitan pedido inmediato (basado en ventas recientes)</span>
            </div>
            <div class="panel-body nopadding table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Stock Actual</th>
                            <th>Nivel Mínimo</th>
                            <th>Ventas/Día</th>
                            <th>Días para Agotar</th>
                            <th>Cantidad Sugerida</th>
                            <th>Valor Estimado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reorder_items)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-success">
                                <i class="ti-check"></i> ¡Excelente! No hay productos que requieran reabastecimiento urgente.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($reorder_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $item['name']; ?></strong><br>
                                    <small><?php echo $item['item_number']; ?></small>
                                </td>
                                <td><?php echo $item['supplier_name'] ?? 'Sin Proveedor'; ?></td>
                                <td>
                                    <span class="badge <?php echo $item['current_quantity'] == 0 ? 'badge-danger' : 'badge-warning'; ?>">
                                        <?php echo to_quantity($item['current_quantity']); ?>
                                    </span>
                                </td>
                                <td><?php echo to_quantity($item['reorder_level']); ?></td>
                                <td><?php echo number_format($item['avg_daily_sales'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $item['days_until_stockout'] <= 7 ? 'text-danger' : 'text-warning'; ?>">
                                        <?php echo $item['days_until_stockout'] >= 999 ? 'N/A' : $item['days_until_stockout'] . ' días'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo to_quantity($item['suggested_order_quantity']); ?></strong>
                                </td>
                                <td><?php echo to_currency($item['estimated_order_value']); ?></td>
                                <td>
                                    <button class="btn btn-xs btn-primary" onclick="window.location='<?php echo site_url('receivings/initialize_supplier/' . $item['supplier_id']); ?>'">
                                        <i class="ti-plus"></i> Pedir
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($reorder_items)): ?>
            <div class="panel-footer">
                <a href="<?php echo site_url('ai_lite/export_reorder_report'); ?>" class="btn btn-info">
                    <i class="ti-download"></i> Exportar Reporte Completo
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Productos Trending -->
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <h3><i class="ti-arrow-up"></i> Productos Estrella (Trending)</h3>
                <span class="panel-subtitle">Productos con mayor crecimiento en ventas (últimos 30 días vs período anterior)</span>
            </div>
            <div class="panel-body nopadding table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Ventas Período Anterior</th>
                            <th>Ventas Período Actual</th>
                            <th>Crecimiento</th>
                            <th>Precio Venta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trending_products)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No hay datos suficientes para calcular tendencias
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($trending_products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $product['name']; ?></strong><br>
                                    <small><?php echo $product['item_number']; ?></small>
                                </td>
                                <td><?php echo $product['category_name'] ?? 'Sin Categoría'; ?></td>
                                <td><?php echo to_quantity($product['previous_period_sales']); ?></td>
                                <td><?php echo to_quantity($product['current_period_sales']); ?></td>
                                <td>
                                    <span class="badge badge-success" style="font-size: 14px;">
                                        +<?php echo round($product['growth_percentage']); ?>%
                                    </span>
                                </td>
                                <td><?php echo to_currency($product['unit_price']); ?></td>
                                <td>
                                    <button class="btn btn-xs btn-info" onclick="showPredictionModal(<?php echo $product['item_id']; ?>)">
                                        <i class="ti-bar-chart"></i> Predecir
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Productos Huérfanos -->
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <h3><i class="ti-flag"></i> Productos Sin Movimiento</h3>
                <span class="panel-subtitle">Productos sin ventas en los últimos 60+ días (capital invertido inactivo)</span>
            </div>
            <div class="panel-body nopadding table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Capital Invertido</th>
                            <th>Última Venta</th>
                            <th>Días Sin Vender</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orphan_products)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-success">
                                <i class="ti-check"></i> ¡Bien! Todos los productos tienen movimiento reciente.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($orphan_products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $product['name']; ?></strong><br>
                                    <small><?php echo $product['item_number']; ?></small>
                                </td>
                                <td><?php echo $product['category_name'] ?? 'Sin Categoría'; ?></td>
                                <td><?php echo to_quantity($product['current_quantity']); ?></td>
                                <td><?php echo to_currency($product['invested_capital']); ?></td>
                                <td><?php echo $product['last_sale_date'] ? date(get_date_format(), strtotime($product['last_sale_date'])) : 'Nunca'; ?></td>
                                <td>
                                    <span class="badge badge-warning">
                                        <?php echo $product['days_since_last_sale']; ?> días
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-warning" title="Sugerir promoción o descuento">
                                        <i class="ti-tag"></i> Promocionar
                                    </button>
                                    <button class="btn btn-xs btn-danger" title="Considerar liquidación">
                                        <i class="ti-trash"></i> Liquidar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sugerencias por Proveedor -->
        <?php if (!empty($purchase_suggestions)): ?>
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <h3><i class="ti-truck"></i> Órdenes de Compra Sugeridas por Proveedor</h3>
                <span class="panel-subtitle">Consolidado inteligente de productos a pedir agrupados por proveedor</span>
            </div>
            <div class="panel-body">
                <div class="row">
                    <?php foreach ($purchase_suggestions as $suggestion): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card supplier-card">
                            <div class="card-header">
                                <h4><?php echo $suggestion['supplier_name']; ?></h4>
                            </div>
                            <div class="card-body">
                                <p><strong>Items:</strong> <?php echo $suggestion['total_items']; ?></p>
                                <p><strong>Cantidad Total:</strong> <?php echo to_quantity($suggestion['total_quantity']); ?></p>
                                <p><strong>Valor Estimado:</strong> <span class="text-success"><?php echo to_currency($suggestion['total_value']); ?></span></p>
                                <hr>
                                <ul class="list-unstyled" style="max-height: 150px; overflow-y: auto;">
                                    <?php foreach (array_slice($suggestion['items'], 0, 5) as $item): ?>
                                    <li>
                                        <small>• <?php echo $item['name']; ?> (<?php echo to_quantity($item['suggested_order_quantity']); ?>)</small>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if (count($suggestion['items']) > 5): ?>
                                    <li class="text-muted"><small>... y <?php echo count($suggestion['items']) - 5; ?> más</small></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary btn-block" onclick="window.location='<?php echo site_url('receivings/initialize_supplier/' . $suggestion['supplier_id']); ?>'">
                                    <i class="ti-plus"></i> Crear Orden de Compra
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Modal de Predicción -->
<div id="predictionModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Predicción de Ventas</h4>
            </div>
            <div id="predictionModalContent" class="modal-body">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
function showPredictionModal(itemId) {
    $('#predictionModal').modal('show');
    $('#predictionModalContent').html('<div class="text-center"><i class="ti-loading spinner"></i> Cargando predicción...</div>');
    
    $.ajax({
        url: '<?php echo site_url('ai_lite/predict_item_sales/'); ?>' + itemId,
        success: function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                var p = data.prediction;
                var trendIcon = p.trend === 'increasing' ? 'ti-arrow-up text-success' : (p.trend === 'decreasing' ? 'ti-arrow-down text-danger' : 'ti-minus text-muted');
                var trendText = p.trend === 'increasing' ? 'Creciente' : (p.trend === 'decreasing' ? 'Decreciente' : 'Estable');
                
                $('#predictionModalContent').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Métricas de Predicción</h4>
                            <table class="table">
                                <tr><td>Tendencia:</td><td><i class="${trendIcon}"></i> ${trendText}</td></tr>
                                <tr><td>Venta Diaria Promedio:</td><td>${p.avg_daily_sales} unidades</td></tr>
                                <tr><td>Promedio Reciente (30 días):</td><td>${p.recent_avg} unidades/día</td></tr>
                                <tr><td>Promedio Anterior:</td><td>${p.older_avg} unidades/día</td></tr>
                                <tr><td>Confianza:</td><td>${p.confidence}%</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>Proyección a 30 Días</h4>
                            <div class="alert alert-info">
                                <h2><i class="ti-package"></i> ${p.predicted_quantity} unidades</h2>
                                <p>Se pronostica la venta de esta cantidad en los próximos 30 días</p>
                            </div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-info" style="width: ${p.confidence}%">
                                    ${p.confidence}% Confianza
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                $('#predictionModalContent').html('<div class="alert alert-danger">Error al cargar predicción</div>');
            }
        }
    });
}
</script>

<style>
.health-score-circle {
    transition: all 0.3s ease;
}

.stat-box {
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
    margin-bottom: 15px;
    text-align: center;
}

.stat-box i {
    font-size: 32px;
    display: block;
    margin-bottom: 10px;
}

.stat-box .stat-number {
    font-size: 28px;
    font-weight: bold;
    display: block;
}

.stat-box .stat-label {
    font-size: 14px;
    color: #666;
}

.stat-box.warning { background: #fff3cd; color: #856404; }
.stat-box.danger { background: #f8d7da; color: #721c24; }
.stat-box.info { background: #d1ecf1; color: #0c5460; }

.supplier-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.supplier-card .card-header {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    border-radius: 8px 8px 0 0;
}

.supplier-card .card-body {
    padding: 15px;
}

.supplier-card .card-footer {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: #fff;
    border-radius: 0 0 8px 8px;
}

.badge-success {
    background-color: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
    padding: 5px 10px;
    border-radius: 4px;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
}
</style>

<?php $this->load->view("common/footer"); ?>
