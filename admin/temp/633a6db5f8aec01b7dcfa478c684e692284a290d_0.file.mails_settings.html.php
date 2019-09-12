<?php /* Smarty version 3.1.24, created on 2019-08-30 18:14:18
         compiled from "/home/carpasgr/test/admin/templates/default/mails_settings.html" */ ?>
<?php
/*%%SmartyHeaderCode:7005588395d69677a7d0995_42180458%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '633a6db5f8aec01b7dcfa478c684e692284a290d' => 
    array (
      0 => '/home/carpasgr/test/admin/templates/default/mails_settings.html',
      1 => 1567188803,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7005588395d69677a7d0995_42180458',
  'variables' => 
  array (
    'error' => 0,
    'successful' => 0,
    'lng' => 0,
    'template_path' => 0,
    'mail_settings' => 0,
    'demo' => 0,
    'info' => 0,
    'extra_info' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d69677a83ce87_77259556',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d69677a83ce87_77259556')) {
function content_5d69677a83ce87_77259556 ($_smarty_tpl) {

$_smarty_tpl->properties['nocache_hash'] = '7005588395d69677a7d0995_42180458';
?>
<div class="p30">
	<form name="settings" method="post" action="mails_settings.php" enctype="multipart/form-data">
		<div class="form_container">
			<?php if ($_smarty_tpl->tpl_vars['error']->value != '') {?><div class="error"><?php echo $_smarty_tpl->tpl_vars['error']->value;?>
</div><?php }?>
			<?php if ($_smarty_tpl->tpl_vars['successful']->value) {?><div class="info"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['settings_saved'];?>
</div><?php }?>

			<div class="clearfix">
				<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['info']['html_mails'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['html_mails'];?>
</div>
				<div class="right_form"><input type="checkbox" class="noborder" name="html_mails" <?php if ($_smarty_tpl->tpl_vars['mail_settings']->value['html_mails'] == 1) {?> checked="checked" <?php }?> /></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['use_smtp_authentication'];?>
</div>
				<div class="right_form"><input type="checkbox" name="use_smtp_auth" <?php if ($_smarty_tpl->tpl_vars['mail_settings']->value['use_smtp_auth'] == 1) {?> checked="checked" <?php }?> /></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['smtp_server'];?>
</div>
				<div class="right_form"><input type="text" maxlength="50" size="40" name="smtp_server" value="<?php echo $_smarty_tpl->tpl_vars['mail_settings']->value['smtp_server'];?>
" /></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['encryption'];?>
</div>
				<div class="right_form">
					<select id="encryption" name="encryption">
						<option value=""><?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['none'];?>
</option>
						<option value="ssl" <?php if ($_smarty_tpl->tpl_vars['mail_settings']->value['encryption'] == 'ssl') {?>selected<?php }?>>SSL</option>
						<option value="tls" <?php if ($_smarty_tpl->tpl_vars['mail_settings']->value['encryption'] == 'tls') {?>selected<?php }?>>TLS</option>
					</select>

				</div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['port'];?>
</div>
				<div class="right_form"><input type="text" maxlength="5" size="10" name="port" value="<?php echo $_smarty_tpl->tpl_vars['mail_settings']->value['port'];?>
"></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['username'];?>
</div>
				<div class="right_form"><input type="text" maxlength="70" size="40" name="mail_username" value="<?php echo $_smarty_tpl->tpl_vars['mail_settings']->value['username'];?>
"></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['password'];?>
</div>
				<div class="right_form"><input type="password" maxlength="70" size="20" name="mail_password" value="<?php echo $_smarty_tpl->tpl_vars['mail_settings']->value['password'];?>
" /></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['info']['bcc_to'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['bcc_to'];?>
</div>
				<div class="right_form"><input type="text" size="40" name="bcc_to" value="<?php echo $_smarty_tpl->tpl_vars['mail_settings']->value['bcc_to'];?>
" /></div>
			</div>

			<div class="clearfix">
				<div class="left_form"><img src="<?php echo $_smarty_tpl->tpl_vars['template_path']->value;?>
images/info.png"  class="tooltip icon" title="<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['info']['send_using_admin_email'];?>
" />&nbsp;<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['send_using_admin_email'];?>
</div>
				<div class="right_form"><input type="checkbox" class="noborder" name="send_using_admin_email" <?php if ($_smarty_tpl->tpl_vars['mail_settings']->value['send_using_admin_email'] == 1) {?> checked="checked" <?php }?> /></div>
			</div>


			<div class="form_footer">
				<div class="buttons rfloat"  <?php if ($_smarty_tpl->tpl_vars['demo']->value) {?>onClick="alert('<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['errors']['demo'];?>
');return false;"<?php }?>>
				<span class="positive"><input type="submit" name="Submit" id="Submit" value="<?php echo $_smarty_tpl->tpl_vars['lng']->value['general']['save_changes'];?>
" /></span>
			</div>
			<div class="clearfix"></div>
		</div>

		<?php if ($_smarty_tpl->tpl_vars['info']->value) {?><div class="info"><?php echo $_smarty_tpl->tpl_vars['info']->value;?>
</div><?php }?>
		<?php if (isset($_smarty_tpl->tpl_vars['extra_info']->value) && $_smarty_tpl->tpl_vars['extra_info']->value) {?><div class="warning"><?php echo $_smarty_tpl->tpl_vars['extra_info']->value;?>
</div><?php }?>

		<div class="form_footer">
			<div class="buttons" style="margin-left: 400px;">
				<span class="positive"><input type="submit" name="Test" value="<?php echo $_smarty_tpl->tpl_vars['lng']->value['settings']['test_mails'];?>
" /></span>
			</div>
			<div class="clearfix"></div>
		</div>


</div> 
</form>
</div> 

<?php }
}
?>