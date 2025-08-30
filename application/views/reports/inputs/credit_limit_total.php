<div class="form-group">
	<?php echo form_label(lang('common_credit_limit').':', 'total_spent_condition_credit_limit', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  ')); ?> 
	<div class="col-sm-9 col-md-2 col-lg-2">							
		<?php echo form_dropdown('total_spent_condition_credit_limit',array('any' => lang('reports_any_amount'), 'greater_than' => lang('reports_sales_generator_selectCondition_7'), 'less_than' => lang('reports_sales_generator_selectCondition_8'), 'equal_to' => lang('reports_sales_generator_selectCondition_9')), $this->input->get('total_spent_condition_credit_limit'), 'id="total_spent_condition_credit_limit" class="form-control"'); ?>
	</div>
</div>

<div class="form-group" style="display: none;" id="credit_limit_container">
		<?php echo form_label(lang('common_amount').':', 'credit_limit', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  ')); ?> 
		<div class="col-sm-9 col-md-2 col-lg-2">							
			<input type="text" class="form-control credit_limit" name="credit_limit" id="credit_limit" value="<?php echo H($this->input->get('credit_limit'));?>">
		</div>
</div>					

<script>
	$(document).ready(function()
	{
		$("#total_spent_condition_credit_limit").change(function()
		{
			if ($(this).val() != 'any')
			{
				$("#credit_limit_container").show();
			}
			else
			{
				$("#credit_limit_container").hide();				
			}
		});
		
		if ($('#total_spent_condition_credit_limit').val() != 'any')
		{
			$("#credit_limit_container").show();
		}
		
	});
</script>