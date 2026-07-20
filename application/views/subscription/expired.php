<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Suscripción Vencida - OneBox</title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/bootstrap-3.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css_phppointofsale/base-modern.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .expired-container { background: white; border-radius: 15px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .expired-icon { font-size: 80px; color: #dc3545; margin-bottom: 20px; }
        .expired-title { color: #333; font-size: 28px; margin-bottom: 15px; }
        .expired-message { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .subscription-info { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; text-align: left; }
        .subscription-info h4 { color: #007bff; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #666; font-weight: bold; }
        .info-value { color: #333; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-expired { background: #ffc107; color: #333; }
        .status-trial { background: #17a2b8; color: white; }
        .btn-contact { background: #007bff; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-contact:hover { background: #0056b3; color: white; }
        .btn-renew { background: #28a745; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-renew:hover { background: #218838; color: white; }
    </style>
</head>
<body>
<div class="expired-container">
    <div class="expired-icon">⚠️</div>
    <h1 class="expired-title">Suscripción Vencida</h1>
    
    <p class="expired-message">
        La suscripción de esta tienda ha vencido o está por vencer. 
        Para continuar utilizando OneBox, es necesario renovar su plan de servicio.
    </p>

    <?php if (isset($subscription_info)): ?>
    <div class="subscription-info">
        <h4>📋 Detalles de Suscripción</h4>
        <div class="info-row">
            <span class="info-label">Tienda:</span>
            <span class="info-value"><?php echo isset($subscription_info['store_name']) ? $subscription_info['store_name'] : 'N/A'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Plan:</span>
            <span class="info-value"><?php echo isset($subscription_info['plan']) ? ucfirst($subscription_info['plan']) : 'Básico'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="status-badge status-<?php echo isset($subscription_info['status']) ? $subscription_info['status'] : 'expired'; ?>">
                <?php echo isset($subscription_info['status']) ? ucfirst($subscription_info['status']) : 'Vencido'; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de Vencimiento:</span>
            <span class="info-value"><?php echo isset($subscription_info['end_date']) ? date('d/m/Y', strtotime($subscription_info['end_date'])) : 'N/A'; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Días Restantes:</span>
            <span class="info-value"><?php echo isset($subscription_info['days_remaining']) ? $subscription_info['days_remaining'] : '0'; ?> días</span>
        </div>
    </div>
    <?php endif; ?>

    <div>
        <a href="mailto:soporte@onebox.mx" class="btn-contact">
            📧 Contactar Soporte
        </a>
        <a href="<?php echo site_url('login/logout'); ?>" class="btn-renew">
            🔐 Cerrar Sesión
        </a>
    </div>

    <p style="margin-top: 25px; color: #999; font-size: 13px;">
        Si cree que esto es un error o ya realizó su pago, contacte a soporte técnico inmediatamente.
    </p>
</div>

<script src="<?php echo base_url(); ?>assets/js_phppointofsale/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js_phppointofsale/bootstrap.min.js"></script>
</body>
</html>
