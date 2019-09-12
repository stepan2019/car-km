<?php /* Smarty version 3.1.24, created on 2019-08-28 21:22:15
         compiled from "/home/carpasgr/test/admin/templates/default/add_language.html" */ ?>
<?php
/*%%SmartyHeaderCode:8022671705d66f0877abe67_71881410%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2af78a198bb6760a1ede8629667f1375ae6b9758' => 
    array (
      0 => '/home/carpasgr/test/admin/templates/default/add_language.html',
      1 => 1567026619,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '8022671705d66f0877abe67_71881410',
  'variables' => 
  array (
    'id' => 0,
    'error' => 0,
    'template_path' => 0,
    'lng' => 0,
    'language' => 0,
    'live_site' => 0,
    'array_maps' => 0,
    'v' => 0,
    'demo' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d66f0877e97c1_97603188',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d66f0877e97c1_97603188')) {
function content_5d66f0877e97c1_97603188 ($_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/home/carpasgr/test/libs/plugins/modifier.capitalize.php';

$_smarty_tpl->properties['nocache_hash'] = '8022671705d66f0877abe67_71881410';
echo $_smarty_tpl->getSubTemplate ("data/fancybox.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0);
?>


<div class="p30">
<form name="language" method="post" action="<?php if (isset($_smarty_tpl->tpl_vars['id']->value) && $_smarty_tpl->tpl_vars['id']->value) {?>edit_language.php?id=<?php echo $_smarty_tpl->tpl_vars['id']->value;
} else { ?>add_language.php<?php }?>" enctype="multipart/form-data">

<div class="form_container">

<?php if (isset($_smarty_tpl->tpl_vars['error']->value) && $_smarty_tpl->tpl_vars['error']->value) {?><div class="error"><?php echo $_smarty_tpl->tpl_vars['error']->value;?>
</div><?php }?>


<div class="clearfix">
	<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['language_id'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['language_id'];?>
</div>
	<div class="right_form"><?php if (isset($_smarty_tpl->tpl_vars['id']->value) && $_smarty_tpl->tpl_vars['id']->value) {
echo $_smarty_tpl->tpl_vars['language']->value['id'];
} else { ?><input type="text" name="id" size="10" value=""><?php }?></div>
</div>

<div class="clearfix">
	<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['code'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['code'];?>
</div>
	<div class="right_form"><input type="text" name="code" size="10" value="<?php if (isset($_smarty_tpl->tpl_vars['language']->value['code'])) {
echo $_smarty_tpl->tpl_vars['language']->value['code'];
}?>" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['codes_link'];?>
</div>
</div>

<div class="clearfix">
	<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['language_name'];?>
</div>
	<div class="right_form"><input type="text" name="name" size="30" value="<?php if (isset($_smarty_tpl->tpl_vars['language']->value['name'])) {
echo $_smarty_tpl->tpl_vars['language']->value['name'];
}?>" /></div>
</div>

<div class="clearfix">
	<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['language_image'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['language_image'];?>
</div>
	<div class="right_form">
	<input name="flag_image" type="file" />
		<?php if (isset($_smarty_tpl->tpl_vars['language']->value['image']) && $_smarty_tpl->tpl_vars['language']->value['image'] != '') {?>
		<a href="<?php echo $_smarty_tpl->tpl_vars['live_site']->value;?>
/images/languages/<?php echo $_smarty_tpl->tpl_vars['language']->value['image'];?>
" class="imgfield">
		<img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/camera.png" /></a>
		<?php } else { ?>
		<img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/camera_off.png" />
		<?php }?>
		<?php if (isset($_smarty_tpl->tpl_vars['language']->value['image']) && $_smarty_tpl->tpl_vars['language']->value['image'] != '') {?>&nbsp;&nbsp;<a href="edit_language.php?id=<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
&delete=image"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/delete.png" class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['delete'];?>
" alt=""></a><?php }?>
	</div>
</div>

<div class="clearfix">
	<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['characters_map'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['characters_map'];?>
</div>
	<div class="right_form">
		<select name="characters_map[]" id="characters_map" multiple="multiple" size="4" class="mselect">
		<?php
$_from = $_smarty_tpl->tpl_vars['array_maps']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$_smarty_tpl->tpl_vars['v'] = new Smarty_Variable;
$_smarty_tpl->tpl_vars['v']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['v']->value) {
$_smarty_tpl->tpl_vars['v']->_loop = true;
$foreach_v_Sav = $_smarty_tpl->tpl_vars['v'];
?>
			<option value="<?php echo $_smarty_tpl->tpl_vars['v']->value;?>
" <?php if (isset($_smarty_tpl->tpl_vars['language']->value['characters_map']) && $_smarty_tpl->tpl_vars['language']->value['characters_map'] && in_array($_smarty_tpl->tpl_vars['v']->value,$_smarty_tpl->tpl_vars['language']->value['characters_map_array'])) {?>selected="selected"<?php }?>><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['v']->value);?>
</option>
		<?php
$_smarty_tpl->tpl_vars['v'] = $foreach_v_Sav;
}
?>
		</select>
	</div>
</div>

<div class="clearfix">
	<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['direction'];?>
</div>
	<div class="right_form">
		<select name="direction" >
			<option value="ltr" <?php if (isset($_smarty_tpl->tpl_vars['language']->value['direction']) && $_smarty_tpl->tpl_vars['language']->value['direction'] == 'ltr') {?>selected="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['ltr'];?>
</option>
			<option value="rtl" <?php if (isset($_smarty_tpl->tpl_vars['language']->value['direction']) && $_smarty_tpl->tpl_vars['language']->value['direction'] == 'rtl') {?>selected="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['rtl'];?>
</option>
		</select>
	</div>
</div>

<div class="clearfix">
	<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['languages']['info']['default'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['default'];?>
</div>
	<div class="right_form"><input name="default" type="checkbox" class="noborder" <?php if (isset($_smarty_tpl->tpl_vars['language']->value['default']) && $_smarty_tpl->tpl_vars['language']->value['default'] == 1) {?> checked="checked" <?php }?> /></div>
</div>

<div class="clearfix">
	<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['enabled'];?>
</div>
	<div class="right_form"><input name="enabled" type="checkbox" class="noborder" <?php if ((!isset($_smarty_tpl->tpl_vars['id']->value) || !$_smarty_tpl->tpl_vars['id']->value) || (isset($_smarty_tpl->tpl_vars['language']->value['enabled']) && $_smarty_tpl->tpl_vars['language']->value['enabled'] == 1)) {?>checked="checked"<?php }?> /></div>
</div>


<div class="form_footer">
	<div class="buttons rfloat" <?php if ($_smarty_tpl->tpl_vars['demo']->value) {?>onClick="alert('<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['errors']['demo'];?>
');return false;"<?php }?>>
		<span class="positive"><input type="submit" name="Submit" id="Submit" value="<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['submit'];?>
" /></span>
	</div>
	<div class="clearfix"></div>
</div>

</div> 

</form>
</div>


<?php echo '<script'; ?>
 type="text/javascript">
$(document).ready(function() {
	$("a.imgfield").fancybox();
});
<?php echo '</script'; ?>
>

<?php }
}
?>