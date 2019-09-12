<?php /* Smarty version 3.1.24, created on 2019-08-28 21:22:15
         compiled from "/home/carpasgr/test/admin/templates/default/data/fancybox.html" */ ?>
<?php
/*%%SmartyHeaderCode:1816524295d66f0877eeac2_19279196%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '98152647a43df29b0b929d31a2b706d4ff218e9d' => 
    array (
      0 => '/home/carpasgr/test/admin/templates/default/data/fancybox.html',
      1 => 1567026619,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1816524295d66f0877eeac2_19279196',
  'variables' => 
  array (
    'live_site' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d66f0877ef909_08987211',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d66f0877ef909_08987211')) {
function content_5d66f0877ef909_08987211 ($_smarty_tpl) {

$_smarty_tpl->properties['nocache_hash'] = '1816524295d66f0877eeac2_19279196';
?>
<?php echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['live_site']->value;?>
/libs/jQuery/plugins/fancybox/jquery.fancybox-1.3.4.pack.js"><?php echo '</script'; ?>
>
<link rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['live_site']->value;?>
/libs/jQuery/plugins/fancybox/jquery.fancybox-1.3.4.min.css" type="text/css" media="screen" />
<?php }
}
?>