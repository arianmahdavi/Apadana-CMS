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

member::check_admin_page_access('newsletter') or warning('عدم دسترسی!', 'شما دسترسی لازم برای مشاهده این بخش را ندارید!');

function _index()
{
	global $d, $tpl,$member_groups;

	require_once(engine_dir.'editor.function.php');

	set_title('خبرنامه');

	$groups = '<select name="newsletter[groups][]" size="6" style="width:300px" multiple="multiple">';
	
		foreach ($member_groups as $c)
		{
			if($c['group_id'] == 5) continue;
			$groups .= '<option value="'.$c['group_id'].'" >'.$c['group_name'].'</option>';
		}
	
	$groups .= '</select>';

	$itpl = new template('newsletter.tpl', engine_dir.'admin/template/');
	$itpl->assign(array(
		'{textarea}' => wysiwyg_textarea('newsletter[text]', null),
		'{members-0}' => $d->numRows("SELECT `member_id` FROM `#__members` WHERE `member_newsletter`='0'", true),
		'{members-1}' => $d->numRows("SELECT `member_id` FROM `#__members` WHERE `member_newsletter`='1'", true),
		'{groups}' => $groups
	));
	set_content(null, $itpl->get_var());
}

function _send()
{
	global $options, $page, $d;
	$where = array();
	$where[]= "`member_status` = '1'";
	$msg = null;
	$data = get_param($_POST, 'newsletter', null, 1);
	$title = htmlencode($data['title']);
	$type = ($data['type'] == "pm" ? "pm" : "email");
	$start = ($data['start'] <= 0 || !is_numeric($data['start']) ? 0 : $data['start']);
	$limit = ($data['limit'] <= 20 || !is_numeric($data['limit']) ? 20 : $data['limit']);
	if(isset($data['groups']) && is_array($data['groups'])){
		$where[] = "`member_group` IN ('" .implode("','",$data['groups']) . "')";	
	}else{
		$data['groups'] = "";
	}
	$all = (isset($data['all']) && intval($data['all']) > 0)? 1 : 0 ;
	if($all == 0) $where[] = "`member_newsletter`='1'";
	
	if (count($where)) $where = "WHERE ".implode (" AND ", $where);
	else $where = "";
	if (empty($data['title']) || empty($data['text']))
	{
		exit;
	}
	//SECURE THE MESSAGE AND TITLE
	$find = array ('/vbscript:/i', '/onclick/i', '/onload/i', '/onunload/i', '/onabort/i', '/onerror/i', '/onblur/i', '/onchange/i', '/onfocus/i', '/onreset/i', '/onsubmit/i', '/ondblclick/i', '/onkeydown/i', '/onkeypress/i', '/onkeyup/i', '/onmousedown/i', '/onmouseup/i', '/onmouseover/i', '/onmouseout/i', '/onselect/i', '/javascript/i' );
	$replace = array ("vbscript<b></b>:", "&#111;nclick", "&#111;nload", "&#111;nunload", "&#111;nabort", "&#111;nerror", "&#111;nblur", "&#111;nchange", "&#111;nfocus", "&#111;nreset", "&#111;nsubmit", "&#111;ndblclick", "&#111;nkeydown", "&#111;nkeypress", "&#111;nkeyup", "&#111;nmousedown", "&#111;nmouseup", "&#111;nmouseover", "&#111;nmouseout", "&#111;nselect", "j&#097;vascript" );
	$data['text'] = preg_replace( $find, $replace, $data['text'] );
	$data['text'] = preg_replace( "#<iframe#i", "&lt;iframe", $data['text'] );
	$data['text'] = preg_replace( "#<script#i", "&lt;script", $data['text'] );
	$data['title'] = preg_replace( $find, $replace, $data['title'] );
	$data['title'] = preg_replace( "#<iframe#i", "&lt;iframe", $data['title'] );
	$data['title'] = preg_replace( "#<script#i", "&lt;script", $data['title'] );
	
	$send = $error = 0;
	$d->query("SELECT `member_email`, `member_name` FROM #__members ".$where." ORDER BY member_id DESC LIMIT ".$start.",".$limit);
	if($type == "email"){
		require_once(engine_dir.'mail.function.php');
		while ($row = $d->fetch()) 
		{
			$toname = $row['member_name'];
			$toemail = $row['member_email'];
			$fromname = $options['title'];
			$fromemail = $options['mail'];
			$subject = 'خبرنامه سایت '.$options['title'];
			$data['text'] = str_replace("{%username%}", $row['member_name'], $data['text']);
			$Body  = '<h2>'.$data['title'].'</h2><br>'.$data['text'];
			$Body .= '<font size="1"><br>این پیام از طریق بخش خبرنامه اختصاصی اعضا ارسال شده است.';
			$Body .= '<br>در صورتی که مایل به دریافت آن نیستید می توانید از تنظیمات پروفایل خود آن را غیرفعال کنید.</font>';

			if (send_mail($toname, $toemail, $fromname, $fromemail, $subject, $Body))
			{
				$send++;
			}
			else
			{
				$error++;
			}
		}
	}else{
		while ($row = $d->fetch()) 
		{
			$data['text'] = str_replace("{%username%}", $row['member_name'], $data['text']);
			$arr = array(
				'msg_sender' => member_name,
				'msg_receiver' => $row['member_name'],
				'msg_subject' => $data['title'],
				'msg_text' => $data['text'],
				'msg_date' => time_now ,
			);
			$d->insert('private_messages', $arr);
			if  ($d->affectedRows())
			{
				$send++;
			}
			else
			{
				$error++;
			}
		}
	}
	$start = $start + $send;
	if($send == 0){
		echo "{\"status\" :\"finished\" , \"count\" : \"{$start}\"}";
	}else{
		echo "{\"status\" :\"ok\" , \"count\" : \"{$start}\"}";
	}
	exit;

	
}

function _get_total(){
	global $d;
	$msg = "";
	$data = get_param($_POST, 'newsletter', null,1);
	
	if (empty($data['title']))
	{
		$msg .= '-عنوان خبرنامه را ننوشته اید!<br>';
	}
	
	if (empty($data['text']))
	{
		$msg .= '-متن خبرنامه را ننوشته اید!<br>';
	}
	
	if($data['start'] < 0 || !is_numeric($data['start'])){
		$msg .= '-مقداری که برای شروع وارد کرده اید قابل قبول نیست<br>';
	}
	
	if($data['limit'] < 20 || !is_numeric($data['limit'])){
		$msg .= '-مقدار ارسال در هر نوبت باید حداقل 20 باشد!<br>';
	}
	
	if($data['time'] < 1 || !is_numeric($data['time'])){
		$msg .= '-مقدار فاصله زمانی هر نوبت باید حداقل 1 باشد!<br>';
	}
	
	if (!empty($msg))
	{
		$msg = message($msg,'error');
		echo json_encode(array("status" => "error", "msg" => $msg));
		exit ;
	}
	else{
		$where = array();
		$where[]= "`member_status` = '1'";
		$data = $_POST['newsletter'];
		$data['type'] = ($data['type'] == "pm" ? "pm" : "email");
		if(isset($data['groups']) && is_array($data['groups'])){
			$where[] = "`member_group` IN ('" .implode("','",$data['groups']) . "')";	
		}else{
			$data['groups'] = "";
		}
		$data['all'] = (isset($data['all']) && intval($data['all']) > 0)? 1 : 0 ;
		if($data['all'] == 0) $where[] = "`member_newsletter`='1'";
		
		if (count($where)) $where = "WHERE ".implode (" AND ", $where);
		else $where = "";
		
		$d->query("SELECT COUNT(*) as count FROM #__members ".$where);
		$row = $d->fetch();
		if($row['count'] == 0){
			$msg = message("کاربری با مشخصات وارد شده توسط شما وجود ندارد!",'error');
			echo json_encode(array("status" => "error", "msg" => $msg));
			
		}
		elseif($row['count'] <= $data['start']){
			$msg = message("تعداد کاربران از مقداری که برای شروع انتخاب کرده اید کمتر است!",'error');
			echo json_encode(array("status" => "error", "msg" => $msg));
			
		}else{
			echo json_encode(array("status" => "ok", "total" => $row['count']));
		}
	}
	exit;
}

$_GET['do'] = get_param($_GET, 'do');

switch($_GET['do'])
{
	case 'send':
	_send();
	break;

	case 'total':
	_get_total();
	break;

	default:
	_index();
	break;
}

?>