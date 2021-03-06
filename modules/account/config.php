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

function account_options()
{
	if (!$options_account = get_cache('options-account'))
	{
		global $d;
		$d->query("SELECT `option_value` FROM `#__options` WHERE `option_name`='account' LIMIT 1");
		$result = $d->fetch();
		$d->freeResult();
		$options_account = maybe_unserialize($result['option_value']);
		set_cache('options-account', $options_account);
	}
	return $options_account;
}

function block_account($op = null, $id = null, $position= null)
{
	if ($op=='remove-cache') // admin
	{
		return true;
	}
	global $d, $member, $options;

	$result = &counter_data();

	$file = get_tpl(root_dir.'modules/account/html/||block.tpl', template_dir.'||account/block.tpl');
	$itpl = new template($file[1], $file[0]);
	
	($hook = get_hook('block_account_start'))? eval($hook) : null;

	if (member)
	{
		$member = member::is('info');
		$messages = is_module('private-messages')? private_messages() : array();
		
		$itpl->assign(array(
			'{name}' => member::group_title($member['member_name'], $member['member_group']),
			'{avatar}' => member::avatar($member['member_avatar']),
			'{newpms}' => isset($messages['newpms'])? (int) $messages['newpms'] : 0,
			'{oldpms}' => isset($messages['oldpms'])? (int) $messages['oldpms'] : 0,
			'{sendpms}' => isset($messages['sendpms'])? (int) $messages['sendpms'] : 0,
			'{allpms}' => isset($messages['allpms'])? (int) $messages['allpms'] : 0,
			'[member]' => null,
			'[/member]' => null,
		));
		$itpl->block('#\\[guest\\](.*?)\\[/guest\\]#s', '');
	}
	else
	{
		$itpl->assign(array(
			'{name}' => 'مهمان',
			'[guest]' => null,
			'[/guest]' => null,
		));
		$itpl->block('#\\[member\\](.*?)\\[/member\\]#s', '');
	}

	$online = $d->query("
		SELECT (SELECT COUNT(*) FROM `#__session` WHERE `session_guest`='1') count_guest,
		(SELECT COUNT(*) FROM `#__session` WHERE `session_guest`='0') count_member;
	");
	$online = $d->fetch($online);

	$itpl->assign(array(
		'{ip}' => get_ip(),
		'{last-member}' => $result['memberNewName'],
		'{members-today}' => (int) $result['membersToday'],
		'{members-yesterday}' => (int) $result['membersYesterday'],
		'{members-month}' => (int) $result['membersMonth'],
		'{members-total}' => (int) $result['membersCount'],
		'{count-guest}' => (int) $online['count_guest'],
		'{count-member}' => (int) $online['count_member'],
		'{count-total}' => (int) ($online['count_guest'] + $online['count_member']),
	));
	
	if (file_exists(engine_dir.'GeoIP/geoip.php'))
	{
		require_once(engine_dir.'GeoIP/geoip.php');
		$geoip = geoip_open(engine_dir.'GeoIP/GeoIP.dat', 1);
	}

	$online = $d->query("
		SELECT s.*,m.member_group
		FROM #__session AS s
		LEFT JOIN #__members AS m ON (session_guest='0' AND m.member_name=s.session_member) 
		GROUP BY s.session_member
		ORDER BY s.session_guest ASC, s.session_member DESC
	");
	while($row = $d->fetch($online))
	{
		$array = array(
			'{name}' => !empty($row['member_group']) && $row['session_guest']==0? member::group_title($row['session_member'], $row['member_group']) : $row['session_member'],
			'{member}' => $row['session_member'],
			'{ip}' => $row['session_ip'],
			'{page}' => strpos($row['session_page'], '?admin='.$options['admin']) !== FALSE? 'javascript:alert(\'صفحه محرمانه!\')' : $row['session_page'],
		);
		
		if ($row['session_guest'] == 1)
		{
			$array['{name}'] = explode('.', $row['session_ip'], 2); // 0.0.0.0
			$array['{name}'] = array_map('intval', $array['{name}']);
			$array['{name}'] = (isset($array['{name}'][0])? $array['{name}'][0] : 'xx') .'.'. (isset($array['{name}'][1])? $array['{name}'][1] : 'xx') .'.xx.xx';
		}
		
		if ($row['session_guest'] == 1)
		{
			$array['[guest]'] = null;
			$array['[/guest]'] = null;
			$array['replace']['#\\[user\\](.*?)\\[/user\\]#s'] = '';
		}
		else
		{
			$array['[user]'] = null;
			$array['[/user]'] = null;
			$array['replace']['#\\[guest\\](.*?)\\[/guest\\]#s'] = '';
		}
		
		if (isset($geoip))
		{
			$array['{flag}'] = strtolower(geoip_country_code_by_addr($geoip, $row['session_ip']));
			$array['{flag}'] = file_exists(root_dir.'modules/counter/images/flags/'.$array['{flag}'].'.gif')? $array['{flag}'] : 'xx';
			$array['{country}'] = $array['{flag}']=='xx'? 'Unknown' : geoip_country_name_by_addr($geoip, $row['session_ip']);
			$array['[flag]'] = null;
			$array['[/flag]'] = null;
		}
		else
		{
			$array['replace']['#\\[flag\\](.*?)\\[/flag\\]#s'] = '';
		}

		$itpl->add_for('online', $array);
	};

	($hook = get_hook('block_account_end'))? eval($hook) : null;

	if (isset($geoip))
	{
		geoip_close($geoip);
		unset($geoip);
	}
	unset($file, $array, $online, $messages, $row);
	return $itpl->get_var();	
}

function block_onlines($op = null, $id = null, $position= null)
{
	if ($op=='remove-cache') // admin
	{
		return true;
	}
	global $d, $options;

	$file = get_tpl(root_dir.'modules/account/html/||block-onlines.tpl', template_dir.'||account/block-onlines.tpl');
	$itpl = new template($file[1], $file[0]);

	($hook = get_hook('block_onlines_start'))? eval($hook) : null;

	if (file_exists(engine_dir.'GeoIP/geoip.php'))
	{
		require_once(engine_dir.'GeoIP/geoip.php');
		$geoip = geoip_open(engine_dir.'GeoIP/GeoIP.dat', 1);
	}

	$online = $d->query("
		SELECT s.*,m.member_group
		FROM #__session AS s
		LEFT JOIN #__members AS m ON (session_guest='0' AND m.member_name=s.session_member) 
		GROUP BY s.session_member
		ORDER BY s.session_guest ASC, s.session_member DESC
	");
	while ($row = $d->fetch($online))
	{
		$array = array(
			'{name}' => !empty($row['member_group']) && $row['session_guest']==0? member::group_title($row['session_member'], $row['member_group']) : $row['session_member'],
			'{member}' => $row['session_member'],
			'{ip}' => $row['session_ip'],
			'{page}' => strpos($row['session_page'], '?admin='.$options['admin']) !== FALSE? 'javascript:alert(\'صفحه محرمانه!\')' : $row['session_page'],
		);
		
		if ($row['session_guest'] == 1)
		{
			$array['{name}'] = explode('.', $row['session_ip'], 2); // 0.0.0.0
			$array['{name}'] = array_map('intval', $array['{name}']);
			$array['{name}'] = (isset($array['{name}'][0])? $array['{name}'][0] : 'xx') .'.'. (isset($array['{name}'][1])? $array['{name}'][1] : 'xx') .'.xx.xx';
		}
		
		if ($row['session_guest'] == 1)
		{
			$array['[guest]'] = null;
			$array['[/guest]'] = null;
			$array['replace']['#\\[user\\](.*?)\\[/user\\]#s'] = '';
		}
		else
		{
			$array['[user]'] = null;
			$array['[/user]'] = null;
			$array['replace']['#\\[guest\\](.*?)\\[/guest\\]#s'] = '';
		}
		
		if (isset($geoip))
		{
			$array['{flag}'] = strtolower(geoip_country_code_by_addr($geoip, $row['session_ip']));
			$array['{flag}'] = file_exists(root_dir.'modules/counter/images/flags/'.$array['{flag}'].'.gif')? $array['{flag}'] : 'xx';
			$array['{country}'] = $array['{flag}']=='xx'? 'Unknown' : geoip_country_name_by_addr($geoip, $row['session_ip']);
			$array['[flag]'] = null;
			$array['[/flag]'] = null;
		}
		else
		{
			$array['replace']['#\\[flag\\](.*?)\\[/flag\\]#s'] = '';
		}

		$itpl->add_for('online', $array);
	};

	($hook = get_hook('block_onlines_end'))? eval($hook) : null;

	if (isset($geoip))
	{
		geoip_close($geoip);
		unset($geoip);
	}
	unset($file, $array, $online, $row);
	return $itpl->get_var();	
}

function block_login($op = null, $id = null, $position= null)
{
	if ($op=='remove-cache') // admin
	{
		return true;
	}

	global $member;

	$file = get_tpl(root_dir.'modules/account/html/||block-login.tpl', template_dir.'||account/block-login.tpl');
	$itpl = new template($file[1], $file[0]);
	
	($hook = get_hook('block_login_start'))? eval($hook) : null;

	if (member)
	{
		$member = member::is('info');
		$messages = is_module('private-messages')? private_messages() : array();
		$messages = isset($messages['newpms'])? (int) $messages['newpms'] : 0;
		
		$itpl->assign(array(
			'{avatar}' => member::avatar($member['member_avatar']),
			'{name}' => member::group_title($member['member_name'], $member['member_group']),
			'{private-messages}' => $messages,
			'[member]' => null,
			'[/member]' => null,
		));
		$itpl->block('#\\[guest\\](.*?)\\[/guest\\]#s', '');
		
		if ($messages > 0)
		{
			$itpl->assign(array(
				'[private-messages]' => null,
				'[/private-messages]' => null,
			));
		}
		else
		{
			$itpl->block('#\\[private-messages\\](.*?)\\[/private-messages\\]#s', '');
		}
	}
	else
	{
		$itpl->assign(array(
			'{name}' => 'مهمان',
			'[guest]' => null,
			'[/guest]' => null,
		));
		$itpl->block('#\\[member\\](.*?)\\[/member\\]#s', '');
	}

	($hook = get_hook('block_login_end'))? eval($hook) : null;

	unset($file, $messages);
	return $itpl->get_var();	
}

function module_account_run()
{
	global $d, $options, $member;

	require_once(root_dir.'modules/account/functions.php');
	$_GET['b'] = get_param($_GET, 'b');

	switch($_GET['b'])
	{
		case 'register':
		_register();
		break;

		case 'login':
		_login();
		break;

		case 'logout':
		_logout();
		break;
		
		case 'members':
		_members();
		break;
		
		case 'profile':
		_profile();
		break;

		case 'profile-edit':
		_profile_edit();
		break;

		case 'change-password':
		_change_password();
		break;

		case 'change-avatar':
		_change_avatar();
		break;
		
		case 'remove-avatar':
		_remove_avatar();
		break;

		case 'forget':
		_forget();
		break;

		default:
		if (!member)
			redirect(url('account/login'));

		_index();
		break;
	}
}

function module_account_sitemap($sitemap)
{
	$sitemap->addItem(url('account/members'), 0, 'daily', '0.6');
	$sitemap->addItem(url('account/register'), 0, 'never', '0.6');
	$sitemap->addItem(url('account/login'), 0, 'never', '0.6');
	$sitemap->addItem(url('account/forget'), 0, 'never', '0.6');
}

?>