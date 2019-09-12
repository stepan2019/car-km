<?php /* Smarty version 3.1.24, created on 2019-09-08 16:10:15
         compiled from "E:/workspace/carpass/admin/templates/default/data/fancybox.html" */ ?>
<?php
/*%%SmartyHeaderCode:19510601795d7527e7a39c11_82652453%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'e217f979fe1609ae66aaf5797847912c9e912620' => 
    array (
      0 => 'E:/workspace/carpass/admin/templates/default/data/fancybox.html',
      1 => 1567332163,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '19510601795d7527e7a39c11_82652453',
  'variables' => 
  array (
    'live_site' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d7527e7a45ae6_49384983',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d7527e7a45ae6_49384983')) {
function content_5d7527e7a45ae6_49384983 ($_smarty_tpl) {

$_smarty_tpl->properties['nocache_hash'] = '19510601795d7527e7a39c11_82652453';
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