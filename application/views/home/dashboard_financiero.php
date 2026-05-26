<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financiero - PHP Point of Sale</title>
    <?php $this->load->view('partial/header'); ?>
    <style>
        .dashboard-container {
            padding: 20px;
            background: var(--bg-body, #f4f6f9);
        }
        
        .dashboard-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .dashboard-tab {
            padding: 12px 24px;
            border: none;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .dashboard-tab.active {
            background: var(--color-primary, #489ee7);
            color: white;
        }
        
        .dashboard-tab:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--color-primary, #489ee7);
        }
        
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .kpi-card.success { border-left-color: var(--color-success, #6fd64b); }
        .kpi-card.danger { border-left-color: var(--color-danger, #fb5d5d); }
        .kpi-card.warning { border-left-color: var(--color-warning, #f7941d); }
        .kpi-card.info { border-left-color: var(--color-info, #9244CC); }
        
        .kpi-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .kpi-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .trend-up { color: var(--color-success, #6fd64b); }
        .trend-down { color: var(--color-danger, #fb5d5d); }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .okr-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .okr-item {
            padding: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .okr-progress {
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
        }
        
        .okr-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--color-primary, #489ee7), var(--color-primary-dark, #357abd));
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert-widget {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-warning {
            background: #fff8e1;
            border-left: 4px solid var(--color-warning, #f7941d);
        }
        
        .alert-info {
            background: #e3f2fd;
            border-left: 4px solid var(--color-info, #9244CC);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Dashboard Ejecutivo</h1>
            <div class="date-range">
                <button class="btn btn-primary" onclick="exportReport()">
                    <i class="fa fa-download"></i> Exportar Reporte
                </button>
            </div>
        </div>
        
        <!-- Pestañas de Navegación -->
        <div class="dashboard-tabs">
            <button class="dashboard-tab active" onclick="switchTab('financial')">
                <i class="fa fa-dollar-sign"></i> Financiero
            </button>
            <button class="dashboard-tab" onclick="switchTab('operations')">
                <i class="fa fa-cogs"></i> Operaciones
            </button>
            <button class="dashboard-tab" onclick="switchTab('sales')">
                <i class="fa fa-chart-line"></i> Ventas
            </button>
            <button class="dashboard-tab" onclick="switchTab('executive')">
                <i class="fa fa-briefcase"></i> Estratégico
            </button>
        </div>
        
        <!-- Contenido: Financiero -->
        <div id="financial" class="tab-content active">
            <div class="kpi-grid">
                <div class="kpi-card success">
                    <div class="kpi-title">Ingresos Totales</div>
                    <div class="kpi-value">$<?= number_format($financial_kpis['total_revenue'], 2) ?></div>
                    <div class="kpi-trend trend-up">
                        <i class="fa fa-arrow-up"></i>
                        <span><?= $financial_kpis['revenue_growth'] ?>% vs mes anterior</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-title">Flujo de Caja</div>
                    <div class="kpi-value">$<?= number_format($financial_kpis['cash_flow'], 2) ?></div>
                    <div class="kpi-trend <?= $financial_kpis['cash_flow_trend'] >= 0 ? 'trend-up' : 'trend-down' ?>">
                        <i class="fa fa-<?= $financial_kpis['cash_flow_trend'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <span><?= abs($financial_kpis['cash_flow_trend']) ?>% cambio</span>
                    </div>
                </div>
                
                <div class="kpi-card warning">
                    <div class="kpi-title">Cuentas por Cobrar</div>
                    <div class="kpi-value">$<?= number_format($financial_kpis['accounts_receivable'], 2) ?></div>
                    <div class="kpi-trend">
                        <i class="fa fa-clock"></i>
                        <span><?= $financial_kpis['avg_collection_days'] ?> días promedio</span>
                    </div>
                </div>
                
                <div class="kpi-card danger">
                    <div class="kpi-title">Cuentas por Pagar</div>
                    <div class="kpi-value">$<?= number_format($financial_kpis['accounts_payable'], 2) ?></div>
                    <div class="kpi-trend">
                        <i class="fa fa-calendar"></i>
                        <span>Vencimiento: <?= $financial_kpis['next_payment_due'] ?></span>
                    </div>
                </div>
            </div>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Proyección vs Realidad</h3>
                    </div>
                    <canvas id="projectionChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Distribución de Ingresos</h3>
                    </div>
                    <canvas id="revenueDistributionChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Flujo de Caja por Entidad Financiera</h3>
                </div>
                <canvas id="cashFlowByEntityChart"></canvas>
            </div>
        </div>
        
        <!-- Contenido: Operaciones -->
        <div id="operations" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card info">
                    <div class="kpi-title">Proyectos Activos</div>
                    <div class="kpi-value"><?= $operations_kpis['active_projects'] ?></div>
                    <div class="kpi-trend">
                        <span><?= $operations_kpis['on_time_percentage'] ?>% a tiempo</span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-title">Carga de Trabajo</div>
                    <div class="kpi-value"><?= $operations_kpis['team_utilization'] ?>%</div>
                    <div class="kpi-trend">
                        <span>Óptimo: 75-85%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenido: Ventas -->
        <div id="sales" class="tab-content">
            <div class="kpi-grid">
                <div class="kpi-card success">
                    <div class="kpi-title">Ventas Totales</div>
                    <div class="kpi-value">$<?= number_format($sales_kpis['total_sales'], 2) ?></div>
                    <div class="kpi-trend trend-up">
                        <i class="fa fa-arrow-up"></i>
                        <span><?= $sales_kpis['sales_growth'] ?>% vs periodo anterior</span>
                    </div>
                </div>
                
                <div class="kpi-card info">
                    <div class="kpi-title">Tasa de Conversión</div>
                    <div class="kpi-value"><?= $sales_kpis['conversion_rate'] ?>%</div>
                    <div class="kpi-trend">
                        <span>Objetivo: <?= $sales_kpis['conversion_target'] ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Embudo de Ventas</h3>
                    </div>
                    <canvas id="salesFunnelChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Ventas por Producto</h3>
                    </div>
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Contenido: Estratégico -->
        <div id="executive" class="tab-content">
            <div class="okr-section">
                <h3 style="margin-bottom: 20px;">OKRs - Objetivos y Resultados Clave</h3>
                
                <?php foreach ($okrs as $okr): ?>
                <div class="okr-item">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <strong><?= $okr['objective'] ?></strong>
                        <span><?= $okr['progress'] ?>%</span>
                    </div>
                    <p style="color: #6c757d; font-size: 14px;"><?= $okr['description'] ?></p>
                    <div class="okr-progress">
                        <div class="okr-progress-bar" style="width: <?= $okr['progress'] ?>%"></div>
                    </div>
                    <div style="margin-top: 12px; font-size: 13px; color: #6c757d;">
                        <small>Resultado Clave: <?= $okr['key_result'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="alert-widget alert-warning">
                <i class="fa fa-exclamation-triangle" style="color: var(--color-warning, #f7941d); font-size: 20px;"></i>
                <div>
                    <strong>Alerta de Mercado:</strong>
                    <p style="margin: 4px 0 0 0; font-size: 14px;">La cuota de mercado ha disminuido un 2.3% este trimestre. Se recomienda revisar estrategia de precios.</p>
                </div>
            </div>
            
            <div class="alert-widget alert-info">
                <i class="fa fa-lightbulb" style="color: var(--color-info, #9244CC); font-size: 20px;"></i>
                <div>
                    <strong>Recomendación Smart Insights:</strong>
                    <p style="margin: 4px 0 0 0; font-size: 14px;">Basado en el análisis predictivo, se sugiere aumentar inventario de productos categoría A en un 15% para el próximo mes.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="<?= base_url('assets/js/chart.min.js') ?>"></script>
    <script>
        // Configuración global de Chart.js
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#6c757d';
        
        // Gráfico de Proyección vs Realidad
        const projectionCtx = document.getElementById('projectionChart').getContext('2d');
        new Chart(projectionCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($projection_data['labels']) ?>,
                datasets: [{
                    label: 'Proyección (Forecast)',
                    data: <?= json_encode($projection_data['forecast']) ?>,
                    borderColor: '#489ee7',
                    backgroundColor: 'rgba(72, 158, 231, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Realidad (Actual)',
                    data: <?= json_encode($projection_data['actual']) ?>,
                    borderColor: '#6fd64b',
                    backgroundColor: 'rgba(111, 214, 75, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Proforma',
                    data: <?= json_encode($projection_data['proforma']) ?>,
                    borderColor: '#f7941d',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Cambio de pestañas
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.dashboard-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Exportar reporte
        function exportReport() {
            window.location.href = '<?= site_url('home/export_dashboard') ?>';
        }
    </script>
</body>
</html>
