<?php $this->load->view("partial/header"); ?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-piluku">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="ion-edit"></i>
					<?php echo lang("common_giftcards_basic_information"); ?>
					<small>(<?php echo lang('common_fields_required_message'); ?>)</small>
				</h3>
			</div>

			<div class="panel-body">
				<?php echo form_open('giftcards/check_giftcard_exist', array('id' => 'giftcard_form', 'class' => 'form-horizontal')); ?>

				<div class="control-group">
					<?php echo form_label(lang('common_giftcards_giftcard_number') . ':', 'giftcard_number', array('class' => 'col-sm-3 col-md-3 col-lg-2 control-label wide required')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(
							array(
								'name' => 'giftcard_number',
								'size' => '8',
								'id' => 'giftcard_number',
								'class' => 'form-control',
								'required' => true,
								'value' => $giftcard_data ? $giftcard_data->giftcard_number : ''
							)
						); ?>
					</div>
					<div style="clear:both;"></div>
				</div>
				<div class="control-group">
					<?php echo form_label('PIN:', 'giftcard_pin', array('class' => 'col-sm-3 col-md-3 col-lg-2 control-label wide required')); ?>
					<div class="col-sm-9 col-md-9 col-lg-10">
						<?php echo form_input(
							array(
								'type' => 'password',
								'name' => 'giftcard_pin',
								'size' => '8',
								'class' => 'form-control',
								'id' => 'giftcard_pin',
								'required' => true
							)
						); ?>
					</div>
					<div style="clear:both;"></div>
				</div>
				<div class="form-actions pull-right" style="margin-top: 20px;">
					<?php
					echo form_submit(
						array(
							'name' => 'submit',
							'id' => 'submit',
							'value' => lang('common_view'),
							'class' => 'btn btn-primary'
						)
					);
					?>
				</div>
				<div class="clear"></div>
				<?php
				echo form_close();
				?>
			</div>

			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						<?php
						if($giftcard_data){
						?>
						<div class="alert alert-info">
							<?php echo lang('sales_giftcard_balance'); ?>: <strong><?php echo to_currency($giftcard_data ? $giftcard_data->value : 0); ?></strong><br />
						</div>
						<?php
						}
						if($giftcard_last_log){
							$logs1 = '<ul class="list-group">';
							if($giftcard_last_log->transaction_amount <=0)
							{
								$logs1.= '<li class="list-group-item list-group-item-danger">'.date(get_date_format(). ' '.get_time_format(), strtotime($giftcard_last_log->log_date)).' '.$giftcard_last_log->log_message.'</li>';
							}
							else
							{
								$logs1.= '<li class="list-group-item list-group-item-success">'.date(get_date_format(). ' '.get_time_format(), strtotime($giftcard_last_log->log_date)).' '.$giftcard_last_log->log_message."</li>";
							}
							$logs1.= '</ul>';

							echo $logs1;
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>
</div>

<script type='text/javascript'>
	//validation and submit handling
	$(document).ready(function() {
		<?php if (!$this->config->item('disable_giftcard_detection')) { ?>
			giftcard_swipe_field($('#giftcard_number'));
		<?php
		}
		?>
		setTimeout(function() {
			$(":input:visible:first", "#giftcard_form").focus();
		}, 100);
		$('#giftcard_form').validate({
			submitHandler: function(form) {
				var ajaxSubmitData = {
					success: function(response) {
						if (response.success) {
							show_feedback('success', response.message, <?php echo json_encode(lang('common_success')); ?>);
							window.location.href = "<?php echo site_url('sales/check_giftcard/'); ?>" + response.item_id;
						} else {
							show_feedback('error', response.message, <?php echo json_encode(lang('common_error')); ?>);
						}
					},
					dataType: 'json'
				};
				$(form).ajaxSubmit(ajaxSubmitData);
			},
			errorClass: "text-danger",
			errorElement: "span",
			highlight: function(element, errorClass, validClass) {
				$(element).parents('.form-group').removeClass('has-success').addClass('has-error');
			},
			unhighlight: function(element, errorClass, validClass) {
				$(element).parents('.form-group').removeClass('has-error').addClass('has-success');
			},
			rules: {
				giftcard_number: {
					required: true
				},
				giftcard_pin: {
					required: true,
					number: true
				},
			},
			messages: {
				giftcard_number: {
					required: <?php echo json_encode(lang('common_giftcards_number_required')); ?>,
				},
				giftcard_pin: {
					required: <?php echo json_encode(lang('common_giftcards_pin_required')); ?>,
					number: <?php echo json_encode(lang('common_giftcards_pin')); ?>
				},
			}
		});
	});
</script>
<?php $this->load->view("partial/footer"); ?>