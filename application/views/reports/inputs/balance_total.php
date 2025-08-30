<div class="form-group">
	<?php echo form_label(lang('common_balance').':', 'total_spent_condition_balance', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  ')); ?> 
	<div class="col-sm-9 col-md-2 col-lg-2">							
		<?php echo form_dropdown('total_spent_condition_balance',array('any' => lang('reports_any_amount'), 'greater_than' => lang('reports_sales_generator_selectCondition_7'), 'less_than' => lang('reports_sales_generator_selectCondition_8'), 'equal_to' => lang('reports_sales_generator_selectCondition_9')), $this->input->get('total_spent_condition_balance'), 'id="total_spent_condition_balance" class="form-control"'); ?>
	</div>
</div>

<div class="form-group" style="display: none;" id="balance_container">
		<?php echo form_label(lang('common_amount').':', 'balance', array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label  ')); ?> 
		<div class="col-sm-9 col-md-2 col-lg-2">							
			<input type="text" class="form-control balance" name="balance" id="balance" value="<?php echo H($this->input->get('balance'));?>">
		</div>
</div>					

<script>
	$(document).ready(function()
	{
		$("#total_spent_condition_balance").change(function()
		{
			if ($(this).val() != 'any')
			{
				$("#balance_container").show();
			}
			else
			{
				$("#balance_container").hide();				
			}
		});
		
		if ($('#total_spent_condition_balance').val() != 'any')
		{
			$("#balance_container").show();
		}
		
	});
</script>