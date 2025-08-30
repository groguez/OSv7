<?php $this->load->view("partial/header"); ?>
<style>
	#category_tree ul {
	    list-style: none;
	    padding-left: 20px;
	}

	#category_tree li {
	    margin: 5px 0;
	}

	.collapsible {
	    cursor: pointer;
	}

	.collapsible::before {
	    content: '▶';
	    display: inline-block;
	    margin-right: 5px;
	    transform: rotate(0deg);
	    transition: transform 0.3s;
	}

	.collapsible.collapsed::before {
	    transform: rotate(90deg);
	}

	.hidden {
	    display: none;
	}

</style>

<script>
	
	// Called on each collapsible click
	function toggleCategory($toggleButton, subList) {
	    let li = $toggleButton.closest('li');
	    let catId = li.data('category-id');
    
	    // Toggle the "hidden" class
	    subList.toggleClass('hidden');
	    $toggleButton.toggleClass('collapsed');
    
	    // Now update localStorage
	    let openStates = JSON.parse(localStorage.getItem('openCategories') || '[]');
	    if (subList.hasClass('hidden')) {
	        // The category is now closed; remove from list
	        openStates = openStates.filter(id => id !== catId);
	    } else {
	        // The category is now open; add to list if not present
	        if (!openStates.includes(catId)) {
	            openStates.push(catId);
	        }
	    }
	    localStorage.setItem('openCategories', JSON.stringify(openStates));
	}
	
	function initCollpaseableTree() {
	    // Let’s load the “open” list from localStorage
	    let openStates = JSON.parse(localStorage.getItem('openCategories') || '[]');

	    $('#category_tree li').each(function() {
	        let $category = $(this);
	        let catId = $category.data('category-id');
	        const $subList = $category.find('> ul');

	        if ($subList.length) {
	            // Create collapsible
	            const $toggleButton = $('<span class="collapsible"></span>').text(
	                $category.contents().filter(function() {
	                    return this.nodeType === 3; // text node
	                }).text().trim()
	            );

	            // Remove the text node and prepend our button
	            $category.contents().filter(function() {
	                return this.nodeType === 3;
	            }).remove();
	            $category.prepend($toggleButton);

	            // If this catId is in openStates, keep it open, otherwise hide it
	            if (!openStates.includes(catId)) {
	                $subList.addClass('hidden');
	            } else {
	                // Mark as expanded
	                $toggleButton.addClass('collapsed'); // So arrow points down
	            }

	            // On click, toggle, and record in localStorage
	            $toggleButton.on('click', function() {
	                toggleCategory($toggleButton, $subList);
	            });
	        }
	    });
	}		
	$(document).ready(initCollpaseableTree);
</script>
<?php if($redirect) { ?>
<div class="manage_buttons">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 margin-top-10">
			<div class="buttons-list">
				<div class="pull-right-btn">
				<?php echo 
					anchor(site_url($redirect), ' ' . lang('common_done'), array('class'=>'btn btn-primary btn-lg ion-android-exit', 'title'=>''));
				?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php $this->load->view('partial/categories/category_modal', array('categories' => $categories));?>
		<div class="row <?php echo $redirect ? 'manage-table' :''; ?>">
			<div class="col-md-12 form-horizontal">
				<div class="panel-piluku panel">
					<div class="panel-heading"><?php echo lang("items_manage_categories"); ?></div>
					<div class="panel-body">
                                                <a href="javascript:void(0);" class="add_child_category" data-category_id="0">[<?php echo lang('items_add_root_category'); ?>]</a>

                                                <div class="form-group" style="margin:10px 0;">
                                                        <input type="text" id="category_search" class="form-control" placeholder="<?php echo H(lang('common_search')); ?>" />
                                                </div>

                                                        <div id="category_tree">
                                                                <?php echo $category_tree; ?>
                                                        </div>

                                                <a href="javascript:void(0);" class="add_child_category" data-category_id="0">[<?php echo lang('items_add_root_category'); ?>]</a>
					</div>
				</div>
			</div>
		</div><!-- /row -->
	</div>
				
<script type='text/javascript'>	
	$(document).on('click', ".edit_category",function()
	{
		$("#categoryModalDialogTitle").html(<?php echo json_encode(lang('common_edit')); ?>);

		var parent_id = $(this).data('parent_id') ? $(this).data('parent_id') : 0;
		$("#categories_form").find('#parent_id').val(parent_id);
		$("#categories_form").attr('open_mode', 'edit');
		
		$parent_id_select = $('#parent_id');
		$parent_id_select[0].selectize.setValue(parent_id, false);
		
		var category_id = $(this).data('category_id');
		$("#categories_form").attr('action',SITE_URL+'/items/save_category/'+category_id);
		
		//Populate form
		$(":file").filestyle('clear');

		$.getJSON(SITE_URL+'/items/get_category/'+category_id, function(response)
		{
			if(response.success){
				$("#categories_form").find('#category_name').val(response.category_info.name);
				$("#categories_form").find('#category_description').val(response.category_info.category_description);
				$("#categories_form").find('#category_info_popup').val(response.category_info.category_info_popup);

				$("#categories_form").find('#category_color').val(response.category_info.color);
				$('#category_color').colorpicker('setValue', response.category_info.color);
			}
		});

		$('#del_image').prop('checked',false);
		
		$(".hide_from_grid_checkbox").prop('checked',false);
		$.getJSON(SITE_URL+'/items/get_hidden_locations_for_category/'+category_id, function(locations)
		{
			for(var k=0;k<locations.length;k++)
			{
				$("#locations_"+locations[k]+"_hide_from_grid").prop('checked',true);
			}
		});
		
		if ($(this).data('exclude_from_e_commerce'))
		{
			$('#exclude_from_e_commerce').prop('checked',true);
		}
		else
		{
			$('#exclude_from_e_commerce').prop('checked',false);
		}
		if ($(this).data('image_id'))
		{
			$("#categories_form").find('#image-preview').attr('src',SITE_URL+'/app_files/view_cacheable/'+$(this).data('image_id')+"?timestamp="+$(this).data('image_timestamp'));
			$('#preview-section').show();
		}
		else 
		{
			$("#categories_form").find('#image-preview').attr('src','');
			$('#preview-section').hide();
		}

		$('select[name^="tier_type"]').val($('select[name^="tier_type"]').find('option:first').val());
		$('input[name^="category_tier"]').val('');

		if ($(this).data('tier-list'))
		{
			let tier_list = $(this).data('tier-list');
			if( tier_list && tier_list.length > 0 ){
				for(let i=0; i<tier_list.length; i++){
					tier_data = tier_list[i];
					let tier_id = tier_data.tier_id;
					let select_tier_tag = $("#categories_form").find('select[name="tier_type[' + tier_id + ']"]');

					let tier_category_id = tier_data.category_id;
					let tier_unit_price = tier_data.unit_price;
					if( tier_unit_price ){
						select_tier_tag.val('unit_price');
						$('input[name^="category_tier[' + tier_id + ']"]').val(to_currency_no_money(tier_unit_price));
					}

					let tier_percent_off = tier_data.percent_off;
					if(tier_percent_off){
						select_tier_tag.val('percent_off');
						$('input[name^="category_tier[' + tier_id + ']"]').val(tier_percent_off);
					}

					let tier_cost_plus_percent = tier_data.cost_plus_percent;
					if(tier_cost_plus_percent){
						select_tier_tag.val('cost_plus_percent');
						$('input[name^="category_tier[' + tier_id + ']"]').val(tier_cost_plus_percent);
					}

					let tier_cost_plus_fixed_amount = tier_data.cost_plus_fixed_amount;
					if(tier_cost_plus_fixed_amount){
						select_tier_tag.val('cost_plus_fixed_amount');
						$('input[name^="category_tier[' + tier_id + ']"]').val(to_currency_no_money(tier_cost_plus_fixed_amount));
					}
				}
			}
		}
		
		//show
		$("#category-input-data").modal('show');
	});
	
	$(document).on('click', ".add_child_category",function()
	{
		$('input[name^="category_tier"]').val('');
		
		$("#categoryModalDialogTitle").html(<?php echo json_encode(lang('items_add_child_category')); ?>);
		var parent_id = $(this).data('category_id');
		
		$parent_id_select = $('#parent_id');
		$parent_id_select[0].selectize.setValue(parent_id, false);
		
		$("#categories_form").attr('action',SITE_URL+'/items/save_category');
		$("#categories_form").attr('open_mode', 'add');
		
		//Clear form
		$(":file").filestyle('clear');
		$("#categories_form").find('#category_name').val("");
		$("#categories_form").find('#category_description').val("");
		$("#categories_form").find('#category_color').val("");
		$('#category_color').colorpicker('setValue', '');
		$("#categories_form").find('#category_image').val("");
		$("#categories_form").find('#image-preview').attr('src','');
		$('#del_image').prop('checked',false);
		$('#exclude_from_e_commerce').prop('checked',false);
		
		$('#preview-section').hide();
		
		//show
		$("#category-input-data").modal('show');
		
	});

	$("#categories_form").submit(function(event)
	{
		event.preventDefault();

		$(this).ajaxSubmit({ 
			success: function(response, statusText, xhr, $form){
				show_feedback(response.success ? 'success' : 'error', response.message, response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);
				if(response.success)
				{
					$("#category-input-data").modal('hide');
					let open_mode = $("#categories_form").attr('open_mode');
					if(open_mode != 'edit'){
						$('#category_tree').load("<?php echo site_url("items/get_category_tree_list"); ?>", initCollpaseableTree);
					}else{
						let current_category_name = $("#category_name").val();
					  $("a.edit_category[data-category_id='" + response.selected + "']")
					    .closest('li')
					    .children('span.category_name')
					    .text(response.category_name);
					}
					var category_id_selectize = $("#parent_id")[0].selectize;
					response.categories.unshift({value:0, text:<?php echo json_encode(lang('common_none')); ?>});
					category_id_selectize.clearOptions();
					category_id_selectize.addOption(response.categories);
					category_id_selectize.addItem(response.selected, true);
				}		
			},
			dataType:'json',
		});
	});

	$(document).on('click', ".delete_category",function()
	{
		var category_id = $(this).data('category_id');
		if (category_id)
		{
			bootbox.confirm(<?php echo json_encode(lang('items_category_delete_confirmation')); ?>, function(result)
			{
				if(result)
				{
					$.post('<?php echo site_url("items/delete_category");?>', {category_id : category_id},function(response) {

						show_feedback(response.success ? 'success' : 'error', response.message,response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);

						//Refresh tree if success
						if (response.success)
						{
							$('#category_tree').load("<?php echo site_url("items/get_category_tree_list"); ?>",initCollpaseableTree);
						}
					}, "json");
				}
			});
		}
	});

	$(document).on('click', ".hide_from_grid",function()
	{
		var category_id = $(this).data('category_id');
		if (category_id)
		{
			$.post('<?php echo site_url("items/save_category");?>'+'/'+category_id, {hide_from_grid: $(this).prop('checked') ? 1 : 0},function(response) {
				show_feedback(response.success ? 'success' : 'error', response.message,response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);
				//Refresh tree if success
				if (response.success)
				{
					$('#category_tree').load("<?php echo site_url("items/get_category_tree_list"); ?>",initCollpaseableTree);
				}
			}, "json");
		}

	});

        $(document).on('click', ".exclude_from_e_commerce",function()
        {
                var category_id = $(this).data('category_id');
                if (category_id)
                {
                        $.post('<?php echo site_url("items/save_category");?>'+'/' +category_id, {exclude_from_e_commerce: $(this).prop('checked') ? 1 : 0},function(response) {
                                show_feedback(response.success ? 'success' : 'error', response.message,response.success ? <?php echo json_encode(lang('common_success')); ?> : <?php echo json_encode(lang('common_error')); ?>);
                                //Refresh tree if success
                                if (response.success)
                                {
                                        $('#category_tree').load("<?php echo site_url("items/get_category_tree_list"); ?>",initCollpaseableTree);
                                }
                        }, "json");
                }

        });

        function filter_categories()
        {
                var query = $('#category_search').val().toLowerCase();

                if(!query)
                {
                        $('#category_tree li').show();
                        return;
                }

                $('#category_tree li').hide();

                $('#category_tree li').filter(function()
                {
                        return $(this).children('span.category_name').text().toLowerCase().indexOf(query) !== -1;
                }).each(function()
                {
                        $(this).show();
                        $(this).parents('li').show();
                });
        }

        $(document).on('keyup', '#category_search', filter_categories);

</script>
<?php $this->load->view('partial/footer'); ?>
