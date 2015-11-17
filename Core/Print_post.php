<?php

chdir(realpath(dirname(__FILE__))."/../");
require_once('Core/Post.php');
require_once('Core/Accounts.php');
require_once('Core/Location.php');
require_once('Core/Utils.php');
require_once('Core/Miniature.php');


function print_full_post($id, $uid, &$p) {
	
	// init the html to return
	$html_data = "";

	// get the user
	$u = account_load(array('_id' => $uid));
	if($u == null || $u == false) {
		$u = account_getDummy();
	}
	
	// load the post if not provided
	if($p == null) {
		$p = post_load(array('_id'=>$id));
	}
	if($p == null) {
		return "";
	}
	$html_data .= print_post($id, $uid, $p);
	//$html_data .= '<div class="post-separator"></div>';
	// TODO we want to assure that the order of the coms is kept intact
	foreach($p['children'] as $cid) {
		$child_html = print_post($cid, $uid);
		if($child_html != "") {
			//$html_data .= $child_html.'<div class="post-separator"></div>';
			$html_data .= $child_html;
		}
	}
	return $html_data;

}

function print_post($id, $uid, &$p) {

	// init the html to return
	$html_data = "";

	// get the user
	$u = account_load(array('_id' => $uid));
	if($u == null || $u == false) {
		$u = account_getDummy();
	}
	
	// load the post if not provided
	if($p == null) {
		$p = post_load(array('_id'=>$id));
	}
	if($p == null) {
		return "";
	}

	// get the user
	$op = account_load(array('_id' => $p['uid']));

	$html_data .= ' <div data-preview="'.$p['preview'].'" class="post ';
	// parent ?
	if($p['parent'] != null || $p['parent'] != 0) {
		$html_data .= 'child-post';
	} else {
		$html_data .= 'parent-post';
	}
	$html_data .= '
		" data-id="'.$id.'">
		<div class="post-menu">
		<div class="op" data-uid="'.$p['uid'].'">
			<div class="avatar">'.account_getAvatarHTML($op).'</div>
			<div class="post-info">
				<div class="author">'.$op['name'].'</div>
				<div class="date">'.convertDate(date('Y-m-d H:i:s', $p['date']->sec)).'</div>
			</div>
		</div>
	';
	if($p['uid'] == $u['_id']) {
		$html_data .= '
				<div onclick="toggleoptionsmenu(this)" class="options">
					<i class="fa fa-caret-down"></i>
					<div class="options-menu">
						<a onclick="editPost(this)">Editer</a>
						<a onclick="deletePost(this)">Supprimer</a>
					</div>
				</div>
		';
	} 
	$html_data .= ' </div> <div class="';
	if($p['parent'] != null && $p['parent'] != 0) {
		$html_data .= 'post-com-text ';
	} else {
		$html_data .= 'post-parent-text ';
	}
	$html_data .= 'dynamicBox viewerBox" data-id="'.$id.'"><div>'.$p['text'].'</div></div>
	</div>
	';
	// parent ?
	if($p['parent'] == null || $p['parent'] == 0) {
		$html_data .= '<div class="post-separator"></div>';
	}

	return $html_data;
}

function print_post_mini(&$p) {
	$html = "";
	if($p != false && ($p['parent'] == null || $p['parent'] == 0)) {
		if(!file_exists(get_miniature_path($p['preview']))) {
			if($p['preview'] != "") {
				$link = gen_miniature($p['preview']);
			}
			if($link != false && $link != "") {
				$inside = '<img src="'.get_miniature($p['preview']).'?'.time().'"/>';
			} else {
				//var_dump($p['preview']);
				$inside = '<img src="'.p2l(pathTo("placeholder", "assets", "jpg")).'"/>';
			}
		} else {
			$inside = '<img src="'.get_miniature($p['preview']).'?'.time().'"/>';
		}
		$html .= '<a class="material-shadow post-mini" href="#'.$p['_id'].'" data-id="'.$p['_id'].'">'.$inside.'</a>';
	}
	return $html;
}


?>
