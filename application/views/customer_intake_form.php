<?php $this->load->view("partial/header_standalone"); ?>
<style>
       .intake-header {
               background-color: #489ee7;
               color: #fff;
               text-align: center;
               padding: 15px;
               margin-bottom: 20px;
       }
       .form-wrapper {
               background: #fff;
               padding: 20px;
               border-radius: 10px;
               box-shadow: 0 0 10px rgba(0,0,0,0.1);
               max-width: 600px;
               margin: 0 auto;
       }
       @media (max-width:768px){
               .form-horizontal .control-label{
                       text-align:left;
                       padding-top:0;
                       margin-bottom:5px;
               }
       }
</style>

<div class="intake-header">
       <h1><?php echo lang('common_customer_intake_form');?></h1>
</div>

<div class="container">
       <div class="form-wrapper">
	  <form action="" method="POST" class="form-horizontal">
       <div class="row">
               <div class="col-md-12">
		<div class="form-group">
			<?php 
			echo form_label(lang('common_first_name').':', 'first_name',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
			<div class="col-sm-9 col-md-9 col-lg-10">
				<div class="input-group" style="width:100%">
					<div class="input-group-btn" style="width:4rem">
						<?php  
						$titles = array(
							"" 		=> '',
							"Mr." 		=> lang('common_mr.'),
							"Mrs." 		=> lang('common_mrs.'),
							"Dr." 		=> lang('common_dr.'),
							"Hon." 		=> lang('common_hon.'),
							"Prof." 	=> lang('common_prof.'),
							"Rev." 		=> lang('common_rev.'),
							"Rt.Hon." 	=> lang('common_rt_hon.'),
							"Sr." 		=> lang('common_sr.'),
							"Jr." 		=> lang('common_jr.'),
							"St." 		=> lang('common_st.'),

							);
						?>
						<?php echo form_dropdown('title', $titles,'', 'class="form-control form-control-sm form-inps" id="title"');?>
				    </div>
					<?php echo form_input(array(
						'class'=>'form-control',
						'name'=>'first_name',
						'id'=>'first_name',
						'value'=>'')
					);?>
				</div>
			</div>
		</div>

		<div class="form-group">
			<?php echo form_label(lang('common_last_name').':', 'last_name',array('class'=>' col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
			<div class="col-sm-9 col-md-9 col-lg-10">
			<?php echo form_input(array(
				'class'=>'form-control',
				'name'=>'last_name',
				'id'=>'last_name',
				'value'=>'')
			);?>
			</div>
		</div>

		<div class="form-group">
			<?php echo form_label(lang('common_email').':', 'email',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
			<div class="col-sm-9 col-md-9 col-lg-10">
			<?php echo form_input(array(
				'class'=>'form-control',
				'name'=>'email',
				'type'=>'text',
				'id'=>'email',
				'value'=>'')
				);?>
			</div>
		</div>
		<div class="form-group">	
			<?php echo form_label(lang('common_phone_number').':', 'phone_number',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
			<div class="col-sm-9 col-md-9 col-lg-10">
			<?php echo form_input(array(
				'class'=>'form-control',
				'name'=>'phone_number',
				'id'=>'phone_number',
				'value'=>''));?>
			</div>
		</div>

<div class="form-group">	
<?php echo form_label(lang('common_address_1').':', 'address_1',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<?php echo form_input(array(
		'class'=>'form-control',
		'name'=>'address_1',
		'id'=>'address_1',
		'value'=>''));?>
	</div>
</div>

			<div class="form-group">	
<?php echo form_label(lang('common_address_2').':', 'address_2',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<?php echo form_input(array(
		'class'=>'form-control',
		'name'=>'address_2',
		'id'=>'address_2',
		'value'=>''));?>
	</div>
</div>

			<div class="form-group">	
<?php echo form_label(lang('common_city').':', 'city',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<?php echo form_input(array(
		'class'=>'form-control ',
		'name'=>'city',
		'id'=>'city',
		'value'=>''));?>
	</div>
</div>

			<div class="form-group">	
<?php echo form_label(lang('common_state').':', 'state',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<?php echo form_input(array(
		'class'=>'form-control ',
		'name'=>'state',
		'id'=>'state',
		'value'=>''));?>
	</div>
</div>

			<div class="form-group">	
<?php echo form_label(lang('common_zip').':', 'zip',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
	<div class="col-sm-9 col-md-9 col-lg-10">
	<?php echo form_input(array(
		'class'=>'form-control ',
		'name'=>'zip',
		'id'=>'zip',
		'value'=>''));?>
	</div>
</div>

<div class="form-group">
<?php echo form_label(lang('common_country').':', 'country',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
<div class="col-sm-9 col-md-9 col-lg-10">
<?php echo form_input(array(
'class'=>'form-control ',
'name'=>'country',
'id'=>'country',
'value'=>''));?>
</div>
</div>

<div class="form-group">
<?php echo form_label(lang('common_comments').':', 'comments',array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label ')); ?>
<div class="col-sm-9 col-md-9 col-lg-10">
<?php echo form_textarea(array(
'class'=>'form-control',
'name'=>'comments',
'id'=>'comments',
'rows'=>'3',
'value'=>''));?>
</div>
</div>

<?php for($k=1;$k<=NUMBER_OF_PEOPLE_CUSTOM_FIELDS;$k++) { ?>
       <?php if($this->Customer->get_custom_field($k) !== FALSE && $this->Customer->get_custom_field($k,'show_on_customer_intake_form')) { ?>
       <div class="form-group">
               <?php echo form_label($this->Customer->get_custom_field($k).':', "custom_field_${k}_value", array('class'=>'col-sm-3 col-md-3 col-lg-2 control-label')); ?>
               <div class="col-sm-9 col-md-9 col-lg-10">
                       <?php if($this->Customer->get_custom_field($k,'type') == 'checkbox') { ?>
                               <?php echo form_checkbox(array('name'=>"custom_field_${k}_value", 'id'=>"custom_field_${k}_value", 'value'=>'1')); ?>
                               <label for="<?php echo "custom_field_${k}_value"; ?>"><span></span></label>
                       <?php } elseif($this->Customer->get_custom_field($k,'type') == 'dropdown') { ?>
                               <?php $choices = explode('|',$this->Customer->get_custom_field($k,'choices')); $select_options = array('' => lang('common_please_select')); foreach($choices as $choice){ $select_options[$choice] = $choice; } echo form_dropdown("custom_field_${k}_value", $select_options, '', 'class="form-control"'); ?>
                       <?php } elseif($this->Customer->get_custom_field($k,'type') == 'date') { ?>
                               <?php echo form_input(array('type'=>'date','name'=>"custom_field_${k}_value",'id'=>"custom_field_${k}_value",'class'=>'form-control')); ?>
                       <?php } else { ?>
                               <?php echo form_input(array('name'=>"custom_field_${k}_value",'id'=>"custom_field_${k}_value",'class'=>'form-control')); ?>
                       <?php } ?>
               </div>
       </div>
       <?php } ?>
<?php } ?>
               <div class="form-group">
                       <div class="col-sm-offset-3 col-md-offset-3 col-lg-offset-2 col-sm-9 col-md-9 col-lg-10">
                               <input type="submit" class="btn btn-primary btn-block" value="<?php echo lang('common_submit');?>" />
                       </div>
               </div>
       </div><!-- /col-md-12 -->
       </div><!-- /row -->
       <?php echo form_close(); ?>
       </div>
</div>
<?php $this->load->view("partial/footer_standalone"); ?>