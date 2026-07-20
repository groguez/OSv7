<?php
$has_cost_price_permission = $this->Employee->has_module_action_permission('items', 'see_cost_price', $this->Employee->get_logged_in_employee_info()->person_id);
?>
<style>
	.editable-container .editable-input textarea.form-control 
	{
		height: 80px !important;
	}
</style>
<a tabindex="-1" href="#" class="dismissfullscreen <?php echo !$fullscreen ? 'hidden' : ''; ?>"><i class="ion-close-circled"></i></a>
<?php if ($cart->get_previous_receipt_id()) { ?>
	<div class="alert alert-danger">
		<?php echo lang('receivings_editing_recv'); ?> <strong><?php echo 'RECV ' . $cart->receiving_id; ?></strong>
	</div>
<?php } ?>

<?php
$search_from_selector = "#add_item_form";
if($this->config->item('advanced_receiving_interface')){
	$search_from_selector = "#add_item_dialog";
}
?>

<div class="row register">
	<div class="col-lg-8 col-md-7 col-sm-12 col-xs-12 no-padding-right no-padding-left">
		<div class="register-box register-items-form">

			<div class="item-form">
				<!-- Item adding form -->
				<?php echo form_open("receivings/add", array('id' => 'add_item_form', 'class' => 'form-inline', 'autocomplete' => 'off')); ?>
				<div class="input-group input-group-mobile contacts">
					<span class="input-group-addon">
						<?php echo anchor("items/view/-1/?redirect=receivings/&progression=1", "<i class='icon ti-pencil-alt'></i> <span class='register-btn-text'>" . lang('common_new_item') . "</span>", array('class' => 'none add-new-item', 'title' => lang('common_new_item'), 'id' => 'new-item-mobile')); ?>
					</span>
					<div class="input-group-addon register-mode <?php echo $mode; ?>-mode dropdown">
						<?php echo anchor("#", "<i class='icon ti-shopping-cart'></i><span class='register-btn-text'>" . $modes[$mode] . "</span>", array('class' => 'none active', 'title' => $modes[$mode], 'id' => 'register-mode-mobile', 'data-target' => '#', 'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'role' => 'button', 'aria-expanded' => 'false')); ?>
						<ul class="dropdown-menu sales-dropdown">
							<?php foreach ($modes as $key => $value) {
								if ($key != $mode) {
							?>
									<li><a tabindex="-1" href="#" data-mode="<?php echo $key; ?>" class="change-mode"><?php echo $value; ?></a></li>
							<?php }
							} ?>
						</ul>
					</div>

					<span class="input-group-addon grid-buttons <?php echo $mode == 'store_account_payment' ? 'hidden' : ''; ?>">
						<?php echo anchor("#", "<i class='icon ti-layout'></i> <span class='register-btn-text'> " . lang('common_show_grid') . "</span>", array('class' => 'none show-grid', 'title' => lang('common_show_grid'))); ?>
						<?php echo anchor("#", "<i class='icon ti-layout'></i> <span class='register-btn-text'> " . lang('common_hide_grid') . "</span>", array('class' => 'none hide-grid hidden', 'title' => lang('common_hide_grid'))); ?>
					</span>
				</div>

				<div class="input-group contacts  register-input-group">
					<!-- Css Loader  -->
					<div class="spinner ajax-loader" style="display:none">
						<div class="rect1"></div>
						<div class="rect2"></div>
						<div class="rect3"></div>
					</div>
					<span class="input-group-addon">
						<?php echo anchor("items/view/-1/?redirect=receivings/&progression=1", "<i class='icon ti-pencil-alt'></i>", array('class' => 'none add-new-item', 'title' => lang('common_new_item'), 'id' => 'new-item')); ?>
					</span>

					<input type="text" id="<?php echo $this->config->item('advanced_receiving_interface') ? "item_advanced_input" : "item" ?>" name="item" <?php echo ($mode == "store_account_payment") ? 'disabled="disabled"' : '' ?> class="add-item-input pull-left keyboardTop" placeholder="<?php echo lang('common_start_typing_item_name'); ?>" data-title="<?php echo lang('common_item_name'); ?>">
					<input type="hidden" name="secondary_supplier_id" class="secondary_supplier_id" />
					<input type="hidden" name="default_supplier_id" class="default_supplier_id" />
					<div class="input-group-addon register-mode <?php echo $mode; ?>-mode dropdown">
						<?php echo anchor("#", "<i class='icon ti-shopping-cart'></i>" . $modes[$mode], array('class' => 'none active', 'title' => $modes[$mode], 'id' => 'register-mode', 'data-target' => '#', 'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'role' => 'button', 'aria-expanded' => 'false')); ?>
						<ul class="dropdown-menu sales-dropdown">
							<?php foreach ($modes as $key => $value) {
								if ($key != $mode) {
							?>
									<li><a tabindex="-1" href="#" data-mode="<?php echo $key; ?>" class="change-mode"><?php echo $value; ?></a></li>
							<?php }
							} ?>
						</ul>
					</div>

					<span class="input-group-addon grid-buttons <?php echo $mode == 'store_account_payment' ? 'hidden' : ''; ?>">
						<?php echo anchor("#", "<i class='icon ti-layout'></i> " . lang('common_show_grid'), array('class' => 'none show-grid', 'title' => lang('common_show_grid'))); ?>
						<?php echo anchor("#", "<i class='icon ti-layout'></i> " . lang('common_hide_grid'), array('class' => 'none hide-grid hidden', 'title' => lang('common_hide_grid'))); ?>
					</span>
				</div>
				</form>
			</div>
		</div>
		<!-- /.Item Form -->

		<!-- Register Items. @contains : Items table -->
		<div class="register-box register-items paper-cut">
			<div class="register-items-holder">

				<?php if ($mode != 'store_account_payment') { ?>

					<?php if ($pagination) { ?>
						<div class="page_pagination pagination-top hidden-print  text-center" id="pagination_top">
							<?php echo $pagination; ?>
						</div>
					<?php } ?>

					<?php if ($this->config->item('allow_drag_drop_recv') && !$this->agent->is_mobile() && !$this->agent->is_tablet()) {  ?>

					<style>
						#register tbody{
							cursor: move;
						}
						#register th.item_sort_able{
							cursor: pointer;
						}

						#grid-loader2.spinner > div {
							height: 100px;
							width: 8px;
							margin-right: 2px;
							margin-top: 30px;
							top: 50%;
						}						
					</style>
					<?php } ?>

					<div class="spinner" id="grid-loader2" style="display: none;">
						<div class="rect1"></div>
						<div class="rect2"></div>
						<div class="rect3"></div>
					</div>					
					<table id="register" class="table table-hover">

						<thead>
							<tr class="register-items-header">
								<th><a href="javascript:void(0);" id="sale_details_expand_collapse" class="expand">-</a></th>
								<th class="item_sort_able item_name_heading <?php echo $this->cart->sort_column && $this->cart->sort_column == 'name'? ($this->cart->sort_type=='asc'?"ion-arrow-down-b":"ion-arrow-up-b"):"";?>"><?php echo lang('sales_item_name'); ?></th>
								<th class="item_sort_able sales_price <?php echo $this->cart->sort_column && $this->cart->sort_column == 'unit_price'? ($this->cart->sort_type=='asc'?"ion-arrow-down-b":"ion-arrow-up-b"):"";?>"><?php echo lang('receivings_cost'); ?></th>
								<th class="item_sort_able sales_quantity <?php echo $this->cart->sort_column && $this->cart->sort_column == 'quantity'? ($this->cart->sort_type=='asc'?"ion-arrow-down-b":"ion-arrow-up-b"):"";?>"><?php echo lang('common_quantity'); ?></th>
								<th class="item_sort_able sales_discount <?php echo $this->cart->sort_column && $this->cart->sort_column == 'discount'? ($this->cart->sort_type=='asc'?"ion-arrow-down-b":"ion-arrow-up-b"):"";?>"><?php echo lang('common_discount_percent'); ?></th>
								<th class="item_sort_able sales_total <?php echo $this->cart->sort_column && $this->cart->sort_column == 'total'? ($this->cart->sort_type=='asc'?"ion-arrow-down-b":"ion-arrow-up-b"):"";?>"><?php echo lang('common_total'); ?></th>
							</tr>
						</thead>


						<?php
						$cart_count = 0;
						if($this->config->item('allow_drag_drop_recv') == 1 && !$this->agent->is_mobile() && !$this->agent->is_tablet()){
							$cart_items = $cart->get_list_sort_by_receipt_sort_order();
						}

						if (count($cart_items) == 0) { ?>
							<tbody class="register-item-content">
								<tr class="cart_content_area">
									<td colspan='6'>
										<div class='text-center text-warning'>
											<h3><?php echo lang('common_no_items_in_cart'); ?> <span class="flatRedc"> [<?php echo lang('module_receivings') ?>]</span></h3>
										</div>
									</td>
								</tr>
							</tbody>
							<?php
						} else {


							$start_index = $cart->offset + 1;
							$end_index = $cart->offset + $cart->limit;

							$the_cart_row_counter = 1;

							foreach (array_reverse($cart_items, true) as $line => $item) {
								if($this->config->item('allow_drag_drop_recv') == 1 && !$this->agent->is_mobile() && !$this->agent->is_tablet()){
									$line = $item->line_index;
								}
								if ($item->quantity > 0 && $item->name != lang('common_store_account_payment')) {
									$cart_count = $cart_count + $item->quantity;
								} elseif ($mode == 'transfer') {
									$cart_count = $cart_count + abs($item->quantity);
								}

								if (!(($start_index <= $the_cart_row_counter) && ($the_cart_row_counter <= $end_index))) {
									$the_cart_row_counter++;
									continue;
								}
								$the_cart_row_counter++;

							?>
							<tbody class="register-item-content" data-line="<?php echo $line; ?>">
								<tr class="register-item-details">
									<td class="text-center"> <?php echo anchor("receivings/delete_item/$line", '<i class="icon ion-android-cancel"></i>', array('class' => 'delete-item')); ?> </td>
									<td>
										<a tabindex="-1" href="<?php echo isset($item->item_id) ? site_url('home/view_item_modal/' . $item->item_id . '/' . $line . '/receivings/' . $supplier_id) . "?redirect=receivings" : site_url('home/view_item_kit_modal/' . $item->item_kit_id) . "?redirect=receivings"; ?>" data-toggle="modal" data-target="#myModal" class="register-item-name"><?php echo H($item->name).($item->variation_name ? '<span class="show-collpased" style="display:none">  ['.$item->variation_name.']</span>' : ''); ?><?php echo $item->size ? ' (' . H($item->size) . ')' : ''; ?></a>
									</td>


									<td class="text-center">
										<?php
										if ($has_cost_price_permission) {
										?>
											<?php if ($items_module_allowed) { ?>
												<a href="#" id="unit_price_<?php echo $line; ?>" class="xeditable xeditable-price" data-validate-number="true" data-type="text" data-value="<?php echo H(to_currency_no_money($item->unit_price, 10)); ?>" data-pk="1" data-name="unit_price" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_price')); ?>"><?php echo to_currency($item->unit_price, 10); ?></a>
										<?php } else {
												echo to_currency($item->unit_price);
											}
										} ?>
									</td>

									<td class="text-center">
										<a href="#" id="quantity_<?php echo $line; ?>" class="xeditable edit-quantity" data-type="text" data-validate-number="true" data-value="<?php echo H(to_quantity($mode == "transfer" ? abs($item->quantity) : $item->quantity)); ?>" data-pk="1" data-name="quantity" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo lang('common_quantity') ?>"><?php echo to_quantity($mode == "transfer" ? abs($item->quantity) : $item->quantity); ?></a>
									</td>

									<td class="text-center">
										<?php if ($line !== $line_for_flat_discount_item && $this->Employee->has_module_action_permission('receivings', 'give_discount', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
											<a href="#" id="discount_<?php echo $line; ?>" class="xeditable" data-type="text" data-validate-number="true" data-pk="1" data-name="discount" data-value="<?php echo H($item->discount); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo lang('common_discount_percent') ?>"><?php echo to_quantity($item->discount); ?>%</a>
										<?php } else { ?>
											<?php echo to_quantity($item->discount); ?>%
										<?php }	?>
									</td>

									<td class="text-center">

										<?php
										if ($has_cost_price_permission) {
										?>

											<?php if ($items_module_allowed) { ?>
												<a href="#" id="total_<?php echo $line; ?>" class="xeditable" data-type="text" data-validate-number="true" data-pk="1" data-name="total" data-value="<?php echo H(to_currency_no_money($item->unit_price * $item->quantity - $item->unit_price * $item->quantity * $item->discount / 100)); ?>" data-url="<?php echo site_url('receivings/edit_line_total/' . $line); ?>" data-title="<?php echo lang('common_total') ?>"><?php echo to_currency($item->unit_price * $item->quantity - $item->unit_price * $item->quantity * $item->discount / 100); ?></a>
											<?php } else {
												echo to_currency($item->unit_price * $item->quantity - $item->unit_price * $item->quantity * $item->discount / 100);
											}	?>
										<?php } ?>
									</td>


								</tr>
								<tr class="register-item-bottom">
									<td>&nbsp;</td>
									<td colspan="5">
										<dl class="register-item-extra-details dl-horizontal">

											<?php
											if (count($item->quantity_units) > 0) { ?>
												<dt class=""><?php echo lang('common_quantity_units'); ?> </dt>
												<dd class="">
													<a href="#" id="quantity_unit_<?php echo $line; ?>" data-name="quantity_unit_id" data-type="select" data-pk="1" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_quantity_units')); ?>"><?php echo character_limiter(H($item->quantity_unit_id ? $item->quantity_units[$item->quantity_unit_id] : lang('common_none')), 50); ?></a></dd>
												<?php
												$source_data = array();
												$source_data[] = array('value' => 0, 'text' => lang('common_none'));

												foreach ($item->quantity_units as $quantity_unit_id => $quantity_unit_name) {
													$source_data[] = array('value' => $quantity_unit_id, 'text' => $quantity_unit_name);
												}
												?>
												<script>
													$('#quantity_unit_<?php echo $line; ?>').editable({
														value: <?php echo (H($item->quantity_unit_id) ? H($item->quantity_unit_id) : 0); ?>,
														source: <?php echo json_encode($source_data); ?>,
														success: function(response, newValue) {
															last_focused_id = $(this).attr('id');
															$("#register_container").html(response);
														}
													});
												</script>
											<?php } ?>

											<?php if ($mode == "transfer") { ?>
												<?php if (isset($location_from)) {  ?>
													<dt class="list" id="list">
														<?php echo sprintf(lang('receivings_transfer_from_location_quantity'), $location_from); ?>
													</dt>
													<dd class="list">
														<?php
															if($item->variation_id){
																$item_variation_location_info_1 = $this->Item_variation_location->get_info($item->variation_id, $location_from_id);
																echo to_quantity($item_variation_location_info_1->quantity);
															}else{
																$item_location_info_1 = $this->Item_location->get_info($item->item_id, $location_from_id);
																echo to_quantity($item_location_info_1->quantity);
															}
														?>
													</dd>
												<?php } ?>

												<?php if (isset($location)) {  ?>
													<dt class="list" id="list">
														<?php echo sprintf(lang('receivings_transfer_to_location_quantity'), $location); ?>
													</dt>
													<dd class="list">
														<?php
															if($item->variation_id){
																$item_variation_location_info_2 = $this->Item_variation_location->get_info($item->variation_id, $location_id);
																echo to_quantity($item_variation_location_info_2->quantity);
															}else{
																$item_location_info_2 = $this->Item_location->get_info($item->item_id, $location_id);
																echo to_quantity($item_location_info_2->quantity);
															}
														?>
													</dd>
												<?php } ?>
											<?php } ?>

											<dt class="list" id="list">
												<?php echo lang('common_serial_number'); ?>
											</dt>
											<dd class="list">
												<a href="#" id="serialnumber_<?php echo $line; ?>" class="xeditable" data-type="textarea" data-pk="1" data-name="serialnumber" data-value="<?php echo H($item->serialnumber); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_serial_number')); ?>"><?php echo character_limiter(H($item->serialnumber), 50); ?></a>
											</dd>

											<script>
												$("#serialnumber_<?php echo $line; ?>")
												  .on("shown", function(ev, editable) {
												    const buttons = editable.container.$form.find(".editable-buttons")[0];
												    buttons.insertAdjacentHTML("beforeend", '<br><button type="submit" class="btn btn-danger btn-sm serial_range editable-submit btn-block margin-top-10"><?php echo lang('common_range');?></button>')
												  }),

											  	$(".list").on("click", ".serial_range", function() {
												  	bootbox.prompt({
														title: '<?php echo lang('common_starting_and_ending_range_seprate_by_dash');?> <br> e.g. KT100-KT105 will add the following serial numbers: KT100,KT101,KT102,KT103,KT104,KT105',
														inputType: 'text',
														callback: function(serial_range) {
															if (serial_range) {
																$.post(<?php echo json_encode(site_url('receivings/edit_item/' . $line)); ?>, {
																	name: 'generate_serial_range',
																	value: serial_range
																}, function(response) {
																	$("#register_container").html(response);
																});
															}
														}
													})
												});
											</script>
											<?php if ($cart->get_previous_receipt_id() && $mode !='transfer') { ?>
												<dt><?php echo lang('common_qty_received'); ?></dt>
												<dd><a href="#" id="quantity_received_<?php echo $line; ?>" class="xeditable" data-type="text" data-validate-number="true" data-pk="1" data-name="quantity_received" data-value="<?php echo H(to_quantity($item->quantity_received)); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_qty_received')); ?>"><?php echo H(to_quantity($item->quantity_received)); ?></a></dd>
											<?php } ?>

											<?php if (isset($item->item_id) && $item->item_id) {
												if ($item->variation_id) {
													$item_variation_location_info = $this->Item_variation_location->get_info($item->variation_id, false, true);
													$item_location_info = $this->Item_location->get_info($item->item_id, false, true);

													$cur_quantity = $item_variation_location_info->quantity;
												} else {
													$item_location_info = $this->Item_location->get_info($item->item_id, false, true);

													$cur_quantity = $item_location_info->quantity;
												}

											?>
											
												<?php
												if ($this->Employee->has_module_action_permission('items','see_item_quantity', $this->Employee->get_logged_in_employee_info()->person_id))
												{
												?>
											
												<dt><?php echo lang('common_stock'); ?></dt>
												<dd><?php echo to_quantity($cur_quantity); ?></dd>
												<?php } ?>

												<?php
												if ($this->Employee->has_module_action_permission('sales', 'edit_sale_price', $this->Employee->get_logged_in_employee_info()->person_id)) {	
													if ($this->Employee->has_module_action_permission('items', 'can_change_global_pricing', $this->Employee->get_logged_in_employee_info()->person_id)){ ?>

													<dt><?php echo lang('common_unit_price'); ?></dt>
													<dd>
														<a href="#" id="selling_price_<?php echo $line; ?>" class="xeditable" data-type="text" data-pk="1" data-name="selling_price" data-value="<?php echo to_currency_no_money($item->selling_price, 10); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_unit_price')); ?>"><?php echo to_currency_no_money($item->selling_price, 10) ?></a>
													</dd>
													<?php
													}
													?>

													<?php
													if (($this->config->item('always_enable_location_selling_price_during_receiving')) || ($item_location_info->unit_price != '' && $item_location_info->unit_price !== NULL && (float) $item_location_info->unit_price != (float) $item->selling_price)) { 
														if ($this->Employee->has_module_action_permission('items', 'can_change_location_pricing', $this->Employee->get_logged_in_employee_info()->person_id)){ ?>
														<dt><?php echo lang('common_location') . ' ' . lang('common_unit_price'); ?></dt>
														<dd>
															<?php if ($this->Employee->has_module_action_permission('sales', 'edit_sale_price', $this->Employee->get_logged_in_employee_info()->person_id)) {	?>
																<a href="#" id="location_selling_price_<?php echo $line; ?>" class="xeditable" data-type="text" data-pk="1" data-name="location_selling_price" data-value="<?php echo to_currency_no_money($item->location_selling_price, 10); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_location') . ' ' . lang('common_unit_price')); ?>"><?php echo $item->location_selling_price == NULL || $item->location_selling_price == 0 ? lang('common_not_set') : to_currency_no_money($item->location_selling_price, 10); ?></a>
															<?php
															} else {
															?>
																<?php echo to_currency_no_money($item_location_info->unit_price, 10) ?>
															<?php } ?>
														</dd>

													<?php 
														}
													}
													?>
												<?php } ?>
												<?php
												$supplier_name = lang('common_none');
												$cart_supplier_id = $item->cart_line_supplier_id;
												$variation_choices = $item->variation_choices;

												if (!empty($variation_choices)) { ?>
													<dt class=""><?php echo lang('common_variation'); ?> </dt>
													<?php
													?>
													<a style="cursor:pointer;" onclick="enable_popup(<?php echo $line; ?>);"><?php echo lang('common_edit'); ?></a>
													<dd class=""><a href="#" id="variation_<?php echo $line; ?>" data-name="variation" data-type="select" data-pk="1" data-url="<?php echo site_url('receivings/edit_item_variation/' . $line); ?>" data-title="<?php echo H(lang('common_variation')); ?>"><?php echo character_limiter(H($item->variation_name), 50); ?></a></dd>

													<?php
													$source_data = array();

													foreach ($variation_choices as $variation_id => $variation_name) {
														$variation_info = $this->Item_variations->get_info($variation_id);

														if(isset($item->variation_id)){
															if($variation_id == $item->variation_id){
																if(isset($variation_info->supplier_id)){
																	$cart_supplier_id = $variation_info->supplier_id;
																}
															}
														}

														$temp_supplier = false;
														if(isset($variation_info->supplier_id)){
															$temp_supplier = $this->Supplier->get_name($variation_info->supplier_id);
														}

														if($temp_supplier){
															$source_data[] = array('value' => $variation_id, 'text' => $variation_name.", ".lang("common_supplier").": ".$temp_supplier);
														}else{
															$source_data[] = array('value' => $variation_id, 'text' => $variation_name);
														}
														
													}
													?>
													<script>
														$('#variation_<?php echo $line; ?>').editable({
															value: <?php echo json_encode(H($item->variation_id) ? H($item->variation_id) : ''); ?>,
															source: <?php echo json_encode($source_data); ?>,
															success: function(response, newValue) {
																last_focused_id = $(this).attr('id');
																$("#register_container").html(response);
															}

														});
													</script>
												<?php } ?>
											<?php } ?>


											<?php 
												
											if($cart_supplier_id && !$this->config->item('hide_supplier_on_recv_interface')){
													$supplier_name =  $this->Supplier->get_name($cart_supplier_id);
											?>
											
											<dt><?php echo lang('common_supplier'); ?> </dt>

											<dd class=""><a href="#" id="supplier_<?php echo $line; ?>" data-name="supplier" data-type="select" data-pk="1" data-url="<?php echo site_url('receivings/edit_item_supplier/' . $line); ?>" data-title="<?php echo H(lang('common_supplier')); ?>"><?php echo character_limiter(H($supplier_name), 50); ?></a></dd>

											<?php 
												$source_data = array();
												foreach($this->Item->get_all_suppliers_of_an_item($item->item_id)->result_array() as $row)
												{
													$source_data[] = array('value' => $row['supplier_id'], 'text' => $row['company_name'] .' ('.$row['full_name'].')');
												}
											?>

											<script>
												$('#supplier_<?php echo $line; ?>').editable({
													value: <?php echo json_encode(H($cart_supplier_id) ? H($cart_supplier_id) : ''); ?>,
													source: <?php echo json_encode($source_data); ?>,
													success: function(response, newValue) {
														try {
															let ret2 = JSON.parse(response);
															if(ret2['state'] == 0){
																show_feedback('error', ret2.message, <?php echo json_encode(lang('common_error')); ?>);
																setTimeout(function() { location.reload(); }, 2000);
																return;
															}
														} catch (e) {
														}
														last_focused_id = $(this).attr('id');
														$("#register_container").html(response);
													}
												});
											</script>
											<?php } ?>

											<?php
											if ($this->config->item('calculate_average_cost_price_from_receivings') && $has_cost_price_permission) {
											?>
												<dt><?php echo lang('receivings_cost_price_preview'); ?></dt>
												<dd><?php echo $item->cost_price_preview; ?></dd>
											<?php
											}
											?>

											<?php if (!$this->config->item('hide_description_on_sales_and_recv')) { ?>
												<dt><?php echo lang('common_description'); ?></dt>
												<dd>
													<?php if (isset($item->allow_alt_description) && $item->allow_alt_description == 1) { ?>
														<a href="#" id="description_<?php echo $line; ?>" class="xeditable" data-type="text" data-pk="1" data-name="description" data-value="<?php echo clean_html($item->description); ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('sales_description_abbrv')); ?>"><?php echo clean_html(character_limiter($item->description), 50); ?></a>
													<?php	} else {
														if ($item->description != '') {
															echo clean_html($item->description);
														} else {
															echo lang('common_none');
														}
													}
													?>
												</dd>
											<?php } ?>

<?php if ($item->expire_date || $this->config->item('always_show_expire_date_in_recv')) { ?>
                                       <dt><?php echo lang('common_expire_date'); ?></dt>
                                       <dd><a href="#" id="expire_date_<?php echo $line; ?>" class="expire_date" data-type="combodate" data-template="<?php echo get_js_date_format(); ?>" data-pk="1" data-name="expire_date" data-value="<?php echo $item->expire_date ? date('Y-m-d', strtotime($item->expire_date)) : ''; ?>" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_expire_date')); ?>"><?php echo H($item->expire_date ? $item->expire_date : lang('common_none')); ?></a></dd>
<?php } ?>
											<dt class="visible-lg">
												<?php
												switch ($this->config->item('id_to_show_on_sale_interface')) {
													case 'number':
														echo lang('common_item_number_expanded');
														break;

													case 'product_id':
														echo lang('common_product_id');
														break;

													case 'id':
														echo lang('common_item_id');
														break;

													default:
														echo lang('common_item_number_expanded');
														break;
												}
												?>
											</dt>
											<dd class="visible-lg">
												<?php
												switch ($this->config->item('id_to_show_on_sale_interface')) {
													case 'number':
														echo property_exists($item,'item_number') ? H($item->item_number) : lang('common_none');
														break;

													case 'product_id':
														echo property_exists($item,'product_id') ? H($item->product_id) : lang('common_none');
														break;

													case 'id':
														echo property_exists($item,'item_id') ? H($item->item_id) : lang('common_none');
														break;

													default:
														echo property_exists($item,'item_number') ? H($item->item_number) : lang('common_none');
														break;
												}
												?>
											</dd>

											<?php if ($this->config->item('charge_tax_on_recv')) { ?>

												<?php if ($this->Employee->has_module_action_permission('receivings', 'edit_taxes', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>

													<dt><?php echo lang('common_tax'); ?></dt>
													<dd>
														<a href="<?php echo site_url("receivings/edit_taxes_line/$line") ?>" class="" id="edit_taxes" data-toggle="modal" data-target="#myModal"><?php echo lang('common_edit_taxes'); ?></a>
													</dd>
												<?php } ?>
											<?php } ?>

										</dl>
									</td>
								</tr>
								<?php
									$line_item_custom_field_count = $this->Receiving->get_line_item_custom_field_count();
								?>
								<tr class="register-item-custom-fields" style="<?php echo $line_item_custom_field_count == 0 ? 'display:none;' : '' ?>">
									<td colspan="100%" class="text-right">
										<div class="line_item_custom_fields" style="cursor:pointer;"> <i class="icon ion-android-add"></i>  <?php echo lang('common_custom_field_config'); ?> </div>
										<div class="custom-fields-container collapse">
											<div style="display:flex; flex-direction:column; width:100%; margin-top:10px;">
												<?php 
													$item_info = $item;
													for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) {
														$custom_field = $this->Receiving->get_line_item_custom_field($k);
														if($custom_field !== FALSE) {
															$required = false;
															$required_text = '';
															if($this->Receiving->get_line_item_custom_field($k,'required') && in_array($current_location,$this->Receiving->get_line_item_custom_field($k,'locations'))){
																$required = true;
																$required_text = 'required';
															}
														?>
														<div class="form-group">
															<?php echo form_label($custom_field . ' :', "line_${line}_custom_field_${k}_value", array('class'=>"col-sm-3 col-md-3 col-lg-2 control-label $required_text")); ?>
															<div class="col-sm-9 col-md-9 col-lg-10">
																<?php if ( $this->Receiving->get_line_item_custom_field($k,'type') == 'checkbox' ) { ?>
																	
																	<?php echo form_checkbox("line_${line}_custom_field_${k}_value", '1', (boolean)$item_info->{"line_item_custom_field_${k}_value"},"id='line_${line}_custom_field_${k}_value' class='custom_field_${k}_value form-control line-item-custom-fields-checkbox' $required_text");?>
																	<label for="<?php echo "line_${line}_custom_field_${k}_value"; ?>"><span></span></label>
																	
																<?php } elseif( $this->Receiving->get_line_item_custom_field($k,'type') == 'date') { ?>
																	
																	<?php echo form_input(array(
																	'name'=>"line_${line}_custom_field_${k}_value",
																	'id'=>"line_${line}_custom_field_${k}_value",
																	'class'=>"custom_field_${k}_value".' form-control line-item-custom-fields-date',
																	'value'=>is_numeric($item_info->{"line_item_custom_field_${k}_value"}) ? date(get_date_format(), $item_info->{"line_item_custom_field_${k}_value"}) : '',
																	($required ? $required_text : $required_text) => ($required ? $required_text : $required_text)
																	)
																	);?>									
																	<script type="text/javascript">
																		var $field = <?php echo "\$('#line_${line}_custom_field_${k}_value')"; ?>;
																	$field.datetimepicker({format: JS_DATE_FORMAT, locale: LOCALE, ignoreReadonly: IS_MOBILE ? true : false});	
																		
																	</script>
																			
																<?php } elseif( $this->Receiving->get_line_item_custom_field($k,'type') == 'dropdown') { ?>
																		
																	<?php 
																	$choices = explode('|',$this->Receiving->get_line_item_custom_field($k,'choices'));
																	$select_options = array('' => lang('common_please_select'));
																	foreach($choices as $choice)
																	{
																		$select_options[$choice] = $choice;
																	}
																	echo form_dropdown("line_${line}_custom_field_${k}_value", $select_options, $item_info->{"line_item_custom_field_${k}_value"}, 'class="form-control line-item-custom-fields-select" '.$required_text);?>
																		
																<?php } elseif( $this->Receiving->get_line_item_custom_field($k,'type') == 'image') {
																	echo form_input(
																		array(
																			'name'=>"line_${line}_custom_field_${k}_value",
																			'id'=>"line_${line}_custom_field_${k}_value",
																			'type' => 'file',
																			'class'=>"custom_field_${k}_value".' form-control line-item-custom-fields-file',
																			'accept'=>".png,.jpg,.jpeg,.gif,.webp"
																		),
																		NULL,
																		$item_info->{"line_item_custom_field_${k}_value"} ? "" : $required_text
																	);
																
																	if ($item_info->{"line_item_custom_field_${k}_value"})
																	{
																		echo "<img width='30%' src='".cacheable_app_file_url($item_info->{"line_item_custom_field_${k}_value"})."' />";
																		echo "<div class='delete-custom-image'><a href='".site_url('receivings/delete_line_item_custom_field_value/'.$item_info->item_id.'/'.$k)."'>".lang('common_delete')."</a></div>";
																	}
																}
																elseif( $this->Receiving->get_line_item_custom_field($k,'type') == 'file')
																{
																	echo form_input(
																		array(
																			'name'=>"line_${line}_custom_field_${k}_value",
																			'id'=>"line_${line}_custom_field_${k}_value",
																			'type' => 'file',
																			'class'=>"custom_field_${k}_value".' form-control line-item-custom-fields-file'
																		),
																		NULL,
																		$item_info->{"line_item_custom_field_${k}_value"} ? "" : $required_text
																	);

																	if ($item_info->{"line_item_custom_field_${k}_value"})
																	{
																		echo anchor('items/download/'.$item_info->{"line_item_custom_field_${k}_value"},$this->Appfile->get_file_info($item_info->{"line_item_custom_field_${k}_value"})->file_name,array('target' => '_blank'));
																		echo "<div class='delete-custom-image'><a href='".site_url('receivings/delete_line_item_custom_field_value/'.$line.'/'.$k)."'>".lang('common_delete')."</a></div>";
																	}
																} else {
																	echo form_input(
																		array(
																			'name'=>"line_${line}_custom_field_${k}_value",
																			'id'=>"line_${line}_custom_field_${k}_value",
																			'class'=>"custom_field_${k}_value".' form-control line-item-custom-fields',
																			'value'=>$item_info->{"line_item_custom_field_${k}_value"},
																			($required ? $required_text : $required_text) => ($required ? $required_text : $required_text)
																		)
																	);?>
																<?php } ?>
															</div>
														</div>
													<?php } //end if?>
												<?php } //end for loop?>

											</div>
										</div>
									</td>
								</tr>
								
							</tbody>
						<?php }
						}  ?>
					</table>

					<?php if ($pagination) { ?>
						<div class="page_pagination pagination-top hidden-print  text-center" id="pagination_top">
							<?php echo $pagination; ?>
						</div>
					<?php } ?>

					<script>
						$('.line-item-custom-fields').change(function() {
							$.post('<?php echo site_url("receivings/save_line_item_custom_field"); ?>', {
								name: $(this).attr('name'),
								value: $(this).val()
							});
						});

						$('.line-item-custom-fields-checkbox').change(function() {
							$.post('<?php echo site_url("receivings/save_line_item_custom_field"); ?>', {
								name: $(this).attr('name'),
								value: $(this).prop('checked') ? 1 : 0
							});
						});

						$('.line-item-custom-fields-select').change(function() {
							$.post('<?php echo site_url("receivings/save_line_item_custom_field"); ?>', {
								name: $(this).attr('name'),
								value: $(this).val()
							});
						});

						$(".line-item-custom-fields-date").on("dp.change", function(e) {
							$.post('<?php echo site_url("receivings/save_line_item_custom_field"); ?>', {
								name: $(this).attr('name'),
								value: $(this).val()
							});
						});

						$('.line-item-custom-fields-file').change(function() {

							var formData = new FormData();
							formData.append('name', $(this).attr('name'));
							formData.append('value', $(this)[0].files[0]);

							$.ajax({
								url: '<?php echo site_url("receivings/save_line_item_custom_field"); ?>',
								type: 'POST',
								data: formData,
								processData: false,
								contentType: false
							});
						});
					</script>

					<!-- End of Sales or Return Mode -->
				<?php } else {  ?>
					<table id="register" class="table table-hover ">
						<thead>
							<tr class="register-items-header">
								<th><?php echo lang('receivings_item_name'); ?></th>
								<th><?php echo lang('common_payment_amount'); ?></th>
								<?php if (!empty($unpaid_store_account_receivings)) { ?>
									<th>&nbsp;</th>
								<?php
								} ?>
							</tr>
						</thead>
						<tbody id="cart_contents">
							<?php
							$cart_count = 0;
							foreach (array_reverse($cart_items, true) as $line => $item) {
							?>

								<tr id="reg_item_top">
									<td class="text text-center text-success"><a tabindex="-1" href="<?php echo isset($item->item_id) ? site_url('home/view_item_modal/' . $item->item_id . '/' . $line . '/receivings/' . $supplier_id) . "?redirect=receivings" : site_url('home/view_item_kit_modal/' . $item->item_kit_id) . "?redirect=receivings"; ?>" data-toggle="modal" data-target="#myModal"><?php echo H($item->name); ?></a></td>
									<td class="text-center">
										<?php
										echo form_open("receivings/edit_item/$line", array('class' => 'line_item_form', 'autocomplete' => 'off'));

										?>

										<a href="#" id="unit_price_<?php echo $line; ?>" class="xeditable" data-validate-number="true" data-type="text" data-value="<?php echo H(to_currency_no_money($item->unit_price, 10)); ?>" data-pk="1" data-name="unit_price" data-url="<?php echo site_url('receivings/edit_item/' . $line); ?>" data-title="<?php echo H(lang('common_price')); ?>"><?php echo to_currency_no_money($item->unit_price, 10); ?></a>
										<?php
										echo form_hidden('quantity', to_quantity($item->quantity));
										echo form_hidden('description', '');
										echo form_hidden('serialnumber', '');
										?>

										</form>
									</td>
									<?php if (!empty($unpaid_store_account_receivings)) {
										$pay_all_btn_class = count($paid_store_account_ids) > 0 ? 'btn-danger' : 'btn-primary';
										$pay_all_btn_text = count($paid_store_account_ids) > 0 ? lang('common_unpay_all') : lang('common_pay_all');
									?>
										<td>
											<button id="pay_or_unpay_all" type="submit" class="btn <?php echo $pay_all_btn_class; ?> pay_store_account_sale pull-right"><?php echo $pay_all_btn_text ?></button>
										</td>
									<?php } ?>
								</tr>
							<?php } /*Foreach*/ ?>
						</tbody>
					</table>
				<?php }  ?>
				<!-- End of Store Account Payment Mode -->
			</div>
		</div>
		<!-- /.Register Items -->

		<?php
		if ($mode == 'store_account_payment') {
			if (!empty($unpaid_store_account_receivings)) { ?>
				<table id="unpaid_sales" class="table table-hover table-condensed">
					<thead>
						<tr class="register-items-header">
							<th class="sp_sale_id"><?php echo lang('receivings_id'); ?></th>
							<th class="sp_date"><?php echo lang('common_date'); ?></th>
							<th class="sp_charge"><?php echo lang('common_total_charge_to_account'); ?></th>
							<th class="sp_comment"><?php echo lang('common_comment'); ?></th>
							<th class="sp_pay"><?php echo lang('common_pay'); ?></th>
						</tr>
					</thead>

					<tbody id="unpaid_sales_data">

						<?php
						foreach ($unpaid_store_account_receivings as $unpaid_receiving) {

							$row_class = isset($unpaid_receiving['paid']) && $unpaid_receiving['paid'] == TRUE ? 'success' : 'active';
							$btn_class = isset($unpaid_receiving['paid']) && $unpaid_receiving['paid'] == TRUE ? 'btn-danger' : 'btn-primary';
						?>
							<tr class="<?php echo $row_class; ?>">
								<td class="sp_receiving_id text-center"><?php echo anchor('receivings/receipt/' . $unpaid_receiving['receiving_id'], 'RECV ' . $unpaid_receiving['receiving_id'], array('target' => '_blank')); ?></td>
								<td class="sp_date text-center"><?php echo date(get_date_format() . ' ' . get_time_format(), strtotime($unpaid_receiving['receiving_time'])); ?></td>
								<td class="sp_charge text-center"><?php echo to_currency($unpaid_receiving['payment_amount']); ?></td>
								<td class="sp_comment text-center"><?php echo $unpaid_receiving['comment'] ?></td>
								<td class="sp_pay text-center">
									<?php echo form_open("receivings/" . ((isset($unpaid_receiving['paid']) && $unpaid_receiving['paid'] == TRUE) ? "delete" : "pay") . "_store_account_receiving/" . $unpaid_receiving['receiving_id'] . "/" . to_currency_no_money($unpaid_receiving['payment_amount']), array('class' => 'pay_store_account_receiving_form', 'autocomplete' => 'off', 'data-full-amount' => to_currency_no_money($unpaid_receiving['payment_amount']))); ?>
									<button type="submit" class="btn <?php echo $btn_class; ?> pay_store_account_receiving"><?php echo isset($unpaid_receiving['paid']) && $unpaid_receiving['paid'] == TRUE  ? lang('common_remove_payment') : lang('common_pay'); ?></button>
									</form>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php
			}
		} ?>
	</div>
	<!-- /.Col-lg-8 @end of left Column -->

	<!-- col-lg-4 @start of right Column -->
	<div class="col-lg-4 col-md-5 col-sm-12 col-xs-12">
		<div class="register-box register-right">
			<!-- Receive  Top Buttons  -->
			<div class="sale-buttons">
				<!-- Extra links -->
				<div class="btn-group">
					<button type="button" class="btn btn-more dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<i class="ion-android-more-horizontal"></i>
					</button>
					<ul class="dropdown-menu sales-dropdown" role="menu">


						<li>
							<?php echo anchor(
								"receivings/suspended/",
								'<i class="ion-ios-list-outline"></i> ' . lang('common_suspended_receivings') . ' ' . lang('common_and') . ' <br /> ' . lang('receivings_purchase_orders'),
								array('class' => 'none suspended_sales_btn', 'title' => lang('common_suspended_receivings'))
							);
							?>
						</li>


						<?php
						if ($this->Location->count_all() > 1) {
						?>
							<li>
								<?php echo anchor(
									"receivings/suspended/2",
									'<i class="ion-ios-list-outline"></i> ' . lang('common_transfer_requests'),
									array('class' => 'none suspended_sales_btn', 'title' => lang('common_transfer_requests'))
								);
								?>
							</li>

							<li>
								<?php echo anchor(
									"receivings/suspended/2/receivings.transfer_to_location_id",
									'<i class="ion-ios-list-outline"></i> ' . lang('receivings_incoming_transfers'),
									array('class' => 'none suspended_sales_btn', 'title' => lang('receivings_incoming_transfers'))
								);
								?>
							</li>

						<?php } ?>


						<li>
							<?php echo anchor(
								"receivings/po/",
								'<i class="ion-ios-paper"></i> ' . lang('receivings_create_purchase_order'),
								array('class' => 'none suspended_sales_btn', 'title' => lang('receivings_create_purchase_order'))
							);
							?>
						</li>

						<li>
							<?php echo '<a href="#look-up-receipt" class="look-up-receipt" data-toggle="modal"><i class="ion-document"></i> ' . lang('receivings_lookup_receipt') . '</a>'; ?>
						</li>


						<?php
						if ($last_receiving_id = $this->Receiving->get_last_receiving_id()) {
							echo '<li>';
							echo anchor(
								"receivings/receipt/$last_receiving_id",
								'<i class="ion-document"></i> ' . lang('receivings_last_receiving_receipt'),
								array('target' => '_blank', 'class' => 'look-up-receipt', 'title' => lang('receivings_last_receiving_receipt'))
							);

							echo '</li>';
						}
						?>


						<li>
							<?php echo anchor(
								"receivings/batch_receiving/",
								'<i class="ion-bag"></i> ' . lang('batch_receivings'),
								array('class' => 'none suspended_sales_btn', 'title' => lang('batch_receivings'))
							);
							?>
						</li>

						<li>
							<?php echo anchor(
								"receivings/custom_fields",
								'<span class="ion-wrench"> ' . lang('common_custom_field_config') . '</span>',
								array('id' => 'custom_fields', 'class' => '', 'title' => lang('common_custom_field_config'))
							); ?>
						</li>

						<li>
							<?php echo anchor(
								"receivings/line_item_custom_fields",
								'<span class="ion-wrench"> ' . lang('common_custom_field_config') . '<br/>(' . lang('common_line_item'). ')'. '</span>',
								array('id' => 'custom_fields', 'class' => '', 'title' => lang('common_custom_field_config'))
							); ?>
						</li>

					</ul>
				</div>
				<?php if (count($cart_items) > 0) { ?>
					<?php echo form_open("receivings/cancel_receiving", array('id' => 'cancel_sale_form', 'autocomplete' => 'off')); ?>

					<?php

					if (!$cart->get_previous_receipt_id() || $cart->suspended) { ?>
						<a href="" class="btn btn-suspended" id="suspend_recv_button">
							<i class="ion-pause"></i>
							<?php echo lang('receivings_suspend_recv'); ?>
						</a>
					<?php } ?>
					<a href="" class="btn btn-cancel" id="cancel_sale_button">
						<i class="ion-close-circled"></i>
						<?php echo $cart->get_previous_receipt_id() ? lang('common_cancel_edit') : lang('receivings_cancel_receiving'); ?>
					</a>
					</form>

				<?php } ?>
			</div>
			<!-- /.End of receive Buttons -->

			<?php if ($mode == "transfer") { ?>
				<?php if (isset($location_from)) {  ?>
					<!-- Customer Badge when customer is added -->
					<div class="customer-badge location">
						<div class="details">

							<a tabindex="-1" href="<?php echo site_url("locations/view/$location_from_id/1"); ?>" class="name">
								<?php echo lang('receivings_transfer_from'); ?>: <?php echo character_limiter(H($location_from), 30); ?>
							</a>

						</div>

					</div>
					<div class="customer-action-buttons btn-group btn-group-justified">
						<a tabindex="-1" href="<?php echo site_url("locations/view/$location_from_id/1"); ?>" class="btn success">
							<i class="ion-ios-compose-outline"></i>
							Edit
						</a>
						<?php echo '' . anchor("receivings/delete_location_from", '<i class="ion-close-circled"></i> ' . lang('common_detach'), array('id' => 'delete_location_from', 'class' => 'btn')); ?>

					</div>
				<?php } else {  ?>

					<div class="customer-form">

						<!-- if the location is not set , show location adding form -->
						<?php echo form_open("receivings/select_location_from", array('id' => 'select_location_from_form', 'autocomplete' => 'off')); ?>
						<div class="input-group contacts">
							<span class="input-group-addon">
								<?php echo anchor("locations/view/-1", "<i class='ion-plus'></i>", array('class' => 'none', 'title' => lang('common_new_customer'), 'id' => 'new-customer')); ?>
							</span>
							<input type="text" id="location_from" name="location_from" class="add-customer-input" placeholder="<?php echo lang('receivings_start_typing_location_name_from'); ?>" data-title="<?php echo lang('common_location'); ?>" />

						</div>
						</form>

					</div>


				<?php }  ?>

				<?php if (isset($location)) {  ?>
					<!-- Customer Badge when customer is added -->
					<div class="customer-badge location">
						<div class="details">

							<a tabindex="-1" href="<?php echo site_url("locations/view/$location_id/1"); ?>" class="name">
								<?php echo lang('receivings_transfer_to'); ?>: <?php echo character_limiter(H($location), 30); ?>
							</a>

						</div>

					</div>
					<div class="customer-action-buttons btn-group btn-group-justified">
						<a tabindex="-1" href="<?php echo site_url("locations/view/$location_id/1"); ?>" class="btn success">
							<i class="ion-ios-compose-outline"></i>
							Edit
						</a>
						<?php echo '' . anchor("receivings/delete_location", '<i class="ion-close-circled"></i> ' . lang('common_detach'), array('id' => 'delete_location', 'class' => 'btn')); ?>

					</div>
				<?php } else {  ?>

					<div class="customer-form">

						<!-- if the location is not set , show location adding form -->
						<?php echo form_open("receivings/select_location", array('id' => 'select_location_form', 'autocomplete' => 'off')); ?>
						<div class="input-group contacts">
							<span class="input-group-addon">
								<?php echo anchor("locations/view/-1", "<i class='ion-plus'></i>", array('class' => 'none', 'title' => lang('common_new_customer'), 'id' => 'new-customer')); ?>
							</span>
							<input type="text" id="location" name="location" class="add-customer-input" placeholder="<?php echo lang('receivings_start_typing_location_name'); ?>" data-title="<?php echo lang('common_location'); ?>" />

						</div>
						</form>

					</div>


				<?php }  ?>
			<?php } else {  ?>
				<?php if (isset($supplier)) {  ?>
					<!-- Customer Badge when customer is added -->
					<div class="customer-badge">
						<div class="avatar">
							<img src="<?php echo $avatar; ?>" alt="">
						</div>
						<div class="details">
							<a tabindex="-1" href="<?php echo site_url("suppliers/view/$supplier_id/1"); ?>" class="name">
								<?php echo character_limiter(H($supplier), 30); ?>
								<?php if ($this->config->item('suppliers_store_accounts') && isset($supplier_balance)) { ?>
									<span class="<?php echo $has_balance ? 'text-danger' : 'text-success'; ?> balance">(<?php echo to_currency($supplier_balance); ?>)</span>
								<?php } ?>
							</a>

							<!-- supplier Email  -->
							<?php if (!empty($supplier_email)) { ?>
								<span class="email">
									<a href="mailto:<?php echo $supplier_email; ?>"><?php echo character_limiter(H($supplier_email), 25); ?></a>
								</span>
							<?php } ?>

							<?php if ($this->config->item('capture_internal_notes_during_receiving')) { ?>
							<span class="internal_notes">

								<?php echo form_textarea(array(
									'name' => 'internal_notes',
									'id' => 'internal_notes',
									'class' => 'form-control text-area',
									'rows' => '2',
									'cols' => '5',
									'placeholder' => lang('common_internal_notes'),
									'value' => $supplier_internal_notes
								)); ?>
							</span>
						<?php } ?>

							<!-- supplier edit -->
							<?php
							if ($this->Employee->has_module_action_permission('suppliers', 'add_update', $this->Employee->get_logged_in_employee_info()->person_id)) { 
								if ($this->config->item('enable_supplier_quick_add') && $this->Employee->has_module_action_permission('suppliers', 'add_update', $this->Employee->get_logged_in_employee_info()->person_id))
								{
								?>
									<?php echo anchor("suppliers/quick_modal/$supplier_id/1", '<i class="ion-ios-compose-outline"></i>',  array('id' => 'edit_supplier', 'data-toggle'=>"modal", 'data-target'=>"#myModalDisableClose",'class' => 'btn btn-edit btn-primary pull-right', 'title' => lang('receivings_update_supplier'))) . ''; ?>
								<?php	
								}
								else
								{
								?>
									<?php echo anchor("suppliers/view/$supplier_id/1", '<i class="ion-ios-compose-outline"></i>',  array('id' => 'edit_supplier', 'class' => 'btn btn-edit btn-primary pull-right', 'title' => lang('receivings_update_supplier'))) . ''; ?>
							<?php } } ?>
							
							
							

						</div>

					</div>


					<div class="customer-action-buttons btn-group btn-group-justified ">
						<?php if (!empty($supplier_email)) { ?>
							<a href="#" class="btn <?php echo (bool) $email_receipt ? 'checked' : ''; ?>" id="toggle_email_receipt">
								<i class="ion-android-mail"></i>
								<?php echo $is_po ? lang('receivings_email_po') : lang('common_email_receipt'); ?>?
							</a>
						<?php } else { ?>
							<?php if ($this->Employee->has_module_action_permission('suppliers', 'add_update', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
								<a href="<?php echo site_url('suppliers/view/' . $supplier_id . '/1');  ?>" class="btn">
									<i class="ion-ios-compose-outline"></i>
									<?php echo lang('receivings_update_supplier'); ?>
								</a>
							<?php } ?>
						<?php } ?>


						<?php
						echo form_checkbox(array(
							'name' => 'email_receipt',
							'id' => 'email_receipt',
							'value' => '1',
							'class'       => 'email_receipt_checkbox hidden',
							'checked' => (bool) $email_receipt
						));

						?>


						<?php echo '' . anchor("receivings/delete_supplier", '<i class="ion-close-circled"></i> ' . lang('common_detach'), array('id' => 'delete_supplier', 'class' => 'btn')); ?>
					</div>
				<?php } else {  ?>

					<div class="customer-form">

						<!-- if the supplier is not set , show supplier adding form -->
						<?php echo form_open("receivings/select_supplier", array('id' => 'select_supplier_form', 'autocomplete' => 'off')); ?>
						<div class="input-group contacts">
							<span class="input-group-addon">
								
								<?php
								if ($this->config->item('enable_supplier_quick_add') && $this->Employee->has_module_action_permission('suppliers', 'add_update', $this->Employee->get_logged_in_employee_info()->person_id))
								{
								?>
									<?php echo anchor("suppliers/quick_modal/-1/1", "<i class='ion-plus'></i>", array('class' => 'none', 'title' => lang('receivings_new_supplier'), 'id' => 'new-customer', 'data-toggle'=>"modal", 'data-target'=>"#myModalDisableClose")); ?>
						
								<?php	
								}
								else
								{
								?>
									<?php echo anchor("suppliers/view/-1/1", "<i class='ion-plus'></i>", array('class' => 'none', 'title' => lang('receivings_new_supplier'), 'id' => 'new-customer')); ?>
								<?php
								}
								?>
								
							</span>
							<input type="text" id="supplier" name="supplier" class="add-customer-input keyboardLeft" data-title="<?php echo lang('common_supplier'); ?>" placeholder="<?php echo lang('receivings_start_typing_supplier_name') . ($this->config->item('require_supplier_for_recv') ? ' (' . lang('common_required') . ')' : ''); ?>" />

						</div>
						</form>

					</div>


				<?php }  ?>


			<?php } ?>
		</div>
		<!-- Summary -->
		<div class="register-box register-summary paper-cut">
			<ul class="list-group">
				<?php if ($this->Employee->has_module_action_permission('receivings', 'give_discount', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
					<li class="list-group-item global-discount-group">
						<div class="key"><?php echo lang('receivings_global_receiving_discount_percent') . ': '; ?> </div>
						<div class="value pull-right">
							<a href="#" id="discount_all_percent" class="xeditable" data-validate-number="false" data-placement="<?php echo $discount_editable_placement; ?>" data-type="text" data-pk="1" data-name="discount_all_percent" data-url="<?php echo site_url('receivings/discount_all'); ?>" data-title="<?php echo H(lang('receivings_global_receiving_discount_percent')); ?>" data-emptytext="<?php echo H(lang('receivings_set_discount')); ?>" data-placeholder="<?php echo H(lang('receivings_set_discount')); ?>"><?php echo isset($discount_all_percent) &&  $discount_all_percent > 0 ?  to_quantity($discount_all_percent) : '' ?></a>
							<?php
							if (isset($discount_all_percent) &&  $discount_all_percent > 0) {
								echo '%';
							}
							?>
						</div>
					</li>

					<li class="list-group-item global-discount-group">
						<div class="key"><?php echo lang('receivings_global_receiving_discount_fixed') . ': '; ?> </div>
						<?php
						$symbol = "";
						if (isset($discount_all_fixed) &&  $discount_all_fixed) {
							$symbol = ($this->config->item('currency_symbol') ? $this->config->item('currency_symbol') : '$');
						}
						?>
						<div class="value pull-right"><span id="TEST"><?php echo $symbol; ?></span>
							<a href="#" id="discount_all_flat" class="xeditable" data-validate-number="false" data-type="text" data-placement="<?php echo $discount_editable_placement; ?>" data-pk="1" data-name="discount_all_flat" data-url="<?php echo site_url('receivings/discount_all'); ?>" data-title="<?php echo H(lang('receivings_global_receiving_discount_fixed')); ?>" data-emptytext="<?php echo H(lang('receivings_set_discount')); ?>" data-placeholder="<?php echo H(lang('receivings_set_discount_fixed_or_percent')); ?>"><?php echo isset($discount_all_fixed) &&  $discount_all_fixed ? $discount_all_fixed : ''; ?></a>
						</div>
					</li>
				<?php } ?>

				<li class="sub-total list-group-item receivings <?php echo (!$see_cost_price) ? "hide" : ""; ?>">
					<span class="key"><?php echo lang('common_sub_total'); ?>:</span>
					<span class="value">

						<?php if ($items_module_allowed) { ?>
							<a href="#" id="subtotal" class="xeditable xeditable-price" data-validate-number="true" data-type="text" data-placement="<?php echo $discount_editable_placement; ?>" data-value="<?php echo H(to_currency_no_money($subtotal)); ?>" data-pk="1" data-name="subtotal" data-url="<?php echo site_url('receivings/edit_subtotal'); ?>" data-title="<?php echo H(lang('common_sub_total')); ?>">

								<?php if (isset($exchange_name) && $exchange_name) {
									echo to_currency_as_exchange($cart, $subtotal);
								?>
								<?php } else {  ?>
									<?php echo to_currency($subtotal,10); ?>
								<?php } ?>
							</a>
						<?php } else { ?>
							
								<?php if (isset($exchange_name) && $exchange_name) {
									echo to_currency_as_exchange($cart, $subtotal);
								?>
								<?php } else {  ?>
									<?php echo to_currency($subtotal); ?>
								<?php } ?>
							
						<?php } ?>
					</span>

					<?php if ($this->Employee->has_module_action_permission('receivings', 'edit_taxes', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
						<?php if ($this->config->item('charge_tax_on_recv')) { ?>
							[<a href="<?php echo site_url('receivings/edit_taxes/') ?>" class="" id="edit_taxes" data-toggle="modal" data-target="#myModal"><?php echo lang('common_edit_taxes'); ?></a>]
						<?php } ?>
					<?php } ?>
				</li>
					
				<?php foreach ($taxes as $name => $value) { ?>
					<li class="list-group-item <?php echo (!$see_cost_price) ? "hide" : ""; ?>">
						<span class="key">
							<?php if ($this->Employee->has_module_action_permission('receivings', 'delete_taxes', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
								<?php echo anchor("receivings/delete_tax/" . rawurlencode($name), '<i class="icon ion-android-cancel"></i>', array('class' => 'delete-tax remove')); ?>
							<?php } ?>
							<?php echo $name; ?>:</td>
						</span>
						<span class="value pull-right">
							<?php if (isset($exchange_name) && $exchange_name) {
								echo to_currency_as_exchange($cart, $value * $exchange_rate);
							?>
							<?php } else {  ?>
								<?php echo to_currency($value * $exchange_rate); ?>
							<?php
							}
							?>
						</span>
					</li>
				<?php }; ?>
			</ul>

			<div class="amount-block <?php echo (!$see_cost_price) ? "hide" : ""; ?>">
				<div class="total amount receiving">
					<div class="side-heading">
						<?php echo lang('common_total'); ?>
					</div>
					<div class="amount total-amount" data-speed="1000" data-currency="<?php echo $this->config->item('currency_symbol'); ?>" data-decimals="<?php echo $this->config->item('number_of_decimals') !== NULL && $this->config->item('number_of_decimals') != '' ? (int) $this->config->item('number_of_decimals') : 2; ?>">
						<?php if (isset($exchange_name) && $exchange_name) {
							
							echo to_currency_as_exchange($cart, $total);
						?>
						<?php } else {  ?>
							<?php echo to_currency($total); ?>
						<?php
						}
						?>

					</div>
				</div>
				<div class="total amount-due">
					<div class="side-heading">
						<?php echo lang('common_amount_due'); ?>
					</div>
					<div class="amount">
						<?php if (isset($exchange_name) && $exchange_name) {
							echo to_currency_as_exchange($cart, $amount_due);
						?>
						<?php } else {  ?>
							<?php echo to_currency($amount_due); ?>
						<?php
						}
						?>
					</div>
				</div>
			</div>
			<!-- ./amount block -->

			<?php
			$exchange_rates = $this->Appconfig->get_exchange_rates()->result_array();
			if (count($exchange_rates)) {
				$exchange_options = array(
					'1|' . $this->config->item('currency_code') . '|' . $this->config->item('currency_symbol') . '|' . $this->config->item('currency_symbol_location') . '|' . $this->config->item('number_of_decimals') . '|' . $this->config->item('thousands_separator') . '|' . $this->config->item('decimal_point') => $this->config->item('currency_code') ? $this->config->item('currency_code') : lang('common_default')
				);
				foreach ($exchange_rates as $exchange_row) {
					$exchange_options[$exchange_row['exchange_rate'] . '|' . $exchange_row['currency_code_to'] . '|' . $exchange_row['currency_symbol'] . '|' . $exchange_row['currency_symbol_location'] . '|' . $exchange_row['number_of_decimals'] . '|' . $exchange_row['thousands_separator'] . '|' . $exchange_row['decimal_point']] = $exchange_row['currency_code_to'];
				} ?>
				<div class="amount-block exchange">
					<div class="side-heading">
						<?php echo lang('common_exchange_to'); ?>
					</div>
					<div class="amount total-amount">
						<?php
						echo form_dropdown('exchange_to', $exchange_options, $exchange_details, 'id="exchange_to" class="form-control"');
						?>
					</div>
				</div>
			<?php } ?>

			<?php if (count($cart_items) > 0) { // Only show this part if there are Items already in the Table. ?>
				<?php if (count($payments) > 0) { ?>
					<ul class="list-group payments <?php echo (!$see_cost_price) ? "hide" : ""; ?>">
						<?php foreach ($payments as $payment_id => $payment) { ?>
							<li class="list-group-item">
								<span class="key">
									<?php echo anchor("receivings/delete_payment/$payment_id", '<i class="icon ion-android-cancel"></i>', array('class' => 'delete-payment remove', 'id' => 'delete_payment_' . $payment_id)); ?>
									<?php echo character_limiter(H($payment->payment_type), 20); ?>
								</span>
								<span class="value">
									<?php
									if (isset($exchange_name) && $exchange_name) {
										echo  to_currency_as_exchange($cart, $payment->payment_amount);
									} else {
										echo  to_currency($payment->payment_amount);
									}
									?>
								</span>
							</li>
						<?php } ?>
					</ul>
				<?php } 
				
				if ($mode != "transfer") { ?>
					<?php if ($supplier_required_check) { ?>
						<div class="add-payment">
							
							<div class="row">
								<div id="create_invoice_holder" class="create_invoice_holder col-md-6 <?php echo $cart->selected_payment == lang("common_store_account") ? '' : 'hidden'; ?>">
									<div class="text-right">
										<?php echo form_label(lang('common_create_invoice'), 'create_invoice',array('class'=>'control-label wide')); ?>
										<?php echo form_checkbox(array(
										'name'=>'create_invoice',
										'id'=>'create_invoice',
										'value'=>'1',
										'checked'=>(boolean)$cart->create_invoice,
										));?>
										<label for="create_invoice" style="padding-left: 10px; margin-top:0px;"><span></span></label>
									</div>
								</div>
							</div>
							
							<div class="side-heading"><?php echo lang('common_add_payment'); ?></div>

							<?php
							if (!$selected_payment) {
								$selected_payment = $default_payment_type;
							}
							?>
							<?php foreach ($payment_options as $key => $value) {
								$active_payment =  ($selected_payment == $value) ? "active" : "";
							?>
								<a tabindex="-1" href="#" class="btn btn-pay select-payment <?php echo $active_payment; ?>" data-payment="<?php echo H($value); ?>">
									<?php echo H($value); ?>
								</a>
							<?php } ?>

							<?php echo form_open("receivings/add_payment", array('id' => 'add_payment_form', 'autocomplete' => 'off')); ?>

								<div class="input-group add-payment-form">
									<?php echo form_dropdown('payment_type', $payment_options, $selected_payment, 'id="payment_types" class="hidden"'); ?>
									<?php echo form_input(array('name' => 'amount_tendered', 'id' => 'amount_tendered', 'value' => to_currency_no_money($amount_due), 'class' => 'add-input numKeyboard form-control '.(!$has_cost_price_permission ? 'hidden' : ''), 'data-title' => lang('common_payment_amount')));	?>
									<span class="input-group-addon">
										<a href="#" class="" id="add_payment_button"><?php echo lang('common_add_payment'); ?></a>
										<a href="#" class="hidden" id="finish_sale_alternate_button"><?php echo (!$is_po ? lang('receivings_complete_receiving') : lang('receivings_suspend_and_complete_po')); ?></a>
									</span>

								</div>
							</form>
						</div>
					<?php } ?>

					<?php if ($this->config->item('track_shipping_cost_recv')) { ?>
						<div class="custom_field_block">
							<?php echo form_label(lang('common_shipping_cost'), "shipping_cost", array('class' => 'control-label ')); ?>

							<?php
							echo form_input(array(
								'name' => "shipping_cost",
								'id' => "shipping_cost",
								'class' => 'form-control custom-fields',
								'value' => $cart->shipping_cost
							)); ?>
						</div>
					<?php 
					}
				}
				?>

				<?php for ($k = 1; $k <= NUMBER_OF_PEOPLE_CUSTOM_FIELDS; $k++) { ?>
					<?php
					$custom_field = $this->Receiving->get_custom_field($k);
					if ($custom_field !== FALSE) {
						$required = false;
						$required_text = '';
						$text_alert = "";
						if($this->Receiving->get_custom_field($k,'required') && in_array($current_location,$this->Receiving->get_custom_field($k,'locations'))){
							$required = true;
							$required_text = 'required';
							$text_alert = "text-danger";
						}
						?>
						<div class="custom_field_block <?php echo "custom_field_${k}_value"; ?>">
							<?php echo form_label($custom_field, "custom_field_${k}_value", array('class' => 'control-label '.$text_alert)); ?>

							<?php if ($this->Receiving->get_custom_field($k, 'type') == 'checkbox') { ?>

								<?php echo form_checkbox("custom_field_${k}_value", '1', (bool) $cart->{"custom_field_${k}_value"}, "id='custom_field_${k}_value' class='custom-fields-checkbox customFields' $required_text"); ?>
								<label for="<?php echo "custom_field_${k}_value"; ?>"><span></span></label>

							<?php } elseif ($this->Receiving->get_custom_field($k, 'type') == 'date') { ?>

								<?php echo form_input(array(
									'name' => "custom_field_${k}_value",
									'id' => "custom_field_${k}_value",
									'class' => "custom_field_${k}_value" . ' form-control custom-fields-date customFields',
									'value' => is_numeric($cart->{"custom_field_${k}_value"}) ? date(get_date_format(), $cart->{"custom_field_${k}_value"})	 : '',
									($required ? $required_text : $required_text) => ($required ? $required_text : $required_text)
								)); ?>
								<script type="text/javascript">
									var $field = <?php echo "\$('#custom_field_${k}_value')"; ?>;
									$field.datetimepicker({
										format: JS_DATE_FORMAT,
										locale: LOCALE,
										ignoreReadonly: IS_MOBILE ? true : false
									});
								</script>

							<?php } elseif ($this->Receiving->get_custom_field($k, 'type') == 'dropdown') { ?>

								<?php
								$choices = explode('|', $this->Receiving->get_custom_field($k, 'choices'));
								$select_options = array('' => lang('common_please_select'));
								foreach ($choices as $choice) {
									$select_options[$choice] = $choice;
								}
								echo form_dropdown("custom_field_${k}_value", $select_options, $cart->{"custom_field_${k}_value"}, 'class="form-control custom-fields-select customFields" '.$required_text); ?>

							<?php } elseif ($this->Receiving->get_custom_field($k, 'type') == 'image' || $this->Receiving->get_custom_field($k, 'type') == 'file') {
								echo form_input(
									array(
										'name'=>"custom_field_${k}_value",
										'id'=>"custom_field_${k}_value",
										'type' => 'file',
										'class'=>"custom_field_${k}_value".' form-control custom-fields-file customFields'
									),
									NULL,
									$cart->{"custom_field_${k}_value"} ? "" : $required_text
								);

								if ($cart->{"custom_field_${k}_value"} && $this->Receiving->get_custom_field($k, 'type') == 'image') {
									echo "<img width='30%' src='" . app_file_url($cart->{"custom_field_${k}_value"}) . "' />";
									echo "<div class='delete-custom-image-recv'><a href='" . site_url('receivings/delete_custom_field_value/' . $k) . "'>" . lang('common_delete') . "</a></div>";
								} elseif ($cart->{"custom_field_${k}_value"} && $this->Receiving->get_custom_field($k, 'type') == 'file') {
									echo anchor('receivings/download/' . $cart->{"custom_field_${k}_value"}, $this->Appfile->get_file_info($cart->{"custom_field_${k}_value"})->file_name, array('target' => '_blank'));
									echo "<div class='delete-custom-image-recv'><a href='" . site_url('receivings/delete_custom_field_value/' . $k) . "'>" . lang('common_delete') . "</a></div>";
								}
							} else {
								echo form_input(array(
									'name' => "custom_field_${k}_value",
									'id' => "custom_field_${k}_value",
									'class' => "custom_field_${k}_value" . ' form-control custom-fields customFields',
									'value' => $cart->{"custom_field_${k}_value"},
									($required ? $required_text : $required_text) => ($required ? $required_text : $required_text)
								)); ?>
							<?php } ?>
						</div>
					<?php } //end if ?>

				<?php  } //end for loop ?>

				<script>
					$("#shipping_cost").change(function() {
						$.post('<?php echo site_url("receivings/save_shipping_cost"); ?>', {
							'shipping_cost': $(this).val()
						});
					})
					$('.custom-fields').change(function() {
						$.post('<?php echo site_url("receivings/save_custom_field"); ?>', {
							name: $(this).attr('name'),
							value: $(this).val()
						});
					});

					$('.custom-fields-checkbox').change(function() {
						$.post('<?php echo site_url("receivings/save_custom_field"); ?>', {
							name: $(this).attr('name'),
							value: $(this).prop('checked') ? 1 : 0
						});
					});

					$('.custom-fields-select').change(function() {
						$.post('<?php echo site_url("receivings/save_custom_field"); ?>', {
							name: $(this).attr('name'),
							value: $(this).val()
						});
					});

					$(".custom-fields-date").on("dp.change", function(e) {
						$.post('<?php echo site_url("receivings/save_custom_field"); ?>', {
							name: $(this).attr('name'),
							value: $(this).val()
						});
					});

					$('.custom-fields-file').change(function() {

						var formData = new FormData();
						formData.append('name', $(this).attr('name'));
						formData.append('value', $(this)[0].files[0]);

						$.ajax({
							url: '<?php echo site_url("receivings/save_custom_field"); ?>',
							type: 'POST',
							data: formData,
							processData: false,
							contentType: false
						});
					});
				</script>
						
				<div class="change-date">
					<?php if ($mode == 'transfer' && isset($location_id) && isset($location_from_id)) { ?>
						<div id="finish_sale" class="receivings-finish-sale">
							<div class="input-group add-payment-form">
								<span class="input-group-addon">
									<a href="#" id="finish_sale_button_transfer_request" class="finish-transfer-button"><?php echo lang('receivings_send_transfer_request'); ?></a>
								</span>
							</div>
						</div>
						<h3 style="text-align:center;"><?php echo lang('common_or'); ?></h3>
						<div id="finish_sale" class="receivings-finish-sale">
							<div class="input-group add-payment-form">
								<span class="input-group-addon" style="background-color: inherit !important;">
									<a href="#" id="finish_sale_button" class="finish-transfer-button btn-danger"><?php echo lang('receivings_complete_transfer'); ?></a>
								</span>
							</div>
						</div>
					<?php } ?>

					<?php
						echo form_checkbox(array(
							'name' => 'change_date_enable',
							'id' => 'change_date_enable',
							'value' => '1',
							'checked' => (bool) $change_date_enable
						));
						echo '<label for="change_date_enable"><span></span>' . lang('receivings_change_recv_date') . '</label>';
					?>
					<div id="change_cart_date_picker" class="input-group date datepicker">
						<span class="input-group-addon"><i class="ion-calendar"></i></span>

						<?php echo form_input(array(
							'name' => 'change_cart_date',
							'id' => 'change_cart_date',
							'size' => '8',
							'class' => 'form-control',
							'value' => date(get_date_format() . ' ' . get_time_format(), $change_cart_date ? strtotime($change_cart_date) : time()),
						)); ?>
					</div>

					<div id="finish_sale" class="finish-sale receivings-finish-sale">
						<?php echo form_open("receivings/" . (!$is_po ? 'complete' : 'suspend'), array('id' => 'finish_sale_form', 'autocomplete' => 'off')); ?>
						<?php if (count($payments) > 0 && $payments_cover_total && $supplier_required_check) {
							echo "<input type='button' class='btn btn-success btn-large btn-block' id='finish_sale_button' value='" . (!$is_po ? lang('receivings_complete_receiving') : lang('receivings_suspend_and_complete_po')) . "' />";
						} ?>
						</form>
					</div>
					<div class="comment-block">
						<div class="side-heading">
							<label id="comment_label" for="comment"><?php echo lang('common_comments'); ?> : </label>
						</div>
						<?php echo form_textarea(array('name' => 'comment', 'id' => 'comment', 'value' => $comment, 'rows' => '2', 'class' => 'form-control', 'data-title' => lang('common_comments'))); ?>
					</div>
				</div>
			<?php } ?>
		</div>
		<!-- /.Summary -->
	</div>
</div>

<?php if($this->config->item('advanced_receiving_interface')){ ?>
	<div class="modal fade advanced_receiving_interface" id="advanced_receiving_interface" role="dialog" aria-labelledby="lookUpItems" aria-hidden="true">
		<div class="modal-dialog customer-recent-receiving">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="lookUpItems"><?php echo lang("sales_all_products"); ?></h4>
				</div>
				<div class="modal-body clearfix"
					style="margin:0px 10px;">
					<?php echo form_open("receivings/add", array('id' => 'add_item_dialog', 'class' => 'form-inline', 'autocomplete' => 'off')); ?>
						<div class="row">
							<div class="col-12">
								<div class="input-group contacts register-input-group" style="width:100%;">
									<!-- Css Loader  -->
									<div class="spinner ajax-loader" style="display:none">
										<div class="rect1"></div>
										<div class="rect2"></div>
										<div class="rect3"></div>
									</div>
									<span style="
										position: absolute;
										left: 10px;
										top: 10px;
									">
										<i class="icon ti-search"></i>
									</span>
									<input type="text" id="item" name="item" <?php echo ($mode == "store_account_payment" || $mode == 'purchase_points') ? 'disabled="disabled"' : '' ?> class="add-item-input pull-left keyboardTop" placeholder="<?php echo lang('common_start_typing_item_name'); ?>" data-title="<?php echo lang('common_item_name'); ?>"
									style="
										height:42px;
										width: 100%;
										background-color: #FFF;
										border: 1px solid #D7DCE5;
										padding-left: 30px !important;
										border-radius: 5px;
									"
									>
									<input type="hidden" name="advanced_item_len" id="advanced_item_len" value="0"/>
									<input type="hidden" name="secondary_supplier_id" class="secondary_supplier_id" />
									<input type="hidden" name="default_supplier_id" class="default_supplier_id" />
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="advanced_search_result_container">
									<div class="spinner"
										style="
											display:none;
											height: 100px;
											top: calc(50% - 50px);
										"
									>
										<div class="rect1"></div>
										<div class="rect2"></div>
										<div class="rect3"></div>
									</div>
									<div class="advanced_search_result" 
										style="overflow-y: auto;"
									>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div style="position:sticky; bottom: 30px; text-align:right; padding: 0 20px;">
					<button data-dismiss="modal" type="button" class="btn btn-default" onclick="close_advanced_item_list()"><?php echo lang('common_close'); ?></button>
					<button type="button" class="btn btn-primary" onclick="add_advanced_item_list()"><?php echo lang('common_add'); ?></button>
				</div>

				<div class="modal-footer">
					<div style="display:flex;">
						<label style="flex:1; text-align:left;">
							<span id="advanced_list_count">0</span> <?php echo lang("sales_selected"); ?>
						</label>
					</div>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
<?php } ?>

<div class="modal fade look-up-receipt" id="look-up-receipt" role="dialog" aria-labelledby="lookUpReceipt" aria-hidden="true">
	<div class="modal-dialog customer-recent-sales">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="lookUpReceipt"><?php echo lang('receivings_lookup_receipt') ?></h4>
			</div>
			<div class="modal-body">
				<?php echo form_open("receivings/receipt_validate", array('class' => 'look-up-receipt-form', 'autocomplete' => 'off')); ?>
				<span class="text-danger text-center has-error look-up-receipt-error"></span>
				<input type="text" class="form-control text-center" name="receiving_id" id="receiving_id" placeholder="<?php echo lang('receivings_id') ?>">
				<?php echo form_submit('submit_look_up_receipt_form', lang("receivings_lookup_receipt"), 'class="btn btn-block btn-primary"'); ?>
				<?php echo form_close(); ?>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade look-up-receipt" id="choose_var" role="dialog" aria-labelledby="lookUpReceipt" aria-hidden="true">
	<div class="modal-dialog customer-recent-sales">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="lookUpReceipt"><?php echo lang('common_variation'); ?></h4>
			</div>
			<div class="modal-body clearfix">
				<?php
				echo "<div class='placeholder_attribute_vals pull-left'>";
				if (isset($show_model)) {
					foreach ($show_model as $key => $variation) {
						echo "<a href='javascript:fetch_attr_values(" . htmlspecialchars(json_encode(trim($key)), ENT_QUOTES) . ");' class='popup_button' style='margin:5px;' id='attri_" . htmlspecialchars(trim($key), ENT_QUOTES) . "'>" . trim($key) . "</a>";
					}
				}
				echo "</div>";

				?>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="var_popup" role="dialog" aria-hidden="true">
	<div class="modal-dialog qty-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo lang('common_variation'); ?> <span id="var-customize"></span></h4>
			</div>
			<form id="save-qty-form" method="POST" action="<?php echo site_url('receivings/add_variations_qty'); ?>">
				<div class="modal-body clearfix">
					<div class="placeholder_attribute_vals pull-left variations-qty">
						<table style="width: 100%" class="table table-hover variation-qty-table">
							<thead>
								<tr class="register-items-header">
									<th><?php echo H(lang('common_variation')); ?></th>
									<th><?php echo H(lang('common_quantity')); ?></th>
								</tr>
							</thead>
						</table>
					</div>
					<div class="placeholder_supplier_vals">
						<table style="width: 100%" class="table table-hover secondary-supplier-table">
							<thead>
								<tr>
									<th></th>
									<th><?php echo H(lang('common_secondary_suppliers')); ?></th>
									<th><?php echo H(lang('common_cost_price')); ?></th>
									<th><?php echo H(lang('common_unit_price')); ?></th>
								</tr>							
							</thead>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary save-qty"><?php echo lang('common_save'); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="modal fade" id="var_popup_ss" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><span id="var-customize-ss"></span></h4>
			</div>

				<div class="modal-body clearfix">
					<div class="placeholder_supplier_vals2">
						<table style="width: 100%" class="table table-hover secondary-supplier-table">
							<thead>
								<tr>
									<th></th>
									<th><?php echo H(lang('common_suppliers')); ?></th>
									<?php if($this->Employee->has_module_action_permission('items','see_cost_price', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
									<th><?php echo H(lang('common_cost_price')); ?></th>
									<th><?php echo H(lang('common_unit_price')); ?></th>
									<?php } ?>
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" id="add_supplier" class="btn btn-primary"><?php echo lang('common_save'); ?></button>
				</div>

		</div>
	</div>
</div>

<div class="modal fade" id="var_popup_ss_1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo H(lang('common_suppliers')); ?> <span id="var-customize-ss"></span></h4>
			</div>

				<div class="modal-body clearfix">
					<div class="placeholder_supplier_vals2">
					<table style="width: 100%" class="table table-hover secondary-supplier-table">
							<thead>
								<tr>
									<th></th>
									<th><?php echo H(lang('common_suppliers')); ?></th>
									<?php if($this->Employee->has_module_action_permission('items','see_cost_price', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
									<th><?php echo H(lang('common_cost_price')); ?></th>
									<th><?php echo H(lang('common_unit_price')); ?></th>
									<?php } ?>
								</tr>

								<?php if(isset($default_supplier)){ foreach($default_supplier as $supplier){ ?>
								<tr class="default_supplier_row" style="cursor:pointer;" data-supplier_id="<?php echo $supplier->supplier_id; ?>"> 
									<td><input class="default_supplier" type="radio" style="display:block;" value="<?php echo $supplier->supplier_id; ?>" name="default_supplier" checked></td> 
									<td><?php echo $supplier->company_name; ?>, <?php echo $supplier->full_name; ?></td> 
									<?php if($this->Employee->has_module_action_permission('items','see_cost_price', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
									<td><?php echo to_currency($supplier->cost_price); ?></td> 
									<td><?php echo to_currency($supplier->unit_price); ?></td> 
									<?php } ?>
								</tr>
								<?php }} ?>

								<?php if(isset($secondary_suppliers_cart)){ foreach($secondary_suppliers_cart as $supplier){ ?>
								<tr class="secondary_supplier_row" style="cursor:pointer;" data-supplier_id="<?php echo $supplier->supplier_id; ?>"> 
									<td><input class="secondary_supplier" type="radio" style="display:block;" value="<?php echo $supplier->supplier_id; ?>" name="secondary_supplier"></td> 
									<td><?php echo $supplier->company_name; ?>, <?php echo $supplier->full_name; ?></td> 
									<?php if($this->Employee->has_module_action_permission('items','see_cost_price', $this->Employee->get_logged_in_employee_info()->person_id)) {?>
									<td><?php echo to_currency($supplier->cost_price); ?></td> 
									<td><?php echo to_currency($supplier->unit_price); ?></td> 
									<?php } ?>
								</tr>
								<?php }} ?>
							</thead>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" id="add_supplier" class="btn btn-primary"><?php echo lang('common_save'); ?></button>
				</div>

		</div>
	</div>
</div>
<div class="modal fade look-up-receipt" id="choose_quick_cash" role="dialog" aria-labelledby="lookUpReceipt" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label=<?php echo json_encode(lang('common_close')); ?>><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo lang('common_amount_tendered'); ?>&nbsp;<span id="amount_holder"></span></h4>
			</div>
			<div class="modal-body clearfix">
				<?php $currency_symbol = $this->config->item('currency_symbol'); ?>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><?php echo $currency_symbol; ?></div>
								<input type="text" class="form-control" id="custom_amount" autocomplete="off">
							</div>
						</div>
					</div>
				</div>
				
				<div class="row" id="quick_cash_holder">

				</div>
			

			</div>

			<div class="modal-footer">
				<button data-dismiss="modal" type="button" class="btn btn-default"><?php echo lang('common_close'); ?></button>
				<button data-bb-handler="confirm" data-quick_amount="0" type="button" class="btn btn-primary quick_amount" id="collect_amount"><?php echo lang('common_collect'); ?></button>
			</div>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
	function fetch_attr_values($attr_id) {
		jQuery('#choose_var').modal('show');
		jQuery.ajax({
			url: "<?php echo site_url('receivings/get_attributes_values'); ?>",
			data: {
				"attr_id": $attr_id
			},
			cache: false,
			success: function(response) {
				jQuery(".customer-recent-sales .modal-body .placeholder_attribute_vals").html(response);
				$('#choose_var').load();
			}
		});
	}

	function fetch_attr_value($attr_id) {
		jQuery.ajax({
			url: "<?php echo site_url('receivings/get_attributes_values'); ?>",
			data: {
				"attr_id": $attr_id
			},
			cache: false,
			success: function(html) {
				jQuery(".customer-recent-sales .modal-body .placeholder_attribute_vals").html(html);

				// location.reload();
			}
		});
	}

	function enable_popup($attr_id) {
		jQuery('#choose_var').modal('show');
		jQuery.ajax({
			url: "<?php echo site_url('receivings/get_attribute_values'); ?>",
			data: {
				"attr_id": $attr_id
			},
			cache: false,
			success: function(response) {
				jQuery(".customer-recent-sales .modal-body .placeholder_attribute_vals").html(response);

			}
		});
	}
	// Look up receipt form handling

	$('#look-up-receipt').on('shown.bs.modal', function() {
		$('#receiving_id').focus();
	});

	$('.look-up-receipt-form').on('submit', function(e) {
		e.preventDefault();

		$('.look-up-receipt-form').ajaxSubmit({
			success: function(response) {
				if (response.success) {
					window.location.href = '<?php echo site_url("receivings/receipt"); ?>/' + response.receiving_id;
				} else {
					$('.look-up-receipt-error').html(response.message);
				}
			},
			dataType: 'json'
		});
	});
	<?php
	if (isset($prompt_convert_sale_to_return) && $prompt_convert_sale_to_return == TRUE) {
	?>

		bootbox.confirm({
			message: <?php echo json_encode(lang("receivings_confirm_convert_sale_to_return")); ?>,
			buttons: {
				confirm: {
					label: <?php echo json_encode(lang('common_yes')) ?>,
					className: 'btn-primary'
				},
				cancel: {
					label: <?php echo json_encode(lang('common_no')) ?>,
					className: 'btn-default'
				}
			},
			callback: function(result) {
				if (result) {
					$.get('<?php echo site_url("receivings/convert_sale_to_return"); ?>', function(response) {
						$("#register_container").html(response);
					});
				}
			}
		});

	<?php
	}
	?>


	<?php
	if (isset($prompt_convert_return_to_sale) && $prompt_convert_return_to_sale == TRUE) {
	?>

		bootbox.confirm({
			message: <?php echo json_encode(lang("receivings_confirm_convert_return_to_sale")); ?>,
			buttons: {
				confirm: {
					label: <?php echo json_encode(lang('common_yes')) ?>,
					className: 'btn-primary'
				},
				cancel: {
					label: <?php echo json_encode(lang('common_no')) ?>,
					className: 'btn-default'
				}
			},
			callback: function(result) {
				if (result) {
					$.get('<?php echo site_url("receivings/convert_return_to_sale"); ?>', function(response) {
						$("#register_container").html(response);
					});
				}
			}
		});

	<?php
	}
	?>
</script>

<?php if ($this->config->item('confirm_error_adding_item') && isset($error)) { ?>
	<script type="text/javascript">
		bootbox.confirm(<?php echo json_encode($error); ?>, function(result) {
			setTimeout(function() {
				$('#item').focus();$("#item_advanced_input").focus();;
			}, 50);
		});
	</script>
<?php } ?>

<script type="text/javascript">
	<?php
	if (isset($error) && !$this->config->item('confirm_error_adding_item')) {
		echo "show_feedback('error', " . json_encode($error) . ", " . json_encode(lang('common_error')) . ");";
	}

	if(isset($vendor_search) && count($vendor_search) > 0){
	?>
		setTimeout(function(){
			var search_item_key = localStorage.getItem('item_search_key');
			if(search_item_key.trim() != ""){

				$("<?php echo $search_from_selector; ?> #item").val(search_item_key);
				bootbox.dialog({
					message: '<?php echo lang("sales_ask_search_in_other_vendors"); ?>',
					size: 'large',
					onEscape: true,
					backdrop: true,
					buttons: {
						<?php
						if(in_array("ig_api_bearer_token", $vendor_search)){
						?>
						api_ig: {
							label: 'Injured Gadgets',
							className: 'btn-info',
							callback: function(){

								$("#item").autocomplete('option', 'source', '<?php echo site_url("home/sync_ig_item_search"); ?>');

								$("#item").autocomplete('option', 'response', 
									function(event, ui){
										$("<?php echo $search_from_selector; ?> .spinner").hide();
										var source_url = $("#item").autocomplete('option', 'source');

										if(ui.content.length == 0 && (source_url.indexOf('receivings/item_search') > -1) && $("<?php echo $search_from_selector; ?> #item").val().trim() != "" ){

										}else if(ui.content.length == 0 && (source_url.indexOf('home/sync_ig_item_search') > -1)){
											var noResult = {
												value:"",
												image:"<?php echo base_url()."assets/img/item.png"; ?>",
												label:"<?php echo lang("sales_no_result_found_ig"); ?>" 
											};
											ui.content.push(noResult);
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}else{
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}
									}
								);

								$("#item").autocomplete('search');
								$("<?php echo $search_from_selector; ?> .spinner").show();

							}
						},
						<?php
						}
						if(in_array("wgp_integration_pkey", $vendor_search)){
						?>
						api_wgp: {
							label: 'WGP',
							className: 'btn-info',
							callback: function(){

								$("#item").autocomplete('option', 'source', '<?php echo site_url("home/sync_wgp_inventory_search"); ?>');

								$("#item").autocomplete('option', 'response', 
									function(event, ui){
										$("<?php echo $search_from_selector; ?> .spinner").hide();
										var source_url = $("#item").autocomplete('option', 'source');

										if(ui.content.length == 0 && (source_url.indexOf('receivings/item_search') > -1) && $("<?php echo $search_from_selector; ?> #item").val().trim() != "" ){

										}else if(ui.content.length == 0 && (source_url.indexOf('home/sync_wgp_inventory_search') > -1)){
											var noResult = {
												value:"",
												image:"<?php echo base_url()."assets/img/item.png"; ?>",
												label:"<?php echo lang("sales_no_result_found_wgp"); ?>" 
											};
											ui.content.push(noResult);
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}else{
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}
									}
								);

								$("#item").autocomplete('search');
								$("<?php echo $search_from_selector; ?> .spinner").show();

							}
						},
						<?php
						}
						?>
						<?php
						if(in_array("p4_api_bearer_token", $vendor_search)){
						?>
						api_p4: {
							label: 'Parts4cells',
							className: 'btn-info',
							callback: function(){

								$("#item").autocomplete('option', 'source', '<?php echo site_url("home/sync_p4_item_search"); ?>');

								$("#item").autocomplete('option', 'response', 
									function(event, ui){
										$("<?php echo $search_from_selector; ?> .spinner").hide();
										var source_url = $("#item").autocomplete('option', 'source');

										if(ui.content.length == 0 && (source_url.indexOf('receivings/item_search') > -1) && $("<?php echo $search_from_selector; ?> #item").val().trim() != "" ){

										}else if(ui.content.length == 0 && (source_url.indexOf('home/sync_p4_item_search') > -1)){
											var noResult = {
												value:"",
												image:"<?php echo base_url()."assets/img/item.png"; ?>",
												label:"<?php echo lang("sales_no_result_found_p4"); ?>" 											};
											ui.content.push(noResult);
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}else{
											$("#item").autocomplete('option', 'source', '<?php echo site_url("receivings/item_search"); ?>');
										}
									}
								);

								$("#item").autocomplete('search');
								$("<?php echo $search_from_selector; ?> .spinner").show();

							}
						},
						<?php
						}
						?>
						cancel: {
							label: '<?php echo lang("common_cancel"); ?>',
							className: 'btn-info',
							callback: function(){
							}
						}
					}
				})
			}
		}, 100);
	<?php
	}


	if (isset($warning)) {
		echo "show_feedback('warning', " . json_encode($warning) . ", " . json_encode(lang('common_warning')) . ");";
	}

	if (isset($success)) {
		if (isset($success_no_message)) {
	?>
			if (ENABLE_SOUNDS) {
				$.playSound(BASE_URL + 'assets/sounds/success');
			}
	<?php
		} else {
			echo "show_feedback('success', " . json_encode($success) . ", " . json_encode(lang('common_success')) . ");";
		}
	}
	?>
</script>


<script type="text/javascript" language="javascript">
	var submitting = false;

	$(document).ready(function() {

		$("#exchange_to").change(function() {
			var rate = $(this).val();
			$.post('<?php echo site_url("receivings/exchange_to"); ?>', {
				'rate': rate
			}, function(response) {
				$("#register_container").html(response);
			});
		});



		$(".pay_store_account_receiving_form").submit(function(e) {
			e.preventDefault();

			var action = $(this).attr('action');
			var is_delete_payment = action.indexOf('delete_store_account') !== -1;

			if (!is_delete_payment) {
				var that = this
				bootbox.prompt({
					title: <?php echo json_encode(lang('common_please_enter_payment_amount')); ?>,
					inputType: 'text',
					value: $(this).data('full-amount'),
					callback: function(amount) {
						if (amount) {
							var new_action = action.replace($(that).data('full-amount'), amount);
							$(that).attr('action', new_action);
							$(that).ajaxSubmit({
								target: "#register_container"
							});
						}
					}
				});
			} else {
				$(this).ajaxSubmit({
					target: "#register_container"
				});
			}
		});

		$('#pay_or_unpay_all').click(function() {
			$("#register_container").load(<?php echo json_encode(site_url('receivings/toggle_pay_all_store_account')); ?>);
		});

		$('#toggle_email_receipt').on('click', function(e) {
			e.preventDefault();
			var checkBoxes = $("#email_receipt");
			checkBoxes.prop("checked", !checkBoxes.prop("checked")).trigger("change");
			$(this).toggleClass('checked');

		})

		$('#email_receipt').change(function(e) {
			e.preventDefault();
			$.post('<?php echo site_url("receivings/set_email_receipt"); ?>', {
				email_receipt: $('#email_receipt').is(':checked') ? '1' : '0'
			});
		});


		$('#change_date_enable').is(':checked') ? $("#change_cart_date_picker").show() : $("#change_cart_date_picker").hide();

		$('#change_date_enable').click(function() {
			if ($(this).is(':checked')) {
				$("#change_cart_date_picker").show();
			} else {
				$("#change_cart_date_picker").hide();
			}
		});

		date_time_picker_field($("#change_cart_date"), JS_DATE_FORMAT + " " + JS_TIME_FORMAT);

		$("#change_cart_date").on("dp.change", function(e) {
			$.post('<?php echo site_url("receivings/set_change_cart_date"); ?>', {
				change_cart_date: $('#change_cart_date').val()
			});
		});

		//Input change
		$("#change_cart_date").change(function() {
			$.post('<?php echo site_url("receivings/set_change_cart_date"); ?>', {
				change_cart_date: $('#change_cart_date').val()
			});
		});

		$('#change_date_enable').change(function() {
			$.post('<?php echo site_url("receivings/set_change_date_enable"); ?>', {
				change_date_enable: $('#change_date_enable').is(':checked') ? '1' : '0'
			});
		});

		//Here just in case the loader doesn't go away for some reason
		$("<?php echo $search_from_selector;?> .ajax-loader").hide();		

		<?php if (!$this->agent->is_mobile()) { ?>
			<?php if (!$this->config->item('auto_focus_on_item_after_sale_and_receiving')) {
			?>
				if (last_focused_id && last_focused_id != 'item') {
					setTimeout(function() {
						$('#' + last_focused_id).focus();
						$('#' + last_focused_id).select();
					}, 10);
				}
			<?php
			} else {
			?>
				setTimeout(function() {
					$('#item').focus();$("#item_advanced_input").focus();;
				}, 10);
			<?php
			}
			?>

			$(document).focusin(function(event) {
				last_focused_id = $(event.target).attr('id');
			});
			<?php } else {
			if ($this->config->item('wireless_scanner_support_focus_on_item_field')) {
			?>
				setTimeout(function() {
					$('#item').focus();$("#item_advanced_input").focus();;
				}, 10);
		<?php
			}
		} ?>



		$("#save-qty-form").submit(function(e) {
			e.preventDefault();
			var item_id = $('.variation-qty-table').data('item-id');

			var query = [];
			$('.variation-qty-table').find('tr.variation-type').each(function() {
				var id = $(this).data('id');
				var qty = $(this).closest('tr').find('input').val();
				if (qty != '0') {
					query.push(qty + '*' + item_id + '#' + id + '|FORCE_ITEM_ID|');
				}
			});

			var secondary_supplier_id = $('input[name="secondary_supplier"]:checked').val();

				if (query.length != 0 && secondary_supplier_id) {
					var variations_qty = JSON.stringify(query);

					var data = {}
					data['items'] = variations_qty;
					data['secondary_supplier_id'] = secondary_supplier_id;

					$.post('<?php echo site_url('receivings/add_variations_qty'); ?>', data, function() {
						$('#var_popup').modal('hide');

						setTimeout(function() {
							$("#register_container").load('<?php echo site_url("receivings/reload"); ?>');
						}, 200);
					});
				}else if(query.length != 0 && !secondary_supplier_id){
				var variations_qty = JSON.stringify(query);

				var data = {}
				data['items'] = variations_qty;

				$.post('<?php echo site_url('receivings/add_variations_qty'); ?>', data, function() {
					$('#var_popup').modal('hide');

					setTimeout(function() {
						$("#register_container").load('<?php echo site_url("receivings/reload"); ?>');
					}, 200);
				});
				}else if(secondary_supplier_id){

				}

		});

		$(document).on('click', '#add_supplier', function(){

			var default_supplier_id = $("#default_supplier_id").val();
			var secondary_supplier_id = $("<?php echo $search_from_selector; ?> .secondary_supplier_id").val();

			if(!default_supplier_id && !secondary_supplier_id){
				$("<?php echo $search_from_selector; ?> .default_supplier_id").val($(".default_supplier_row").find(".default_supplier").val());
			}
			
			$('#var_popup_ss').modal('hide');
			$('#var_popup_ss_1').modal('hide');
			$('#add_item_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit,
				success: itemScannedSuccess
			});
		});

		$('#select_supplier_form,#select_location_form').ajaxForm({
			target: "#register_container",
			beforeSubmit: receivingsBeforeSubmit
		});

		$(document).on("click", ".secondary_supplier_row", function(){
			$(this).find(".secondary_supplier").prop("checked", true);
			$("<?php echo $search_from_selector; ?> .secondary_supplier_id").val($(this).find(".secondary_supplier").val());
			$(".default_supplier_row").find(".default_supplier").prop("checked", false);
			$("<?php echo $search_from_selector; ?> .default_supplier_id").val("");
		});

		$(document).on("click", ".default_supplier_row", function(){
			$(this).find(".default_supplier").prop("checked", true);
			$("<?php echo $search_from_selector; ?> .default_supplier_id").val($(this).find(".default_supplier").val());
			$("<?php echo $search_from_selector; ?> .secondary_supplier_id").val("");

			$(".secondary_supplier_row").each(function(){
				$(this).find(".secondary_supplier").prop("checked", false);
			});
		});

		$(document).on("change", ".secondary_supplier", function(){
			$("<?php echo $search_from_selector; ?> .secondary_supplier_id").val($(this).val());
		});

		var item_variable_key = '';
		<?php if ($this->Employee->has_module_action_permission('receivings', 'allow_item_search_suggestions_for_receivings', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
			$("#item").autocomplete({
				<?php if($this->config->item('advanced_receiving_interface')){ ?>
					source: function(request, response) {
						$(".advanced_search_result").empty()
						if($("#item").val().trim() != ""){
							$('.advanced_search_result_container .spinner').show();
						}else{
							return;
						}

						$.ajax({
							url: '<?php echo site_url("receivings/item_search"); ?>',
							method: 'GET',
							dataType: 'json',
							data: {
								term: request.term,
								<?php if ($mode == 'transfer') { ?>
									mode: 'transfer',
									location_from: '<?php echo isset($location_from) ? $location_from_id : 0; ?>',
									location_to: '<?php echo isset($location) ? $location_id : 0; ?>',
								<?php } ?>
							},
							success: function(data) {
								// Process the response data and display autocomplete results in the autocomplete widget
								response(data);
								// Hide spinner image
								$('.advanced_search_result_container .spinner').hide();
							},
							error: function(xhr, status, error) {
								console.error('Error fetching autocomplete data:', error);
								// Hide spinner image
								$('.advanced_search_result_container .spinner').hide();
							}
						});						
					},
					response: function(event, ui){
						setTimeout(function(){
							$('#add_item_dialog .advanced-item-suggestions').on('click', function() {
								event.preventDefault();
								console.log('check advanced item click');


								let is_variable = $(this).data('variable');
								if(is_variable == 1){//variable block
									let item_value = $(this).data('id');
									$("#a_item_" + item_value).prop('checked', !$("#a_item_" + item_value).prop('checked'));
									let is_checked = $("#a_item_" + item_value).prop('checked');
									let block_list = [];
									var elements = $('[id^="a_item_'+item_value+'V"]');
									// Log the selected elements
									elements.each(function(index, element) {
										let e_id = $(element).attr('id');
										let item_value = e_id.split('a_item_')[1];
										if(is_checked){
											$("#a_item_" + item_value).prop('checked', true);
										}else{
											$("#a_item_" + item_value).prop('checked', false);
										}
										manage_advanced_item_list(item_value, is_checked);
									});
								} else if(is_variable == 2){//variable element
									let item_value = $(this).data('id');
									$("#a_item_" + item_value).prop('checked', !$("#a_item_" + item_value).prop('checked'));
									let is_checked = $("#a_item_" + item_value).prop('checked');
									if(!is_checked){
										let block_item_value = item_value.split('V')[0];
										$("#a_item_" + block_item_value).prop('checked',false);
									}
									manage_advanced_item_list(item_value, is_checked);
								} else {
									let item_value = $(this).data('id');
									$("#a_item_" + item_value).prop('checked', !$("#a_item_" + item_value).prop('checked'));

									let is_checked = $("#a_item_" + item_value).prop('checked');
									manage_advanced_item_list(item_value, is_checked);
								}
							});

							$('#add_item_dialog .advanced-item-suggestions').each(function(index, element){
								let element_id = $(element).data('id');
								if(exist_in_advanced_item_list(element_id)){
									$("#a_item_" + element_id).prop('checked', true);
								}else{
									$("#a_item_" + element_id).prop('checked', false);
								}
							})
						},100);
					},
				<?php } else { ?>
					source: '<?php echo site_url("receivings/item_search"); ?>',
				<?php } ?>
				delay: 500,
				autoFocus: false,
				minLength: 0,

				select: function(event, ui) {
					if(ui.item.value == "") return;
					// if isset secondary suppliers length else 0 
					if (typeof(ui.item.secondary_suppliers) !== "undefined" && typeof(ui.item.secondary_suppliers.length) !== "undefined") {
						var secondary_suppliers_length = ui.item.secondary_suppliers.length;
					} else {
						var secondary_suppliers_length = 0;
					}
					<?php if(!$this->config->item('disable_variation_popup_in_receivings')){?>
						if ( typeof ui.item.attributes != 'undefined' && ui.item.attributes != null && ui.item.secondary_suppliers.length > 0) {
							$('#var-customize').text(ui.item.label);
							$('#var_popup').modal('show');
							console.log(ui);
							$('.variation-qty-table').data('item-id', decodeHtml(ui.item.value).split('#')[0]);
							$.ajax({
								type: "POST",
								url: "<?php echo site_url("receivings/get_item_attr"); ?>",
								data: 'item=' + decodeHtml(ui.item.value) + '|FORCE_ITEM_ID|',
								dataType: "json",
								success: function(data) {
									$('.variation-qty-table tr').not(':first').remove();
									$('.placeholder_supplier_vals .secondary-supplier-table tr').not(':first').remove();
									$.each(data, function(k, v) {
										$('.variation-qty-table tr:last').after('<tr class="variation-type" data-id="' + k + '"><td>' + v + '</td><td><input type="text" class="variation-control form-control input-sm" style="padding-right: 24px;" value="0"></td></tr>');
									});

									$.each(ui.item.default_supplier, function(supplier_key, supplier){
										$('.placeholder_supplier_vals .secondary-supplier-table tr:last').after('<tr class="default_supplier_row" style="cursor:pointer;" data-supplier_id="' + supplier.supplier_id + '"> <td><input class="default_supplier" type="radio" style="display:block;" value="'+supplier.supplier_id+'" name="default_supplier" ></td> <td>'+supplier.company_name+', '+supplier.full_name+'</td> <td>'+parseFloat(supplier.cost_price).toFixed(2)+'</td> <td>'+parseFloat(supplier.unit_price).toFixed(2)+'</td> </tr>');
										$("<?php echo $search_from_selector; ?> .default_supplier_id").val(supplier.supplier_id);
									});

									$(".default_supplier_row").find(".default_supplier").prop("checked", true);

									$.each(ui.item.secondary_suppliers, function(supplier_key, supplier){
										$('.placeholder_supplier_vals .secondary-supplier-table tr:last').after('<tr class="secondary_supplier_row" style="cursor:pointer;" data-supplier_id="' + supplier.supplier_id + '"> <td><input class="secondary_supplier" type="radio" style="display:block;" value="'+supplier.supplier_id+'" name="secondary_supplier" ></td> <td>'+supplier.company_name+', '+supplier.full_name+'</td> <td>'+parseFloat(supplier.cost_price).toFixed(2)+'</td> <td>'+parseFloat(supplier.unit_price).toFixed(2)+'</td></tr>');
									});
								},
								failure: function(errMsg) {
									alert(errMsg);
								}
							});
							return true;
						}else if(typeof ui.item.attributes != 'undefined' && ui.item.attributes != null){
							$('#var-customize').text(ui.item.label);
							$('#var_popup').modal('show');

							$('.variation-qty-table').data('item-id', decodeHtml(ui.item.value).split('#')[0]);
							$.ajax({
								type: "POST",
								url: "<?php echo site_url("receivings/get_item_attr"); ?>",
								data: 'item=' + decodeHtml(ui.item.value) + '|FORCE_ITEM_ID|',
								dataType: "json",
								success: function(data) {
									$('.variation-qty-table tr').not(':first').remove();
									$('.secondary-supplier-table').remove();
									$.each(data, function(k, v) {
										$('.variation-qty-table tr:last').after('<tr class="variation-type" data-id="' + k + '"><td>' + v + '</td><td><input type="text" class="variation-control form-control input-sm" style="padding-right: 24px;" value="0"></td></tr>');
									});
								},
								failure: function(errMsg) {
									alert(errMsg);
								}
							});
							return true;
						}else if(secondary_suppliers_length > 0){
							console.log(ui.item);
							$('#var-customize-ss').text(ui.item.label);
							$('#var_popup_ss').modal('show');
							$('.placeholder_supplier_vals2 .secondary-supplier-table tr').not(':first').remove();

							$.each(ui.item.default_supplier, function(supplier_key, supplier){
								$('.placeholder_supplier_vals2 .secondary-supplier-table tr:last').after('<tr class="default_supplier_row" style="cursor:pointer;" data-supplier_id="' + supplier.supplier_id + '"> <td><input class="default_supplier" type="radio" style="display:block;" value="'+supplier.supplier_id+'" name="default_supplier" ></td> <td>'+supplier.company_name+', '+supplier.full_name+'</td> <td>'+parseFloat(supplier.cost_price).toFixed(2)+'</td> <td>'+parseFloat(supplier.unit_price).toFixed(2)+'</td> </tr>');
								$("<?php echo $search_from_selector; ?> .default_supplier_id").val(supplier.supplier_id);
							});

							$(".default_supplier_row").find(".default_supplier").prop("checked", true);

							$.each(ui.item.secondary_suppliers, function(supplier_key, supplier){
								$('.placeholder_supplier_vals2 .secondary-supplier-table tr:last').after('<tr class="secondary_supplier_row" style="cursor:pointer;" data-supplier_id="' + supplier.supplier_id + '"> <td><input class="secondary_supplier" type="radio" style="display:block;" value="'+supplier.supplier_id+'" name="secondary_supplier" ></td> <td>'+supplier.company_name+', '+supplier.full_name+'</td> <td>'+parseFloat(supplier.cost_price).toFixed(2)+'</td> <td>'+parseFloat(supplier.unit_price).toFixed(2)+'</td> </tr>');
							});

							$("#item").val(decodeHtml(ui.item.value) + '|FORCE_ITEM_ID|');
							return true;
						}
					<?php } ?>
					$("#item").val(decodeHtml(ui.item.value) + '|FORCE_ITEM_ID|');

					$('<?php echo $search_from_selector;?>').ajaxSubmit({
						target: "#register_container",
						beforeSubmit: receivingsBeforeSubmit,
						success: itemScannedSuccess
					});
				},
			}).data("ui-autocomplete")._renderItem = function(ul, item) {
				<?php if($this->config->item('advanced_receiving_interface')){ ?>
					let variable_id = "";
					let advanced_item_value = item.value;
					if(advanced_item_value.indexOf(' ') > -1){
						advanced_item_value = advanced_item_value.toUpperCase();
						advanced_item_value = advanced_item_value.toString().replace(' ', '_');
					}
					if(advanced_item_value.indexOf('#') > -1){
						console.log(advanced_item_value);

						variable_id = advanced_item_value.split("#")[0];
						advanced_item_value = advanced_item_value.toUpperCase();
						advanced_item_value = advanced_item_value.toString().replace('#', 'V');
					}

					let item_record = $("<div class='advanced-item-suggestions' data-variable='"+ (variable_id ?'2':'0') +"' data-id='"+advanced_item_value+"'></div>")
						.data("item.autocomplete", item)
						.append(
							'<div class="advanced-item-suggestions-container" style="'+ (variable_id?'padding-left:50px;':'') +'">' +
								'<div class="advanced-item-check">' +
									'<input type="checkbox" id="a_item_'+ advanced_item_value +'">' +
									'<label for="a_item_'+ advanced_item_value +'"><span></span>' +
								'</div>' +
								'<div class="advanced-item-image">' +
									'<img style="width:50px; margin:10px;" src="' + item.image + '" alt="">' +
								'</div>' +
								'<div class="advanced-item-details">' +
									'<div class="name">' +
										decodeHtml(item.label) +
									'</div>' +
									'<div class="attributes-container">' +
										'<span class="attributes">' + '<?php echo lang("common_category"); ?>' + ' : <span class="value">' + (item.category ? item.category : <?php echo json_encode(lang('common_none')); ?>) + '</span></span>' +
										<?php if ($this->Employee->has_module_action_permission('items', 'see_item_quantity', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
											<?php if($mode == 'transfer'){ ?>
												(typeof item.location_from_qty
												!== 'undefined' && item.location_from_qty
												!== null ? '<span class="attributes">' + '<?php echo lang('receivings_transfer_from').' '.lang("common_quantity"); ?>' + ' <span class="value">' + item.location_from_qty
												+ '</span></span>' : '') +
												(typeof item.location_to_qty
												!== 'undefined' && item.location_to_qty
												!== null ? '<span class="attributes">' + '<?php echo lang('receivings_transfer_to').' '.lang("common_quantity"); ?>' + ' <span class="value">' + item.location_to_qty
												+ '</span></span>' : '') +
											<?php } else{ ?>
												(typeof item.quantity !== 'undefined' && item.quantity !== null ? '<span class="attributes">' + '<?php echo lang("common_quantity"); ?>' + ' <span class="value">' + item.quantity + '</span></span>' : '') +
											<?php } ?>
										<?php } ?>
										(item.attributes ? '<span class="attributes">' + '<?php echo lang("common_attributes"); ?>' + ' : <span class="value">' + item.attributes + '</span></span>' : '') +
										'<?php if(!$this->config->item('hide_supplier_in_item_search_result')){ ?>'+
											(item.supplier_name ? '<span class="attributes">' + '<?php echo lang("common_supplier"); ?>' + ' : <span class="value">' + item.supplier_name + '</span></span>' : '') +
										'<?php } ?>'+
									'</div>' + 
								'</div>' + 
							'</div>');
						if(variable_id){
							if(variable_id != item_variable_key){
								let variable_label = item.label.slice(0, item.label.lastIndexOf(' - '));
								let item_variation = $("<div class='advanced-item-suggestions' id='advanced-item-v-"+variable_id+"' data-variable='"+ (variable_id ?'1':'0') +"' data-id='"+variable_id+"'></div>")
								.data("item.autocomplete", item)
								.append(
									'<div class="advanced-item-suggestions-container">' +
										'<div class="advanced-item-check">' +
											'<input type="checkbox" id="a_item_'+ variable_id +'">' +
											'<label for="a_item_'+ variable_id +'"><span></span>' +
										'</div>' +
										'<div class="advanced-item-image">' +
											'<img style="width:50px; margin:10px;" src="' + item.image + '" alt="">' +
										'</div>' +
										'<div class="advanced-item-details">' +
											'<div class="name">' +
												decodeHtml(variable_label) +
											'</div>' +
										'</div>' + 
									'</div>')
									.appendTo($(".advanced_search_result"));
							}
							item_variable_key = variable_id;
						}else{
							item_variable_key = '';
						}
						return item_record.appendTo($(".advanced_search_result"));

				<?php }else{ ?>
					return $("<li class='item-suggestions'></li>")
						.data("item.autocomplete", item)
						.append('<a class="suggest-item" data-value="' + item.value + '" data-attributes="' + item.attributes + '"><div class="item-image">' +
							'<img src="' + item.image + '" alt="">' +
							'</div>' +
							'<div class="details">' +
							'<div class="name">' +
							decodeHtml(item.label) +
							'</div>' +
							'<span class="attributes">' + '<?php echo lang("common_category"); ?>' + ' : <span class="value">' + (item.category ? item.category : <?php echo json_encode(lang('common_none')); ?>) + '</span></span>' +
							<?php if ($this->Employee->has_module_action_permission('items', 'see_item_quantity', $this->Employee->get_logged_in_employee_info()->person_id)) { ?>
								(typeof item.quantity !== 'undefined' && item.quantity !== null ? '<span class="attributes">' + '<?php echo lang("common_quantity"); ?>' + ' <span class="value">' + item.quantity + '</span></span>' : '') +
							<?php } ?>
							(item.attributes ? '<span class="attributes">' + '<?php echo lang("common_attributes"); ?>' + ' : <span class="value">' + item.attributes + '</span></span>' : '') +
							'<?php if(!$this->config->item('hide_supplier_in_item_search_result')){ ?>'+
							(item.supplier_name ? '<span class="attributes">' + '<?php echo lang("common_supplier"); ?>' + ' : <span class="value">' + item.supplier_name + '</span></span>' : '') +
							'<?php } ?>'+
							'</div>')
						.appendTo(ul);
				<?php } ?>
			};
		<?php } ?>
		// if #mode is changed
		$('.change-mode').click(function(e) {
			e.preventDefault();
			if ($(this).data('mode') == "store_account_payment") { // Hiding the category grid
				$('#show_hide_grid_wrapper, #category_item_selection_wrapper').fadeOut();
			} else { // otherwise, show the categories grid
				$('#show_hide_grid_wrapper, #show_grid').fadeIn();
				$('#hide_grid').fadeOut();
			}
			$.post('<?php echo site_url("receivings/change_mode"); ?>', {
				mode: $(this).data('mode')
			}, function(response) {
				$("#register_container").html(response);
			});
		});


		//make username editable
		$('.xeditable').editable({
			validate: function(value) {
				if ($.isNumeric(value) == '' && $(this).data('validate-number')) {
					return <?php echo json_encode(lang('common_only_numbers_allowed')); ?>;
				}
			},
			success: function(response, newValue) {
				last_focused_id = $(this).attr('id');
				$("#register_container").html(response);
			},
			savenochange: true
		});

		$(".expire_date").editable({
			validate: function(value) {
				if (!value) {
					return <?php echo json_encode(lang('receivings_invalid_date')); ?>;
				}
			},
			combodate: {
				maxYear: <?php echo date("Y") + 20; ?>,
				minYear: <?php echo date("Y"); ?>,
			},
			success: function(response, newValue) {
				last_focused_id = $(this).attr('id');
				$("#register_container").html(response);
			}
		});

		$('.xeditable').on('shown', function(e, editable) {

			$(this).closest('.table-responsive').css('overflow-x', 'hidden');

			editable.input.postrender = function() {
				//Set timeout needed when calling price_to_change.editable('show') (Not sure why)
				setTimeout(function() {
					editable.input.$input.select();
				}, 200);
			};
		});

		$('.xeditable').on('hidden', function(e, editable) {
			$(this).closest('.table-responsive').css('overflow-x', 'auto');
		});


		$('.xeditable').on('hidden', function(e, editable) {
			last_focused_id = $(this).attr('id');
			$('#' + last_focused_id).focus();
			$('#' + last_focused_id).select();
		});

		<?php if (isset($cart_count)) { ?>
			$('.cart-number').html(<?php echo $cart_count; ?>);
		<?php } ?>

		$('#location').change(function() {
			$('#select_location_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});

		$('#location_from').change(function() {
			$('#select_location_from_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});

		// Select Location 
		<?php if ($mode == "transfer" and !isset($location)) { ?>


			$("#location").autocomplete({
				source: '<?php echo site_url("receivings/location_search"); ?>',
				delay: 500,
				autoFocus: false,
				minLength: 0,
				select: function(event, ui) {
					$.post('<?php echo site_url("receivings/select_location"); ?>', {
						location: decodeHtml(ui.item.value)
					}, function(response) {
						$("#register_container").html(response);
					});
				},
			}).data("ui-autocomplete")._renderItem = function(ul, item) {
				return $("<li class='customer-badge suggestions'></li>")
					.data("item.autocomplete", item)
					.append('<a class="suggest-item location-suggest"><div class="avatar">' +
						'<span class="badge" style="background-color:' + item.color + '">&nbsp;</span>' +
						'</div>' +
						'<div class="details">' +
						'<div class="name">' +
						item.label +
						'</div>' +
						'</div></a>')
					.appendTo(ul);

			};
		<?php } ?>

		// Select Location From
		<?php if ($mode == "transfer" and !isset($location_from)) { ?>


			$("#location_from").autocomplete({
				source: '<?php echo site_url("receivings/location_search"); ?>',
				delay: 500,
				autoFocus: false,
				minLength: 0,
				select: function(event, ui) {
					$.post('<?php echo site_url("receivings/select_location_from"); ?>', {
						location_from: decodeHtml(ui.item.value)
					}, function(response) {
						$("#register_container").html(response);
					});
				},
			}).data("ui-autocomplete")._renderItem = function(ul, item) {
				return $("<li class='customer-badge suggestions'></li>")
					.data("item.autocomplete", item)
					.append('<a class="suggest-item location-suggest"><div class="avatar">' +
						'<span class="badge" style="background-color:' + item.color + '">&nbsp;</span>' +
						'</div>' +
						'<div class="details">' +
						'<div class="name">' +
						item.label +
						'</div>' +
						'</div></a>')
					.appendTo(ul);

			};
		<?php } ?>



		$('#location_from_from').change(function() {
			$('#select_location_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});

		// Select Location 
		<?php if ($mode == "transfer" and !isset($location)) { ?>


			$("#location").autocomplete({
				source: '<?php echo site_url("receivings/location_search"); ?>',
				delay: 500,
				autoFocus: false,
				minLength: 0,
				select: function(event, ui) {
					$.post('<?php echo site_url("receivings/select_location"); ?>', {
						location: decodeHtml(ui.item.value)
					}, function(response) {
						$("#register_container").html(response);
					});
				},
			}).data("ui-autocomplete")._renderItem = function(ul, item) {
				return $("<li class='customer-badge suggestions'></li>")
					.data("item.autocomplete", item)
					.append('<a class="suggest-item location-suggest"><div class="avatar">' +
						'<span class="badge" style="background-color:' + item.color + '">&nbsp;</span>' +
						'</div>' +
						'<div class="details">' +
						'<div class="name">' +
						item.label +
						'</div>' +
						'</div></a>')
					.appendTo(ul);

			};
		<?php } ?>


		// Select Supplier 
		<?php if ($mode != "transfer" and !isset($supplier)) { ?>


			<?php
			if ($this->Employee->has_module_action_permission('receivings', 'allow_supplier_search_suggestions_for_suppliers', $this->Employee->get_logged_in_employee_info()->person_id)) {
			?>

				$("#supplier").autocomplete({
					source: '<?php echo site_url("receivings/supplier_search"); ?>',
					delay: 500,
					autoFocus: false,
					minLength: 0,
					select: function(event, ui) {
						$.post('<?php echo site_url("receivings/select_supplier"); ?>', {
							supplier: decodeHtml(ui.item.value) + "|FORCE_PERSON_ID|"
						}, function(response) {
							$("#register_container").html(response);
						});
					},
				}).data("ui-autocomplete")._renderItem = function(ul, item) {
					return $("<li class='customer-badge suggestions'></li>")
						.data("item.autocomplete", item)
						.append('<a class="suggest-item"><div class="avatar">' +
							'<img src="' + item.avatar + '" alt="">' +
							'</div>' +
							'<div class="details">' +
							'<div class="name">' +
							item.label +
							'</div>' +
							'<span class="email">' +
							item.subtitle +
							'</span>' +
							'</div></a>')
						.appendTo(ul);

				};
			<?php } ?>
		<?php } ?>



		//Add payment to the sale 
		$("#add_payment_button").click(function(e) {
			e.preventDefault();

			if (noPaymentSelected()) {
				return false;
			}

			$('#add_payment_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});
		
		 <?php
		if($this->config->item('advanced_receiving_interface'))
		{
		?>
		        $('#add_item_form').submit(function(e)
		        {
		                event.preventDefault();
		        });
		<?php
		}
		?>


		$('#select_supplier_form').bind('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$('#select_supplier_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: receivingsBeforeSubmit
				});
			}
		});

		$('#select_location_form').bind('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$('#select_location_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: receivingsBeforeSubmit
				});
			}
		});

		$('#select_location_from_form').bind('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$('#select_location_from_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: receivingsBeforeSubmit
				});
			}
		});


		$('<?php echo $search_from_selector;?>').ajaxForm({
			target: "#register_container",
			beforeSubmit: receivingsBeforeSubmit,
			success: itemScannedSuccess
		});

		$('<?php echo $search_from_selector;?>').bind('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				localStorage.setItem('item_search_key', $("<?php echo $search_from_selector;?> #item").val());
				$('<?php echo $search_from_selector;?>').ajaxSubmit({
					<?php if(!$this->config->item('advanced_receiving_interface')){ ?>
					target: "#register_container",
					<?php } ?>
					beforeSubmit: receivingsBeforeSubmit,
					success: itemScannedSuccess
				});
			}
		});

		//Add payment to the sale when hit enter on amount tendered input
		$('#amount_tendered').bind('keypress', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();

				//Quick complete possible
				if ($("#finish_sale_alternate_button").is(":visible")) {
					if (noPaymentSelected()) {
						return false;
					}

					$('#add_payment_form').ajaxSubmit({
						target: "#register_container",
						beforeSubmit: receivingsBeforeSubmit,
						complete: function() {
							$('#finish_sale_button').trigger('click');
						}
					});
				} else {
					if (noPaymentSelected()) {
						return false;
					}

					$('#add_payment_form').ajaxSubmit({
						target: "#register_container",
						beforeSubmit: receivingsBeforeSubmit
					});
				}
			}
		});

		//Select all text in the input when input is clicked
		$("input:text, textarea").not(".description,#comment,#internal_notes").click(function() {
			$(this).select();
		});

		<?php if (!$this->config->item('disable_quick_complete_sale')) { ?>

			if ((<?php echo $amount_due; ?> >= 0 && $('#amount_tendered').val() >= <?php echo $amount_due; ?>) || (<?php echo $amount_due; ?> < 0 && $('#amount_tendered').val() <= <?php echo $amount_due; ?>)) {
				$('#finish_sale_alternate_button').removeClass('hidden');
				$('#add_payment_button').addClass('hidden');
			} else {
				$('#finish_sale_alternate_button').addClass('hidden');
				$('#add_payment_button').removeClass('hidden');
			}


			$('#amount_tendered').on('input', function() {
				if ((<?php echo $amount_due; ?> >= 0 && $('#amount_tendered').val() >= <?php echo $amount_due; ?>) || (<?php echo $amount_due; ?> < 0 && $('#amount_tendered').val() <= <?php echo $amount_due; ?>)) {
					$('#finish_sale_alternate_button').removeClass('hidden');
					$('#add_payment_button').addClass('hidden');
				} else {
					$('#finish_sale_alternate_button').addClass('hidden');
					$('#add_payment_button').removeClass('hidden');
				}

			});

			$('#finish_sale_alternate_button').on('click', function(e) {
				e.preventDefault();

				if (noPaymentSelected()) {
					return false;
				}

				$('#add_payment_form').ajaxSubmit({
					target: "#register_container",
					beforeSubmit: receivingsBeforeSubmit,
					complete: function() {
						$('#finish_sale_button').trigger('click');
					}
				});
			});

		<?php } ?>

		// Show or hide item grid
		$("#show_grid, .show-grid").on('click', function(e) {
			e.preventDefault();
			$("#category_item_selection_wrapper").slideDown();

			$('.show-grid').addClass('hidden');
			$('.hide-grid').removeClass('hidden');
		});

		$("#hide_grid,#hide_grid_top, .hide-grid").on('click', function(e) {
			e.preventDefault();
			$("#category_item_selection_wrapper").slideUp();

			$('.hide-grid').addClass('hidden');
			$('.show-grid').removeClass('hidden');
		});


		$("#cart_contents input").change(function() {
			$(this.form).ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});

		$('#item,#supplier,#location').click(function() {
			$(this).attr('value', '');
		});

		$('#mode').change(function() {
			$('#mode_form').ajaxSubmit({
				target: "#register_container",
				beforeSubmit: receivingsBeforeSubmit
			});
		});

		$('#comment').change(function() {
			$.post('<?php echo site_url("receivings/set_comment"); ?>', {
				comment: $('#comment').val()
			});
		});
		
		$('#create_invoice').change(function()
		{
			$.post('<?php echo site_url("receivings/set_create_invoice");?>', {create_invoice:$('#create_invoice').is(':checked') ? '1' : '0'});
		});
		

		$('#internal_notes').change(function() {
			$.post('<?php echo site_url("receivings/set_internal_notes"); ?>', {
				internal_notes: $('#internal_notes').val()
			});
		});

		<?php if (!$is_po) { ?>
			$("#finish_sale_form").submit(function() {
				<?php if ($mode == "transfer" and !isset($location)) { ?>
					bootbox.alert(<?php echo json_encode(lang("receivings_location_required")); ?>);
					$('#location').focus();
					return;
				<?php } ?>

				var finishForm = this;

				<?php if (!$this->config->item('disable_confirm_recv')) { ?>

					bootbox.confirm(<?php echo json_encode(lang("receivings_confirm_finish_receiving")); ?>, function(result) {
						if (result) {
							//Prevent double submission of form
							$("#finish_sale_button").hide();
							finishForm.submit();
						}
					});
					return false;
				<?php } ?>
			});
			$("#finish_sale_button_transfer_request").click(function(e) {
				e.preventDefault();

				bootbox.confirm(<?php echo json_encode(lang("receivings_confirm_finish_receiving_transfer_request")); ?>, function(result) {
					if (result) {
						doSuspendRecvTransferRequest();
					}
				})

			});

			$("#finish_sale_button").click(function(e) {
				e.preventDefault();

				if ($("#comment").val()) {
					$.post('<?php echo site_url("receivings/set_comment"); ?>', {
						comment: $('#comment').val()
					}, function() {
						$('#finish_sale_form').submit();
					});
				} else {
					$('#finish_sale_form').submit();
				}


			});
		<?php } ?>
		$("#cancel_sale_button").click(function(e) {
			e.preventDefault();
			bootbox.confirm(<?php echo json_encode(lang("receivings_confirm_cancel_receiving")); ?>, function(result) {
				if (result) {
					$('#cancel_sale_form').ajaxSubmit({
						target: "#register_container",
						beforeSubmit: receivingsBeforeSubmit
					});
				}
			});
		});

		//Select Payment
		$('.select-payment').on('click', selectPayment);

		$('.delete-item, .delete-payment, #delete_supplier, #delete_location,#delete_location_from').click(function(event) {
			event.preventDefault();
			$("#register_container").load($(this).attr('href'));
		});

		$('.delete-tax').click(function(event) {
			event.preventDefault();
			var $that = $(this);
			bootbox.confirm(<?php echo json_encode(lang("common_confirm_sale_tax_delete")); ?>, function(result) {
				if (result) {
					$("#register_container").load($that.attr('href'));
				}
			});
		});


		$("input[type=text]").click(function() {
			$(this).select();
		});

		$("#suspend_recv_button<?php echo $is_po ? ', #finish_sale_button' : ''; ?>").click(function(e) {
			e.preventDefault();
			bootbox.confirm(<?php echo json_encode(lang("receivings_confim_suspend_recv")); ?>, function(result) {
				if (result) {
					if ($("#comment").val()) {
						$.post('<?php echo site_url("receivings/set_comment"); ?>', {
							comment: $('#comment').val()
						}, function() {
							doSuspendRecv();
						});
					} else {
						doSuspendRecv();
					}
				}
			});
		});

		$('.fullscreen').on('click', function(e) {
			e.preventDefault();
			salesRecvFullScreen();
			$.get('<?php echo site_url("home/set_fullscreen/1"); ?>');
		});

		$('.dismissfullscreen').on('click', function(e) {
			e.preventDefault();
			salesRecvDismissFullscren();
			$.get('<?php echo site_url("home/set_fullscreen/0"); ?>');
		});
	});

	function doSuspendRecv() {
		<?php if (!$is_po) { ?>
			<?php if ($this->config->item('show_receipt_after_suspending_sale')) { ?>
				window.location = '<?php echo site_url("receivings/suspend"); ?>';
			<?php } else { ?>
				$("#register_container").load('<?php echo site_url("receivings/suspend"); ?>');
			<?php } ?>
		<?php
		} else {
		?>
			window.location = '<?php echo site_url("receivings/suspend"); ?>';
		<?php
		}
		?>

	}

	function doSuspendRecvTransferRequest() {
		$("#register_container").load('<?php echo site_url("receivings/suspend/2"); ?>');
	}

	function receivingsBeforeSubmit(formData, jqForm, options) {
		if (submitting) {
			return false;
		}
		submitting = true;

		$('.cart-number').html(<?php echo $cart_count; ?>);
		$("<?php echo $search_from_selector;?> .ajax-loader").show();
		$("#finish_sale_button").hide();
	}

	function itemScannedSuccess(responseText, statusText, xhr, $form) {
		<?php if($this->config->item('advanced_receiving_interface')){ ?>
			close_advanced_item_list();
			setTimeout(function() {
				$('#item_advanced_input').focus();
				$("#register_container").html(responseText);
			}, 1000, responseText);
		<?php } else{ ?>
			setTimeout(function() {
				$('#item').focus();$("#item_advanced_input").focus();;
			}, 10);
		<?php } ?>
	}

	function checkPaymentTypes() {
		var paymentType = $("#payment_types").val();
		switch (paymentType) {
			case <?php echo json_encode(lang('common_cash')); ?>:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter') . ' ' . lang('common_cash') . ' ' . lang('common_amount')); ?>);
				break;
			case <?php echo json_encode(lang('common_check')); ?>:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter') . ' ' . lang('common_check') . ' ' . lang('common_amount')); ?>);
				break;
			case <?php echo json_encode(lang('common_debit')); ?>:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter') . ' ' . lang('common_debit') . ' ' . lang('common_amount')); ?>);
				break;
			case <?php echo json_encode(lang('common_credit')); ?>:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter') . ' ' . lang('common_credit') . ' ' . lang('common_amount')); ?>);
				break;
			case <?php echo json_encode(lang('common_store_account')); ?>:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter') . ' ' . lang('common_store_account') . ' ' . lang('common_amount')); ?>);
				break;
			default:
				$("#amount_tendered").val(<?php echo json_encode(to_currency_no_money($amount_due)); ?>);
				$("#amount_tendered").attr('placeholder', <?php echo json_encode(lang('common_enter')); ?> + ' ' + paymentType + ' ' + <?php echo json_encode(lang('common_amount')); ?>);
		}
	}

	function selectPayment(e) {
		e.preventDefault();
		$.post('<?php echo site_url("receivings/set_selected_payment"); ?>', {
			payment: $(this).data('payment')
		});
		$('#payment_types').val($(this).data('payment'));
		$('.select-payment').removeClass('active');
		$(this).addClass('active');
		$("#amount_tendered").focus();
		$("#amount_tendered").select();
		$("#amount_tendered").attr('placeholder', '');

		checkPaymentTypes();
		
		if ($(this).data('payment') == <?php echo json_encode(lang('common_store_account')) ?>)
		{
			$("#create_invoice_holder").removeClass('hidden');
		}
		else
		{
			$("#create_invoice_holder").addClass('hidden');
		}
		
	}

	checkPaymentTypes();

	$(".delete-custom-image-recv a").click(function(e) {
		e.preventDefault();
		var $that = $(this);
		bootbox.confirm(CONFIRM_IMAGE_DELETE, function(result) {
			if (result) {
				$.get($that.attr('href'), function() {
					//face out image and link
					$that.parent().fadeOut();
					$that.parent().prev().fadeOut();
				});
			}
		});
	});

	function noPaymentSelected() {
		var no_payment = $(".select-payment.active").length == 0;
		if (no_payment) {
			bootbox.alert(<?php echo json_encode(lang('common_must_select_payment')); ?>);
		}
		return no_payment
	}


	<?php
	if (isset($quantity_set) && $quantity_set) {
	?>
		var quantity_to_change = $('#register a[data-name="quantity"]').first();
		quantity_to_change.editable('show');
	<?php
	}
	?>

	$("#sale_details_expand_collapse").click(function() {
		$('.register-item-bottom').toggleClass('collapse');
		$('.register-item-custom-fields').toggleClass('collapse');

		if ($('.register-item-bottom').hasClass('collapse')) {
			$.post('<?php echo site_url("receivings/set_details_collapsed"); ?>', {
				value: '1'
			});
			$("#sale_details_expand_collapse").text('+');
			$(".show-collpased").show();
			
		} else {
			$.post('<?php echo site_url("receivings/set_details_collapsed"); ?>', {
				value: '0'
			});
			$("#sale_details_expand_collapse").text('-');
			$(".show-collpased").hide();
			
		}
	});

	$(".line_item_custom_fields").on('click', function(){
		$(this).next().toggleClass('collapse');
		if($(this).next().hasClass('collapse')){
			$(this).find('i').addClass('ion-android-add');
			$(this).find('i').removeClass('ion-minus');
		}else{
			$(this).find('i').addClass('ion-minus');
			$(this).find('i').removeClass('ion-android-add');
		}
	});

	<?php if ($details_collapsed) { ?>
		$("#sale_details_expand_collapse").text('+');
		$('.register-item-bottom').addClass('collapse');
		$(".show-collpased").show();
	<?php } ?>

	$(".page_pagination a").click(function(e) {
		e.preventDefault();
		$("#register_container").load($(this).attr('href'));
	});

	<?php 
	$denominations = $this->Register->get_register_currency_denominations()->result();

	$bills = array();
	foreach($denominations as $denom){
		if($denom->value >= 1 && count($bills) <= 8){
			$bills[] = $denom->value;
		}
	}

	sort($bills);
	?>

	var $bills = <?php echo json_encode($bills, JSON_NUMERIC_CHECK); ?>;

	<?php if(count($bills) > 0) { ?>

	$(".btn-pay").dblclick(function(){
		var $currency_symbol = "<?php echo $this->config->item('currency_symbol'); ?>";
		var $amount_tendered = $("#amount_tendered").val();


		var $possible_amount  = get_possible_amount($amount_tendered, $bills);


		var $html = '';

		$.each($possible_amount, function($index, $value) {
	
			$html += '<div class="col-md-3" style="margin-bottom:15px;">';
				$html += '<button tabindex="'+($index)+'" class="btn btn-primary btn-block quick_amount" data-quick_amount="'+$value+'.00" style="height:50px; border-radius:0px; font-size:16px; font-weight:bold;">'+$currency_symbol+''+$value+'.00</button>';
			$html += '</div>';

		});

		$("#quick_cash_holder").html($html);
		
		$("#choose_quick_cash").modal("show");
	});

	<?php }?>
	var get_possible_amount = function($sales_amount, $bills) {
		
		var $found_amount, $get_extra, $key, $bill, $current_bill, $previous_bill, $qutnt, $mod, $quotient, $new_extra_amount, $possible_amount_using_this_bill;
		
		$sales_amount = Math.ceil($sales_amount);
		
		$found_amount = [$sales_amount];
		
		$get_extra = [];
		
		for ($key in $bills) { $bill = $bills[$key];
			if($key == 0){
				$get_extra.push(0);
				continue;
				}else{
				$current_bill = $bill;
				$previous_bill = $bills[$key-1];
				
				$qutnt = $current_bill/$previous_bill;
				
				$mod = $current_bill%$previous_bill;
				
				if($mod != 0){
					$get_extra.push($previous_bill * Math.ceil($qutnt));
					}else{
					$get_extra.push(0);
				}
			}
		}
		
		for ($key in $bills) { $bill = $bills[$key];
			$quotient = $sales_amount / $bill;
			
			if($sales_amount % $bill == 0){
				$new_extra_amount = ($sales_amount-$bill)+$get_extra[$key];
				if($new_extra_amount >= $sales_amount && !inArray($new_extra_amount, $found_amount)){
					$found_amount.push($new_extra_amount);
				}
			}
			
			$possible_amount_using_this_bill = $bill * Math.ceil($quotient);
			
			if (inArray($possible_amount_using_this_bill, $found_amount)) {
				continue;
			}
			
			if (isNaN($possible_amount_using_this_bill))
			{
				continue;
			}
			
			
			$found_amount.push($possible_amount_using_this_bill);
		}

		$found_amount.sort();

		return $found_amount.sort(function(a, b){return a-b});
	
	}


	function inArray(needle, haystack) {
		var length = haystack.length;
		for(var i = 0; i < length; i++) {
			if(haystack[i] == needle) return true;
		}
		return false;
	}

	$('#choose_quick_cash').on('shown.bs.modal', function (e) {
		$("#custom_amount").focus();
	});

	$(document).on('click', '.quick_amount', function(){
		var amount_tendered = $(this).data("quick_amount");
		$("#amount_tendered").val(amount_tendered);
		$('#choose_quick_cash').modal('hide');
		$("#finish_sale_alternate_button").trigger('click');
		$("#finish_sale_button").trigger('click');
	});

	$(document).on('keyup', '#custom_amount', function(){
		var amount_tendered = $(this).val();
		$("#collect_amount").data("quick_amount", amount_tendered);
	});

	$(window).keydown(function(event) {
		if( event.ctrlKey && event.which == 81 ) { 
			$('.btn-pay').trigger("dblclick");
			event.preventDefault(); 
		}

		if($("#custom_amount").focus() && $("#custom_amount").val() > 0  && event.which == 13 ) { 
			$('#collect_amount').trigger("click");
			event.preventDefault(); 
		}
	});

</script>

<script>
	$(document).ready(function() {
		
		<?php if ($this->config->item('allow_drag_drop_recv') && !$this->agent->is_mobile() && !$this->agent->is_tablet()) {  ?>

			$("#register th").on("click", function(){
				var column = ""
				if($(this).hasClass("item_name_heading")){
					column = "name";
				}else if($(this).hasClass("sales_price")){
					column = "price";
				}else if($(this).hasClass("sales_quantity")){
					column = "quantity";
				}else if($(this).hasClass("sales_discount")){
					column = "discount";
				}else if($(this).hasClass("sales_total")){
					column = "total";
				}
				if(column == '') return;

				var type = "asc";
				if($(this).hasClass("ion-arrow-down-b")){
					type = "desc";
					$("#register th").removeClass("ion-arrow-down-b");
					$("#register th").removeClass("ion-arrow-up-b");
					$(this).addClass("ion-arrow-up-b");
				}else if($(this).hasClass("ion-arrow-up-b")){
					type = "asc";
					$("#register th").removeClass("ion-arrow-down-b");
					$("#register th").removeClass("ion-arrow-up-b");
					$(this).addClass("ion-arrow-down-b");
				}else{
					type = "asc";
					$("#register th").removeClass("ion-arrow-down-b");
					$("#register th").removeClass("ion-arrow-up-b");
					$(this).addClass("ion-arrow-down-b");
				}
				$('#grid-loader2').show();
				$.post('<?php echo site_url("receivings/sort"); ?>', {
						sort_column: column,
						sort_type: type,
				}, function(response) {
					$('#grid-loader2').hide();
					$("#register_container").html(response);
				});			
			})

			$(function () {
			var start_pos = 0;
			if($("#register tbody").length > 1){
				$("#register").sortable({
					items: 'tbody',
					cursor: 'pointer',
					axis: 'y',
					dropOnEmpty: false,
					start: function (e, ui) {
						ui.item.addClass("selected");
						var td_width = [];
						var td_height = [];
						for( let i = 0; i < $("#register tbody").length; i ++){
							if($($("#register tbody")[i]).hasClass('selected') || $($("#register tbody")[i]).hasClass('ui-sortable-placeholder')){
								continue;
							}else{
								td_height = $($("#register tbody")[i]).height();
								for(let j = 0; j<$($("#register tbody")[i]).find(".register-item-details td").length; j++){
									td_width.push($($($("#register tbody")[i]).find(".register-item-details td")[j]).width());
								}
								break;
							}
						}
						$(".ui-sortable-placeholder").html("<tr><td>&nbsp;</td></tr>");
						$(".ui-sortable-placeholder").height(td_height+'px');
						for(let k=0; k<$($("#register tbody.selected tr")[0]).find('td').length; k++){
							$($($("#register tbody.selected tr")[0]).find('td')[k]).width(td_width[k]+'px');
						}
						start_pos = $("#register tbody.selected").parent().children().index($("#register tbody.selected"));
					},
					stop: function (e, ui) {

						let current_pos = $("#register tbody.selected").parent().children().index($("#register tbody.selected"));
						var drop_index = 0;
						if(current_pos < start_pos) // up
						{
							drop_index = $("#register tbody.selected").next().data('line');
						}else if(current_pos > start_pos){ // dwon
							drop_index = $("#register tbody.selected").prev().data('line');
						}else{
							return;
						}

						var drag_index = $("#register tbody.selected").data('line');

						for(let k=0; k<$($("#register tbody.selected tr")[0]).find('td').length; k++){
							$($($("#register tbody.selected tr")[0]).find('td')[k]).attr('style','');
						}						
						ui.item.removeClass("selected");
						$("#register th").removeClass("ion-arrow-down-b");
						$("#register th").removeClass("ion-arrow-up-b");

						if(drag_index != drop_index){
							$('#grid-loader2').show();
							$.post('<?php echo site_url("receivings/sort"); ?>', {
								'drag_index': drag_index,
								'drop_index': drop_index,
								'sort_column': 'drag_drop',
							}, function(response) {
								$('#grid-loader2').hide();
								$("#register_container").html(response);
							});			
						}
					},
					sort:function(e){
						$(".ui-sortable-helper").css("width", $("table#register").width()+'px');
						$(".ui-sortable-helper tr").css("width", $("table#register").width()+'px');
					}
				});
			}
        });	
		<?php } ?>	
	});
	
	<?php
	if (isset($async_inventory_updates) && $async_inventory_updates && $_SESSION['do_async_inventory_updates'])
	{
		if (!empty($_SESSION['async_inventory_updates']))
		{
			?>
			$.get(<?php echo json_encode(site_url('home/async_external_inventory_updates')); ?>);
			<?php
		}
	
		unset($_SESSION['do_async_inventory_updates']);
	}
	?>
	
	<?php if($this->config->item('advanced_receiving_interface')){ ?>

		var advanced_selected_list = [];

		$("#item_advanced_input").on('keydown', function(){
			$('#advanced_receiving_interface').modal('show');
			clear_advanced_item_list();
		});

		$('#advanced_receiving_interface').on('shown.bs.modal', function() {
			$('#item').val($('#item_advanced_input').val());
			$('#item').focus();
			$('#item_advanced_input').val('');
			$('#item').trigger('keydown');
		});

		$('#advanced_receiving_interface').on('hidden.bs.modal', function() {
			clear_advanced_item_list();
		});

		function exist_in_advanced_item_list(id){
			if(advanced_selected_list.indexOf(id) > -1) return true;
			return false;
		}

		function manage_advanced_item_list(id, is_checked){
			if(is_checked){
				//add
				if(advanced_selected_list.indexOf(id) > -1){

				}else{
					advanced_selected_list.push(id);
				}
			}else{
				//remove
				if(advanced_selected_list.indexOf(id) > -1){
					advanced_selected_list.splice(advanced_selected_list.indexOf(id),1);
				}
			}
			$("#advanced_list_count").text(advanced_selected_list.length);
		}

		function add_advanced_item_list(){
			let selected_id_list = "";
			for(let i=0; i<advanced_selected_list.length; i++){
				if(selected_id_list == ""){
					selected_id_list = advanced_selected_list[i].toString().replace('_',' ').replace('V','#') + '|FORCE_ITEM_ID|';
				}else{
					selected_id_list = selected_id_list + '-' + advanced_selected_list[i].toString().replace('_',' ').replace('V','#') + '|FORCE_ITEM_ID|';
				}
			}
			$("#add_item_dialog #item").val(selected_id_list);
			$("#add_item_dialog #advanced_item_len").val(advanced_selected_list.length);

			$('<?php echo $search_from_selector;?>').ajaxSubmit({
				beforeSubmit: receivingsBeforeSubmit,
				success: itemScannedSuccess
			});
		}
  
		function close_advanced_item_list(){
			$('#advanced_receiving_interface').modal('hide');
			clear_advanced_item_list();
		}

		function clear_advanced_item_list(){
			$(".advanced_search_result").empty()
			advanced_selected_list = [];
			$("#advanced_list_count").text(0);
                $("<?php echo $search_from_selector;?> .ajax-loader").hide();
                item_variable_key = "";
        }
<?php } ?>
</script>

<?php if (isset($related_items) && count($related_items)) { ?>
<?php ob_start(); ?>
<div id="related_items_modal_recv">
       <div class="row register-grid">
       <?php foreach($related_items as $rel_item) { ?>
			<div class="category_item item col-md-3 col-sm-3 col-xs-6 register-holder">
				<?php $rel_id = 'related_item_'.$rel_item->item_id; ?>
				<label for="<?php echo $rel_id; ?>">
					<?php if($rel_item->main_image_id) { ?>
						<img class="img-responsive" src="<?php echo site_url('app_files/view/'.$rel_item->main_image_id); ?>"/>
					<?php } ?>
				</label>
				<input type="checkbox" id="<?php echo $rel_id; ?>" value="<?php echo $rel_item->item_id; ?>" />
				<label style="position:absolute; top:0; left:0; z-index:2000; width:100%; text-align:center; color:#000; font-weight:bold;" for="<?php echo $rel_id; ?>"><span></span> <?php echo H($rel_item->name); ?><br /><?php echo to_currency($rel_item->unit_price); ?></label>
			</div>
       <?php } ?>
       </div>
</div>
<?php $related_html = ob_get_clean(); ?>
<script type="text/javascript">
var relatedRecvDialog = bootbox.dialog({
       title: <?php echo json_encode(lang('items_related_products')); ?>,
       message: <?php echo json_encode($related_html); ?>,
       size: 'large',
       buttons:{
               cancel:{label: <?php echo json_encode(lang('common_close')); ?>, className:'btn-default'},
               add:{label: <?php echo json_encode(lang('common_add')); ?>, className:'btn-primary', callback:function(){
                       var ids=[]; $('#related_items_modal_recv input:checked').each(function(){ ids.push($(this).val()); });
                       function addNext(i){ if(i<ids.length){ $.post(<?php echo json_encode(site_url('receivings/add')); ?>,{item:ids[i]},function(){ addNext(i+1); }); } else { $('#register_container').load(<?php echo json_encode(site_url('receivings/reload')); ?>); } }
                       addNext(0);
               }}
       }
});
</script>
<?php } ?>

<style>
	.advanced-item-suggestions-container{
		padding: 12px;
		display: flex;
		justify-content: start;
		align-items: center;
		border-bottom: 1px solid #ccc;
		cursor: pointer;
	}
	.advanced-item-suggestions-container:hover{
		background: #eee;
	}

	.advanced-item-check{

	}

	.advanced-item-details{
		width:100%;
	}

	.advanced-item-details .attributes:first-child{
		flex:1;
	}

	.advanced-item-details .attributes{
		margin-left: 10px;
		margin-right: 20px;
	}

	.advanced-item-details .attributes-container{
		display:flex;
	}
</style>
