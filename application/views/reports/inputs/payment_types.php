<?php

$payment_types_to_use = Report::get_payment_types_to_use();
$selected_payment_types = Report::get_selected_payment_types();

if (count($payment_types_to_use) > 0) {?>		
<div class="form-group">	
	<?php echo form_label(lang('common_payment_types').':', null,array('class'=>'col-sm-3 col-md-3 col-lg-2 col-sm-3 col-md-3 col-lg-2 control-label')); ?>
		<div class="col-sm-9 col-md-9 col-lg-10">
		<ul id="reports_payment_types_list" class="list-inline">
			<?php
			echo '<li>'.form_checkbox(
				array(
								'id' => 'select_all_payment_type',
								'class' => 'all_checkboxes',
								'name' => 'select_all_payment_type',
								'value' => '1',
							)
				). '<label for="select_all_payment_type"><span></span><strong>'.lang('common_select_all').'</strong></label></li>';
			foreach($payment_types_to_use as $payment_type) 
			{
				$checkbox_options = array(
				'id' => 'reports_selected_payment_types'.$payment_type,
				'class' => 'reports_selected_payment_types_checkboxes',
				'name' => 'payment_types[]',
				'value' => $payment_type,
				'checked' => in_array($payment_type, $selected_payment_types),
			);
																
				echo '<li>'.form_checkbox($checkbox_options). '<label for="reports_selected_payment_types'.$payment_type.'"><span></span>'.$payment_type.'</label></li>';
			}
		?>
		</ul>
	</div>
	
</div>
<script>

$(document).on('click', '#select_all_payment_type', function() {
	$(".reports_selected_payment_types_checkboxes").prop('checked', $("#select_all_payment_type").prop('checked'));
});

$(document).on('click', '.reports_selected_payment_types_checkboxes', function() {
	check_boxes_payment_type();
});
check_boxes_payment_type();
function check_boxes_payment_type()
{
	var total_checkboxes = $(".reports_selected_payment_types_checkboxes").length;
	var checked_boxes = 0;
	$(".reports_selected_payment_types_checkboxes").each(function( index ) {
		if ($(this).prop('checked'))
		{
			checked_boxes++;
		}
	});

	if (checked_boxes == total_checkboxes)
	{
		$("#select_all_payment_type").prop('checked', true);
	}
	else
	{
		$("#select_all_payment_type").prop('checked', false);
	}
}

</script>
<?php } ?>