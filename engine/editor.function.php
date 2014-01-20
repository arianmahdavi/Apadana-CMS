<?php
/**
 * @In the name of God!
 * @author: Apadana CMS Development Team 
 * @email: info@apadanacms.ir
 * @link: http://www.apadanacms.ir
 * @license: http://www.gnu.org/licenses/
 * @copyright: Copyright © 2012-2014 ApadanaCms.ir. All rights reserved.
 * @Apadana CMS is a Free Software
 */

defined('security') or exit('Direct Access to this location is not allowed.');

function wysiwyg_textarea($name, $value, $config = 'apadana', $class = null, $extra = null, $cols = 50, $rows = 10)
{
    if ($config == 'Class')
	{
		global $page;
		set_head('<script language="JavaScript" src="'.url.'engine/editor/ckeditor/ckeditor.js" type="text/javascript"></script>');
        $class = !empty($class)? ' '.$class : null;
        $extra = !empty($extra)? ' '.$extra : null;
	    return '<textarea name="'.$name.'" rows="'.$rows.'" cols="'.$cols.'" class="ckeditor'.$class.'"'.$extra.'>'.$value.'</textarea>';
	}
	$id = 'textarea_'.trim(str_replace(array('[', ']', '-', '__', ' '), '_', $name), '_');
	if ($data = get_cache(('ckeditor-cache-'.$config))){
	$data = str_replace(array("{textarea_id}","{textname}","{textvalue}"),array($id,$name,$value),$data);
	 return $data;
	}
	
	global $options;

	require_once root_dir.'engine/editor/ckeditor/ckeditor.php';
	$ckeditor = new CKEditor();
	$ckeditor->returnOutput = true;
	$ckeditor->basePath = url.'engine/editor/ckeditor/';
	$ckeditor->textareaAttributes['id'] = "{textarea_id}";
	$ckeditor->config['smiley_path'] = url.'engine/images/smiles/';
	$ckeditor->config['uiColor'] = $options['editor-color'];
	$ckeditor->config['fontSize_sizes'] = '30/30%;50/50%;100/100%;120/120%;150/150%;200/200%;300/300%';
	$images = $des = array();
	for($i = 1; $i<= $options['smiles_number']; $i++){
			$images[] = ($i.'.'.$options['smiles_extension']);
			$des[] = ('smiley'.$i);
	}
	$ckeditor->config['smiley_images'] = $images;
	$ckeditor->config['smiley_descriptions'] = $des;
	switch($config)
	{
	    case 'Basic':
		$ckeditor->config['toolbar'] = array(
			array( 'FontSize','Bold','Italic','TextColor','-','JustifyLeft','JustifyCenter','JustifyRight','-','Link','Unlink','Smiley' )
		);	
		break;	

	    case 'BBcode':
		$ckeditor->config['extraPlugins'] = 'bbcode';
		$ckeditor->config['removePlugins'] = 'bidi,dialogadvtab,div,filebrowser,flash,format,forms,horizontalrule,iframe,justify,liststyle,pagebreak,showborders,stylescombo,table,tabletools,templates';
		$ckeditor->config['disableObjectResizing'] = true;

		$ckeditor->bbcode_set = $options['smiles_number'];
		 $ckeditor->config['toolbar'] =array(
		array('name' => 'document' ,'groups' => array('mode', 'document', 'doctools'), 'items' => array('Source')) ,
		array('name' => 'clipboard','groups' => array( 'clipboard', 'undo' ), 'items' => array('Cut', 'Copy', 'Paste', 'PasteText', '-', 'Undo', 'Redo')),
		array('name' => 'basicstyles','groups' => array(  'basicstyles', 'cleanup' ), 'items' => array('Bold', 'Italic', 'Underline', '-', 'RemoveFormat')),
		array('name' => 'paragraph','groups' => array( 'list', 'indent', 'blocks' ), 'items' => array('NumberedList', 'BulletedList', '-', 'Blockquote')),
		array('name' => 'links', 'items' => array('Link', 'Unlink', 'Anchor')),
		array('name' => 'insert','items' => array('Image', 'Table', 'HorizontalRule', 'Smiley' ,'SpecialChar')),
		array('name' => 'styles', 'items' => array( 'FontSize' )),
		array('name' => 'colors', 'items' => array('TextColor')),
		array('name' => 'tools', 'items' => array('Maximize')),
		array('name' => 'others', 'items' => array('-')),
		); 
		break;
		
	    case 'apadana':
		if (group_admin == 1 && member::check_admin_page_access('media'))
		{
			global $options;
			$ckeditor->config['filebrowserBrowseUrl'] = url.'?admin='.$options['admin'].'&section=media&noTemplate=true&editor=true';
		}		
		break;
		
	    default:
		 $ckeditor->config['toolbar'] =array(
		array('name' => 'document' ,'groups' => array('mode', 'document', 'doctools'), 'items' => array('Source')),
		array('name' => 'clipboard','groups' => array( 'clipboard', 'undo' ), 'items' => array('Cut', 'Copy', 'Paste', 'PasteText', '-', 'Undo', 'Redo')),
		array('name' => 'editing','groups' => array( 'find', 'selection', 'spellchecker' ), 'items' => array('Scayt')),
		array('name' => 'basicstyles','groups' => array(  'basicstyles', 'cleanup' ), 'items' => array('Bold', 'Italic', 'Underline', '-', 'RemoveFormat')),
		array('name' => 'paragraph','groups' => array( 'list', 'indent', 'blocks' ), 'items' => array('NumberedList', 'BulletedList', '-', 'Blockquote')),
		array('name' => 'links', 'items' => array('Link', 'Unlink', 'Anchor')),
		array('name' => 'insert','items' => array('Image', 'Table', 'HorizontalRule', 'Smiley' ,'SpecialChar')),
		array('name' => 'styles', 'items' => array( 'FontSize' )),
		array('name' => 'colors', 'items' => array('TextColor')),
		array('name' => 'tools', 'items' => array('Maximize')),
		array('name' => 'others', 'items' => array('-')),
		); 
		break;		
	}
	$data = $ckeditor->editor("{textname}", "{textvalue}", $config,array()); 
	set_cache(('ckeditor-cache-'.$config), $data);
	$data = str_replace(array("{textarea_id}","{textname}","{textvalue}"),array($id,$name,$value),$data);
	return $data;
}

?>