<?php $this->load->view("partial/header"); ?>
<div id="status"><?php echo lang('common_wait');?> <?php echo img(array('src' => base_url().'assets/img/ajax-loader.gif')); ?></div>

<div class="panel panel-piluku">
	<div class="panel-body">
	   <h4 id="title"><?php echo lang('sales_please_swipe_credit_card_on_machine');?></h4>
	</div>
</div>
<?php
if(isset($balance)) { ?>
	<script>
	bootbox.alert(<?php echo json_encode(lang('common_balance').': '.to_currency($balance)); ?>, function()
	{
		window.location = '<?php echo site_url('sales');?>';
	});
	</script>
<?php
$this->load->view("partial/footer");
}