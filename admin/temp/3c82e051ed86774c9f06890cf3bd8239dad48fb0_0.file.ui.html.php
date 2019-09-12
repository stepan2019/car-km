<?php /* Smarty version 3.1.24, created on 2019-09-08 16:10:15
         compiled from "E:/workspace/carpass/admin/templates/default/data/ui.html" */ ?>
<?php
/*%%SmartyHeaderCode:9277445305d7527e7a82d35_46213482%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '3c82e051ed86774c9f06890cf3bd8239dad48fb0' => 
    array (
      0 => 'E:/workspace/carpass/admin/templates/default/data/ui.html',
      1 => 1567332163,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '9277445305d7527e7a82d35_46213482',
  'variables' => 
  array (
    'ui_included' => 0,
    'live_site' => 0,
  ),
  'has_nocache_code' => false,
  'version' => '3.1.24',
  'unifunc' => 'content_5d7527e7abfbe4_77571516',
),false);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_5d7527e7abfbe4_77571516')) {
function content_5d7527e7abfbe4_77571516 ($_smarty_tpl) {

$_smarty_tpl->properties['nocache_hash'] = '9277445305d7527e7a82d35_46213482';
if (!isset($_smarty_tpl->tpl_vars['ui_included']->value) || !$_smarty_tpl->tpl_vars['ui_included']->value) {?>
<?php $_smarty_tpl->tpl_vars["ui_included"] = new Smarty_Variable("1", null, 3);
$_ptr = $_smarty_tpl->parent; while ($_ptr != null) {$_ptr->tpl_vars["ui_included"] = clone $_smarty_tpl->tpl_vars["ui_included"]; $_ptr = $_ptr->parent; }
Smarty::$global_tpl_vars["ui_included"] = clone $_smarty_tpl->tpl_vars["ui_included"];?>
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['live_site']->value;?>
/libs/jQuery/jquery-ui.min.css" />
<?php echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['live_site']->value;?>
/libs/jQuery/jquery-ui.min.js"><?php echo '</script'; ?>
>
<?php }

}
}
?>