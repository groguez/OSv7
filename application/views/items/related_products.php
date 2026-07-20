<?php $this->load->view("partial/header"); ?>
<?php $query = http_build_query(array('redirect' => $redirect, 'progression' => $progression ? 1 : null, 'quick_edit' => $quick_edit ? 1 : null)); ?>
<div class="manage_buttons">
    <div class="row">
        <div class="<?php echo isset($redirect) ? 'col-xs-9 col-sm-10 col-md-10 col-lg-10': 'col-xs-12 col-sm-12 col-md-12' ?> margin-top-10">
            <div class="modal-item-info padding-left-10">
                <div class="modal-item-details margin-bottom-10">
                    <?php if(!$item_info->item_id) { ?>
                        <span class="modal-item-name new"><?php echo lang('items_new'); ?></span>
                    <?php } else { ?>
                        <span class="modal-item-name"><?php echo H($item_info->name).' ['.lang('common_id').': '.$item_info->item_id.']'; ?></span>
                        <span class="modal-item-category"><?php echo H($category); ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php if(isset($redirect) && !$progression) { ?>
        <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2 margin-top-10">
            <div class="buttons-list">
                <div class="pull-right-btn">
                    <?php echo anchor(site_url($redirect), ' ' . lang('common_done'), array('class'=>'outbound_link btn btn-primary btn-lg ion-android-exit', 'title'=>'')); ?>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php if(!$quick_edit) { ?>
<?php $this->load->view('partial/nav', array('progression' => $progression, 'query' => $query, 'item_info' => $item_info)); ?>
<?php } ?>
<?php echo form_open('items/save_related_products/'.(!isset($is_clone) ? $item_info->item_id : ''),array('id'=>'item_form','class'=>'form-horizontal')); ?>
<div class="row <?php echo $redirect ? 'manage-table' :''; ?>">
    <div class="col-md-12">
        <div class="panel panel-piluku">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="ion-link"></i> <?php echo lang('items_related_products'); ?> <small>(<?php echo lang('common_fields_required_message'); ?>)</small></h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <?php echo form_label(lang('common_search') . ':', 'related_item',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label wide')); ?>
                    <div class="col-sm-9 col-md-9 col-lg-10">
                        <input type="text" id="related_item" class="form-control form-inps" />
                    </div>
                </div>
                <ul id="related_items_list" class="list-group sortable">
                    <?php foreach($related_items as $related) { ?>
                        <li class="list-group-item"><?php echo H($this->Item->get_info($related->related_item_id)->name); ?> <a href="#" class="delete-related pull-right">&times;</a><input type="hidden" name="related_items[]" value="<?php echo $related->related_item_id; ?>" /></li>
                    <?php } ?>
                </ul>
                <div class="checkbox">
                    <?php echo form_checkbox(array('name'=>'disable_related_sales_prompt','id'=>'disable_related_sales_prompt','value'=>1,'checked'=>$item_info->disable_related_sales_prompt)); ?>
                    <label for="disable_related_sales_prompt"><span></span><?php echo lang('items_dont_prompt_on_sales'); ?></label>
                </div>
                <div class="checkbox">
                    <?php echo form_checkbox(array('name'=>'disable_related_receivings_prompt','id'=>'disable_related_receivings_prompt','value'=>1,'checked'=>$item_info->disable_related_receivings_prompt)); ?>
                    <label for="disable_related_receivings_prompt"><span></span><?php echo lang('items_dont_prompt_on_receivings'); ?></label>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo form_hidden('progression', isset($progression) ? $progression : ''); ?>
<?php echo form_hidden('quick_edit_post', isset($quick_edit) ? $quick_edit : ''); ?>
<?php echo form_hidden('redirect', isset($redirect) ? $redirect : ''); ?>
<div class="form-actions pull-right">
    <?php echo form_submit(array('name'=>'submitf','id'=>'submitf','value'=>lang('common_save'),'class'=>'submit_button btn btn-primary')); ?>
</div>
<?php echo form_close(); ?>
<script type='text/javascript'>
<?php $this->load->view("partial/common_js"); ?>

$("#related_item").autocomplete({
    source: '<?php echo site_url("items/item_search");?>',
    minLength: 0,
    delay: 300,
    select: function(event, ui){
        if(ui.item.value == '<?php echo $item_info->item_id; ?>') return;
        var row = $('<li class="list-group-item"></li>')
            .text(ui.item.label + ' [<?php echo lang('common_id'); ?>: '+ui.item.value+']')
            .append(' <a href="#" class="delete-related pull-right">&times;</a>')
            .append('<input type="hidden" name="related_items[]" value="'+ui.item.value+'" />');
        $('#related_items_list').append(row);
        $('#related_item').val('');
        return false;
    }
}).autocomplete("widget").addClass("dropdown-menu dropdown-menu-sm");

// Custom render method
$("#related_item").data("ui-autocomplete")._renderItem = function(ul, item) {
    if(item.value == '<?php echo $item_info->item_id; ?>')  return $("<div>"); // Prevent adding the current item as a related item
    return $("<li>")
        .data("item.autocomplete", item)
        .append("<a>" + item.label + ' [<?php echo lang('common_id'); ?>: '+item.value+']' + "</a>")
        .appendTo(ul);
};

$("#related_item").on('keypress',function(e){
    if(e.which==13){
        e.preventDefault();
        var scan = $(this).val();
        $.getJSON('<?php echo site_url("items/item_search");?>',{term:scan,exact_search:1},function(response){
            if(response.length && response[0].value){
                if(response[0].value == '<?php echo $item_info->item_id; ?>') return;
                var row = $('<li class="list-group-item"></li>')
                    .text(response[0].label + ' [<?php echo lang('common_id'); ?>: '+response[0].value+']')
                    .append(' <a href="#" class="delete-related pull-right">&times;</a>')
                    .append('<input type="hidden" name="related_items[]" value="'+response[0].value+'" />');
                $('#related_items_list').append(row);
                $('#related_item').val('');
            }
        });
    }
});

$('#related_items_list').on('click','.delete-related',function(e){e.preventDefault();$(this).closest('li').remove();});
$('#related_items_list').sortable();
<?php if ($this->session->flashdata('manage_success_message')) { ?>
       show_feedback('success', <?php echo json_encode($this->session->flashdata('manage_success_message')); ?>, <?php echo json_encode(lang('common_success')); ?>);
<?php } ?>
	   
$("#related_item").focus();
</script>
<?php $this->load->view('partial/footer'); ?>
