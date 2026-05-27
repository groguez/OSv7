<?php $this->load->view("partials/header"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">
                <i class="fa fa-shield"></i> <?php echo lang('super_admin_dashboard'); ?>
                
                <?php if (is_ghost_mode()): ?>
                    <span class="badge badge-danger ml-3">
                        <i class="fa fa-user-secret"></i> Modo Ghost Activo
                    </span>
                    <a href="<?php echo site_url('super_admin/exit_ghost_mode'); ?>" class="btn btn-sm btn-outline-danger ml-2">
                        <i class="fa fa-sign-out-alt"></i> Salir de Ghost
                    </a>
                <?php endif; ?>
            </h1>
            
            <!-- Indicador de Usuario Original en Ghost Mode -->
            <?php if (is_ghost_mode()): ?>
                <?php $original = get_original_user_info(); ?>
                <div class="alert alert-info">
                    <strong>Vista como:</strong> <?php echo $original->first_name . ' ' . $original->last_name; ?> 
                    (<?php echo $original->email; ?>)
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Tarjetas de Estadísticas -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="icon-circle bg-primary">
                        <i class="fa fa-store"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-title"><?php echo lang('total_stores'); ?></div>
                        <div class="stats-value"><?php echo $total_stores; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="icon-circle bg-success">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-title"><?php echo lang('total_employees'); ?></div>
                        <div class="stats-value"><?php echo $total_employees; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="icon-circle bg-info">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-title"><?php echo lang('active_subscriptions'); ?></div>
                        <div class="stats-value"><?php echo $active_subscriptions; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats">
                <div class="card-body">
                    <div class="icon-circle bg-warning">
                        <i class="fa fa-hourglass-half"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-title"><?php echo lang('trial_stores'); ?></div>
                        <div class="stats-value"><?php echo $trial_stores; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Suscripciones Próximas a Vencer -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-exclamation-triangle"></i> <?php echo lang('expiring_soon'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($expiring_soon) > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo lang('store_name'); ?></th>
                                    <th><?php echo lang('subscription_status'); ?></th>
                                    <th><?php echo lang('end_date'); ?></th>
                                    <th><?php echo lang('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiring_soon as $store): ?>
                                    <tr>
                                        <td><?php echo $store->store_name; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $store->subscription_status == 'active' ? 'success' : 'warning'; ?>">
                                                <?php echo $store->subscription_status; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $store->subscription_end_date; ?></td>
                                        <td>
                                            <a href="<?php echo site_url('super_admin/store_subscription_form/'.$store->location_id); ?>" class="btn btn-sm btn-primary">
                                                <?php echo lang('edit'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted"><?php echo lang('no_expiring_soon'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Logs Recientes de Ghost Mode -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-user-secret"></i> <?php echo lang('recent_ghost_activity'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_ghost_logs) > 0): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><?php echo lang('admin'); ?></th>
                                    <th><?php echo lang('target'); ?></th>
                                    <th><?php echo lang('action'); ?></th>
                                    <th><?php echo lang('time'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_ghost_logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log->admin_name; ?></td>
                                        <td><?php echo $log->target_name; ?></td>
                                        <td><?php echo $log->action; ?></td>
                                        <td><?php echo $log->timestamp; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted"><?php echo lang('no_ghost_activity'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Accesos Rápidos -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fa fa-bolt"></i> <?php echo lang('quick_actions'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="<?php echo site_url('super_admin/users'); ?>" class="btn btn-block btn-outline-primary">
                                <i class="fa fa-users"></i> <?php echo lang('manage_users'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo site_url('super_admin/stores'); ?>" class="btn btn-block btn-outline-success">
                                <i class="fa fa-store"></i> <?php echo lang('manage_stores'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo site_url('super_admin/global_config'); ?>" class="btn btn-block btn-outline-info">
                                <i class="fa fa-cog"></i> <?php echo lang('global_config'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo site_url('super_admin/audit_logs'); ?>" class="btn btn-block btn-outline-secondary">
                                <i class="fa fa-history"></i> <?php echo lang('audit_logs'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-stats {
    border-left: 4px solid;
}
.card-stats .icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    float: left;
    margin-right: 15px;
}
.card-stats .stats-content {
    overflow: hidden;
}
.card-stats .stats-title {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
}
.card-stats .stats-value {
    font-size: 28px;
    font-weight: bold;
    color: #343a40;
}
.page-title {
    margin-bottom: 20px;
}
</style>

<?php $this->load->view("partials/footer"); ?>
