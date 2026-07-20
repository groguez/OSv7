<?php $this->load->view('partial/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo lang('subscriptions_manage_title'); ?></h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo lang('common_employee'); ?></th>
                                    <th><?php echo lang('subscriptions_type'); ?></th>
                                    <th><?php echo lang('subscriptions_status'); ?></th>
                                    <th><?php echo lang('subscriptions_start_date'); ?></th>
                                    <th><?php echo lang('subscriptions_end_date'); ?></th>
                                    <th><?php echo lang('common_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($employees->result_array() as $employee): ?>
                                <?php 
                                    $sub_info = $this->Employee_subscription->get_subscription_info($employee['person_id']);
                                    $status_class = '';
                                    if ($sub_info) {
                                        switch($sub_info->status) {
                                            case 'active': $status_class = 'label-success'; break;
                                            case 'trial': $status_class = 'label-info'; break;
                                            case 'cancelled': $status_class = 'label-warning'; break;
                                            case 'failed': $status_class = 'label-danger'; break;
                                            default: $status_class = 'label-default';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                    <td>
                                        <?php if ($sub_info): ?>
                                            <span class="label label-primary"><?php echo $this->Employee_subscription->get_status_label($sub_info->subscription_type); ?></span>
                                        <?php else: ?>
                                            <span class="label label-default"><?php echo lang('subscriptions_no_subscription'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sub_info): ?>
                                            <span class="label <?php echo $status_class; ?>"><?php echo $this->Employee_subscription->get_status_label($sub_info->status); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $sub_info ? date(get_date_format(), strtotime($sub_info->start_date)) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo $sub_info && $sub_info->end_date ? date(get_date_format(), strtotime($sub_info->end_date)) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo anchor("subscriptions/view/{$employee['person_id']}", '<span class="glyphicon glyphicon-edit"></span>', array('class'=>'btn btn-sm btn-primary', 'title'=>lang('common_edit'))); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($pagination): ?>
                        <div class="text-center">
                            <?php echo $pagination; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partial/footer'); ?>
