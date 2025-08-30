<?php $this->load->view("partial/header");

	$qr_code_type = $this->input->get('qr_code_type') ? $this->input->get('qr_code_type') : ($this->config->item('qr_code_type') ? $this->config->item('qr_code_type') : 'qr');
	$qr_code_overall_width = $this->input->get('qr_code_overall_width') ? $this->input->get('qr_code_overall_width') : ($this->config->item('qr_code_overall_width') ? $this->config->item('qr_code_overall_width') : 2);
	$qr_code_overall_height = $this->input->get('qr_code_overall_height') ? $this->input->get('qr_code_overall_height') : ($this->config->item('qr_code_overall_height') ? $this->config->item('qr_code_overall_height') : 2);
	$qr_code_width 		= $this->input->get('qr_code_width') ? $this->input->get('qr_code_width') : ($this->config->item('qr_code_width') ? $this->config->item('qr_code_width') : 1);
	$qr_code_overall_font_size 	= $this->input->get('qr_code_overall_font_size') ? $this->input->get('qr_code_overall_font_size') : ($this->config->item('qr_code_overall_font_size') ? $this->config->item('qr_code_overall_font_size') : 10);
	$qr_code_scale = $this->input->get('qr_code_scale') ? $this->input->get('qr_code_scale') : ($this->config->item('qr_code_scale') ? $this->config->item('qr_code_scale') : 2);
?>
<style>
	
@page {
	margin: 0 !important;
	padding: 0 !important;
}
	
@media print
{
	.wrapper {
  	 overflow: visible;
	 font-family: serif !important;
	}
}

.qr-code-label
{
	-webkit-box-sizing: content-box;
	-moz-box-sizing: content-box;
	box-sizing: content-box;
	width: <?php echo $qr_code_overall_width; ?>in;
	height:<?php echo $qr_code_overall_height; ?>in;
	letter-spacing: normal;
	word-wrap: break-word;
	overflow: hidden;
	margin:0 auto;
	text-align:center;
	padding: 10px;
	font-size: <?php echo $qr_code_overall_font_size;?>pt;
	line-height: .9em;
	font-family: serif !important;
	/* background:green; */
}
.qr-code-label img{
	max-width: 100%;
	max-height: 100%;
}

.item-price-qr-code
{
	font-size: 115%;
}

</style>
<div class="hidden-print" style="text-align: center;margin-top: 20px;">
	<?php
	$labels_saved = $this->Appconfig->get_qr_coded_labels()->result_array();
	?>
	<select id="saved_qr_coded_labels">
		<option value="">--<?php echo H(lang('common_load_saved_value'));?>--</option>
		<?php
		foreach($labels_saved as $label_saved)
		{
			$label_settings = unserialize($label_saved['value']);
			echo "<option value='".H(json_encode($label_settings))."'>".$label_settings['qr_code_saved_name']."</option>";
		}
		?>
	</select>
	<form method="get" action="<?php echo site_url('home/save_qr_code_settings'); ?>" id="qr_code_form">
		<div class="row">
			<div class="col-md-12">
				<div class="panel-body">
					<div class="form-group">
						<?php echo form_label("QR code Type".':', 'qr_code_type',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php
								echo form_dropdown('qr_code_type', array(
									'qr'  => 'Default Type',
									'qr-l'    => 'Low Error Correction Level',
									'qr-m'   => 'Medium Error Correction Level', 
									'qr-q'    => 'Quartile Error Correction Level',
									'qr-h'    => 'High Error Correction Level'
									),
									$qr_code_type, 'class="form-control"'
								);
							?>
						</div>
					</div>
					<div class="form-group">
						<?php echo form_label("QR Code Overall Width(inch)".':', 'qr_code_overall_width',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_overall_width',
								'id'=>'qr_code_overall_width',
								'class'=>'form-control form-inps',
								'value'=>$qr_code_overall_width)
							);?>
						</div>
					</div>
					<div class="form-group">
						<?php echo form_label("QR Code Overall Height(inch)".':', 'qr_code_overall_height',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_overall_height',
								'id'=>'qr_code_overall_height',
								'class'=>'form-control form-inps',
								'value'=>$qr_code_overall_height)
							);?>
						</div>
					</div>
					<div class="form-group">
						<?php echo form_label("QR Code Width(inch)".':', 'qr_code_width',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_width',
								'id'=>'qr_code_width',
								'class'=>'form-control form-inps',
								'value'=>$qr_code_width)
							);?>
						</div>
					</div>

					<div class="form-group">
						<?php echo form_label("Overall Font Size".':', 'qr_code_overall_font_size',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_overall_font_size',
								'id'=>'qr_code_overall_font_size',
								'class'=>'form-control form-inps',
								'value'=>$qr_code_overall_font_size)
							);?>
						</div>
					</div>

					<!-- <div class="form-group">
						<?php echo form_label("QR Code Scale".':', 'qr_code_scale',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_scale',
								'id'=>'qr_code_scale',
								'class'=>'form-control form-inps',
								'value'=>$qr_code_scale)
							);?>
						</div>
					</div> -->

					<div class="form-group">
						<?php echo form_label("Zero Fill QR Code".':', 'zerofill_qr_code',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'zerofill_qr_code',
								'id'=>'zerofill_qr_code',
								'class'=>'form-control form-inps',
								'value'=>$this->config->item('zerofill_qr_code') ? $this->config->item('zerofill_qr_code') : 10)
							);?>
						</div>
					</div>
					<div class="form-group">
						<?php echo form_label(lang('common_save_above_values_name').':', 'qr_code_saved_name',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
						<div class="col-sm-9 col-md-9 col-lg-10">
							<?php echo form_input(array(
								'name'=>'qr_code_saved_name',
								'id'=>'qr_code_saved_name',
								'class'=>'form-control form-inps',
								'value'=>'')
							);?>
						</div>
					</div>
				</div>
				<input type="submit" class="btn btn-lg btn-primary">
			</div>
		</div>
	</form>

	<?php
	if(isset($excel_url) && $excel_url)
	{
	?>
	<form action="<?php echo $excel_url; ?>" method="POST">
	<br />	
	<?php
	
	
		
	if ($this->input->post('item_id'))
	{
		echo form_hidden('item_id',$this->input->post('item_id'));
	}

	if ($this->input->post('items_number_of_qr_barcodes'))
	{
		echo form_hidden('items_number_of_qr_codes',$this->input->post('items_number_of_qr_barcodes'));	
	}
	
	if ($this->input->post('item_variations_number_of_qr_barcodes'))
	{
		foreach($this->input->post('item_variations_number_of_qr_barcodes') as $var_id => $qty)
		{
			echo form_hidden('item_variations_number_of_qr_codes['.$var_id.']',$qty);
		}
	}
	?>
	<input type="submit" class="btn btn-success btn-lg" value="<?php echo lang('common_excel_export'); ?>">
	<?php	
	}
	?>
</form>
	
	<br /><br />
	<a class="btn btn-danger text-white hidden-print" id="reset_labels" href="<?php echo site_url('home/reset_qr_code_labels');?>"><?php echo lang('items_reset_labels');?></a><br /><br /><br />

	<button class="btn btn-primary text-white hidden-print" id="print_button" onclick="window.print();"><?php echo lang('common_print'); ?></button>	
</div>	
<?php 
$company = ($company = $this->Location->get_info_for_key('company')) ? $company : $this->config->item('company');

for($k=0;$k<count($items);$k++)
{
	$customer_name = "";
	if( isset($customers) && array_key_exists($k, $customers)){
		$customer_name = $customers[$k]['customer_name'];
	}
	// customer phone 
	$customer_phone = "";
	if( isset($customers) && array_key_exists($k, $customers)){
		$customer_phone = $customers[$k]['customer_phone'];
	}

	$item 			= $items[$k];
	$expire_key 	= (isset($from_recv) ? $from_recv : 0).'|'.ltrim($item['id'],0);
	$qr_code 		= $item['id'];
	$text 			= $item['name'];
	$description 	= $item['description'];

	// Check Store Config to see if Show Description on Service Tag is enabled or not 
	if($this->config->item('show_item_description_service_tag') && $this->uri->segment(1)=='work_orders'){
		$text .= ' <br> '.$description;
	}

	// Check Store Config to see if Show Show Custom Fields on Service Tag is enabled or not 
	if($this->config->item('show_custom_fields_service_tag_work_orders')) {
		if(isset($item['custom_fields'])) {
			if($this->config->item('show_custom_fields_label_service_tag_work_orders')) { 
				$text .= '<br>'.implode('<br> ',$item['custom_fields']);
			} else {
				$text .= '<br> '.implode(', ',$item['custom_fields']);
			}
		}
	}

	if($this->config->item('show_estimated_repair_date_on_service_tag_work_orders')) {
		if(isset($item['estimated_repair_date'])) {
			$text .= '<br>'.$item['estimated_repair_date'];
		}
	}

	if(!$this->config->item('hide_expire_date_on_barcodes') && isset($items_expire[$expire_key]) && $items_expire[$expire_key] && !$this->config->item('hide_name_on_barcodes'))
	{
		$text.= " (".lang('common_expire_date').' '.$items_expire[$expire_key].')';		
	}
	elseif (isset($from_recv) && !$this->config->item('hide_name_on_barcodes'))
	{
		if (!$this->config->item('disable_recv_number_on_barcode'))
		{
			$text.= " (RECV $from_recv)";
		}
	}
	

	if($customer_name != ""){
		echo '<p style="font-size: 10pt;
		line-height: .9em;
		font-family: Arial, Helvetica, sans-serif !important;
		color: #000000 !important;
		width: 100%; 
		text-align: center;
		margin: 20px 0 0 0; ">'.$customer_name.'</p>';
	}
	// Check Store Config to see if Show Customer Phone on Service Tag is enabled or not
	if($this->config->item('show_phone_number_service_tag')){
		if($customer_phone != ""){
			echo '<p style="font-size: 10pt;
			line-height: .9em;
			font-family: Arial, Helvetica, sans-serif !important;
			color: #000000 !important;
			width: 100%; 
			text-align: center;
			margin: 2px 0 0 0; ">'.$customer_phone.'</p>';
		}
	}

	$page_break_after = ($k == count($items) -1) ? 'auto' : 'always';

	echo "<div class='qr-code-label' style='page-break-after: $page_break_after'>"."<img src='" .site_url('qrcodegenerator/index?qrcode=' . urlencode($qr_code)) . '&s=' . urlencode($qr_code_type) . '&scale='.rawurlencode($qr_code_scale). '&width='.$qr_code_width."' alt='QR code'/>"."<br/><br/>".$qr_code."<br /><br />".$text."</div>";
}
?>
<script>
	<?php if (isset($_POST) && count($_POST)) { ?>
		var post_data = <?php echo json_encode($_POST); ?>;
		var post_data_clean = [];
		
		for (var name in post_data) 
		{
			var value = post_data[name];
			post_data_clean.push({name: name,value: value});
		}
	<?php } ?>
	
	if (typeof post_data !== 'undefined') 
	{
		$("#qr_code_form").submit(function(e){
			e.preventDefault();
			$(this).ajaxSubmit(function()
			{
				post_submit(<?php echo json_encode(current_url()); ?>,"POST",post_data_clean);
			});
		});
		
		$("#excel_form").submit(function(e){
			e.preventDefault();
			$(this).ajaxSubmit(function()
			{
				post_submit(<?php echo json_encode(current_url()); ?>,"POST",post_data_clean);
			});
		});
		
		
		$("#reset_labels").click(function(e)
		{
			e.preventDefault();
			$.get($(this).attr('href'), function()
			{
				post_submit(<?php echo json_encode(current_url()); ?>,"POST",post_data_clean);
			});
		});
	}
	else
	{
		$("#qr_code_form").submit(function(e){
			e.preventDefault();
			$(this).ajaxSubmit(function()
			{
				window.location.reload();
			});
		});
	}
	
	$("#saved_qr_coded_labels").change(function()
	{
		if ($(this).val())
		{
			var settings = JSON.parse($(this).val());
		
			for(var key in settings)
			{
				$("#"+key).val(settings[key]);
			}
		} else {
			location.reload();
		}
	});
	</script>
<?php $this->load->view("partial/footer"); ?>