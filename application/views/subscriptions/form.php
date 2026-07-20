<?php $this->load->view('partial/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo lang('subscriptions_edit_title'); ?> - <?php echo $employee_info->first_name . ' ' . $employee_info->last_name; ?></h3>
                </div>
                <div class="panel-body">
                    <?php echo form_open("subscriptions/save/{$employee_info->person_id}", array('class'=>'form-horizontal', 'id'=>'subscription_form')); ?>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_type'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_dropdown('subscription_type', $subscription_types, $subscription_info ? $subscription_info->subscription_type : 'trial', 'class="form-control"'); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_status'); ?></label>
                        <div class="col-sm-8">
                            <?php 
                                $status_options = array(
                                    'active' => lang('subscriptions_status_active'),
                                    'trial' => lang('subscriptions_status_trial'),
                                    'cancelled' => lang('subscriptions_status_cancelled'),
                                    'failed' => lang('subscriptions_status_failed'),
                                    'expired' => lang('subscriptions_status_expired')
                                );
                                echo form_dropdown('status', $status_options, $subscription_info ? $subscription_info->status : 'trial', 'class="form-control"'); 
                            ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_start_date'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_input(array(
                                'name'=>'start_date',
                                'id'=>'start_date',
                                'value'=>$subscription_info ? date('Y-m-d', strtotime($subscription_info->start_date)) : date('Y-m-d'),
                                'class'=>'form-control',
                                'type'=>'date'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_end_date'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_input(array(
                                'name'=>'end_date',
                                'id'=>'end_date',
                                'value'=>$subscription_info && $subscription_info->end_date ? date('Y-m-d', strtotime($subscription_info->end_date)) : '',
                                'class'=>'form-control',
                                'type'=>'date'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_trial_end_date'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_input(array(
                                'name'=>'trial_end_date',
                                'id'=>'trial_end_date',
                                'value'=>$subscription_info && $subscription_info->trial_end_date ? date('Y-m-d', strtotime($subscription_info->trial_end_date)) : '',
                                'class'=>'form-control',
                                'type'=>'date'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_amount'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_input(array(
                                'name'=>'amount',
                                'id'=>'amount',
                                'value'=>$subscription_info ? $subscription_info->amount : '',
                                'class'=>'form-control',
                                'type'=>'number',
                                'step'=>'0.01'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_currency'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_input(array(
                                'name'=>'currency',
                                'id'=>'currency',
                                'value'=>$subscription_info ? $subscription_info->currency : 'USD',
                                'class'=>'form-control'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_auto_renew'); ?></label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline">
                                <?php echo form_checkbox('auto_renew', '1', $subscription_info && $subscription_info->auto_renew); ?>
                                <?php echo lang('common_yes'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-sm-4 control-label"><?php echo lang('subscriptions_notes'); ?></label>
                        <div class="col-sm-8">
                            <?php echo form_textarea(array(
                                'name'=>'notes',
                                'id'=>'notes',
                                'value'=>$subscription_info ? $subscription_info->notes : '',
                                'class'=>'form-control',
                                'rows'=>'4'
                            )); ?>
                        </div>
                    </div>
                    
                    <div class="form-actions text-center">
                        <?php 
                            echo anchor('subscriptions', '<span class="glyphicon glyphicon-arrow-left"></span> '.lang('common_back'), array('class'=>'btn btn-default'));
                            echo ' ';
                            echo form_submit('submit', lang('common_save'), 'class="btn btn-primary"');
                        ?>
                    </div>
                    
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#subscription_form').validate({
        submitHandler: function(form) {
            var formData = $(form).serialize();
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location = '<?php echo site_url("subscriptions"); ?>';
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('<?php echo lang("common_error"); ?>');
                }
            });
            return false;
        }
    });
});
</script>

<?php $this->load->view('partial/footer'); ?>
