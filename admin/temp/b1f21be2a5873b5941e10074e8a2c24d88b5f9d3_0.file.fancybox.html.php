<?php /* Smarty version 3.1.24, created on 2019-09-10 21:28:45
         compiled from "E:/workspace/car-km/admin/templates/default/data/fancybox.html" */ ?>
<?php
/*%%SmartyHeaderCode:6070598565d78158d2608c6_11135351%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b1f21be2a5873b5941e10074e8a2c24d88b5f9d3' => 
    array (
      0 => 'E:/workspace/car-km/admin/templates/default/data/fancybox.html',
      1 => 1567332163,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '6070598565d78158d2608c6_11135351',
  'variables' => 
  array (
    'live_site' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d78158d278879_93268855',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d78158d278879_93268855')) {
function content_5d78158d278879_93268855 ($_smarty_tpl) {

$_smarty_tpl->properties['nocache_hash'] = '6070598565d78158d2608c6_11135351';
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