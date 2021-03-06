<?php
namespace content_cp\posts;

use \lib\utility;
use \lib\debug;

class model extends \content_cp\home\model
{
	// ---------------------------------------------------- handle all type of request used for all common modules
	function get_delete()
	{
		// Check permission and if user can do this operation
		// allow to do it, else show related message in notify center
		$this->access('cp', 'posts', 'delete', 'block');

		$this->delete( $this->sql()->table('posts')->where('id', $this->childparam('delete')));
	}

	function delete_delete()
	{
		var_dump(0);exit();
		$this->delete( $this->sql()->table('posts')->where('id', $this->childparam('delete')));
	}

	function post_delete()
	{
		// Check permission and if user can do this operation
		// allow to do it, else show related message in notify center
		$this->access('cp', 'posts', 'delete', 'notify');

		$this->delete( $this->sql()->table('posts')->where('id', $this->childparam('delete')));
	}

	function post_add()
	{
		// Check permission and if user can do this operation
		// allow to do it, else show related message in notify center
		$this->access('cp', 'posts', 'add', 'notify');

		if($this->module() === 'attachments')
			$this->sp_attachment_add();
		else
			$this->cp_create_query();
	}

	function put_edit()
	{
		// Check permission and if user can do this operation
		// allow to do it, else show related message in notify center
		$this->access('cp', 'posts', 'edit', 'notify');

		$this->cp_create_query();
	}

	function post_options()
	{
		return 'soon';
	}

	/**
	 * -------------------------------------------------------- our custom code for this module is below this line

	 */


	/**
	 * this function set custom operator for each custom module in cp
	 * @param  [type] $_id [description]
	 * @return [type]      [description]
	 */
	function cp_create_query($_id = null)
	{
		if(!$_id)
			$_id  = $this->childparam('edit');

		// if don't set title return error
		if(!utility::post('title'))
		{
			debug::error(T_("Please enter title!"));
			return false;
		}


		// remove this line!
		$mymodule = $this->cpModule('raw');



		// set useful variables
		$datarow                = array();
		$cpModule              = $this->cpModule();
		$qry                   = $this->sql()->table('posts');
		// set all variable get form all type of forms
		$datarow['language']    = utility::post('language');
		$datarow['title']       = utility::post('title');
		$datarow['slug']        = utility::post('slug', 'filter');
		$datarow['content']     = utility::post('desc');
		$datarow['type']        = $cpModule['type'];
		$datarow['url']         = null;
		$datarow['status']      = utility::post('status');
		$datarow['parent']      = utility::post('parent');
		$datarow['user_id']     = $this->login('id');
		$datarow['publishdate'] = date('Y-m-d H:i:s');

		// read post meta and rewrite it
		$datarow['meta']        = $this->sql()->table('posts')->where('id', $_id)->select()->assoc('post_meta');
		$datarow['meta']        = json_decode($datarow['meta'], true);

		// meta fields
		$datarow['meta']['thumbid'] = utility::post('thumbid');
		$datarow['meta']['slug']    = $datarow['slug'];

		$datarow['meta'] = json_encode($datarow['meta']);





		// set slug if is not set
		if(!$datarow['slug'])
			$datarow['slug'] = utility\Filter::slug($datarow['title']);


		switch ($cpModule['raw'])
		{
			case 'pages':
			case 'books':
				// calc and set url
				if($datarow['parent'])
				{
					$datarow['url'] = $this->sql()->table('posts')
						->where('post_type', $cpModule['type'])->and('id', $datarow['parent'])
						->select()->assoc('post_url').'/'.$datarow['slug'];
				}
				else
				{
					$datarow['parent'] = '#NULL';
					$datarow['url']    = $datarow['slug'];
				}

				if($cpModule['raw'] === 'books')
				{
					$datarow['url'] = 'book/' . preg_replace("#^(book\/)+#", "", $datarow['url']);
				}
				break;


			// only on edit
			case 'attachments':
				// remove unuse fields like slug, url, data, status, ...
				// commented row not deleted and check
				unset($datarow['language']);
				// unset($datarow['title']);
				// unset($datarow['slug']);
				// unset($datarow['content']);
				unset($datarow['type']);
				unset($datarow['url']);
				// unset($datarow['status']);
				unset($datarow['parent']);
				// unset($datarow['user_id']);
				unset($datarow['publishdate']);

				if(utility::post('cat'))
					$cat = utility::post('cat');
				else
					$cat = 'file';
				$datarow['url'] = $cat . '/'. $datarow['slug'];
				$datarow['url'] = trim($datarow['url'], '/');

				// // read post meta and rewrite it
				// $datarow['meta'] = $this->sql()->table('posts')
				// 		->where('post_type', 'attachment')->and('id', $_id)
				// 		->select()->assoc('post_meta');

				// $datarow['meta'] = json_decode($datarow['meta'], true);
				// $datarow['meta']['slug'] = $datarow['slug'];
				// $datarow['meta'] = json_encode($datarow['meta']);

				unset($datarow['slug']);
				// var_dump(utility::post('cat'));
				// var_dump($datarow['meta']);
				// exit();
				break;

			case 'socialnetwork':
				$datarow['slug'] = 'social'.md5(time());
				$datarow['url'] = 'social/'.$datarow['slug'];
				$datarow['status'] = 'draft';
				// print_r($datarow);
				// exit();
			break;

			// all other type of post
			default:
				unset($datarow['parent']);
				$datarow['url'] = utility::post('cat');

				// create url with selected cat
				if($cpModule['raw'] === 'books')
				{
					$datarow['url'] = 'books';
				}
				elseif(!$datarow['url'])
				{
					// calc and set url
					$datarow['url'] = $this->sql()->table('terms')->where('id', 1)
						->select()->assoc('term_url');
				}

				if($datarow['url'])
					$datarow['url'] = $datarow['url'].'/';
				$datarow['url']  = $datarow['url']. $datarow['slug'];
				break;
		}

		// if in edit get this record data

		if($_id)
		{
			$record = $this->sql()->table('posts')->where('id', $_id)->select()->assoc();
			$record_meta = $this->sql()->table('options')->where('post_id', $_id)->order('id','asc')->select()->allassoc();

			// fill options value like posts field
			foreach ($record_meta as $key => $value)
				$record[$record_meta[$key]['option_key']] = $record_meta[$key]['option_value'];
		}
		$changed = false;


		// set values if exist
		foreach ($datarow as $key => $value)
		{
			$key = $key === 'user_id'? 'user_id': 'post_'.$key;
			if($_id)
			{
				// check with old data and if change then set it
				if($record[$key] !== $value)
				{
					$qry     = $qry->set($key, $value);
					$changed = true;
				}
			}
			elseif($value)
				$qry = $qry->set($key, $value);
		}


		$post_new_id = $_id;
		if($_id)
		{
			// on edit
			if($changed)
				$qry = $qry->where('id', $_id)->update();
		}
		else
		{
			// on add
			$qry         = $qry->insert();
			$post_new_id = $qry->LAST_INSERT_ID();
		}



		if($post_new_id === 0 || !$post_new_id)
			return;


		// if publish post share it on twitter and save in options
		// before share check db for share before
		// if on add or in edit and staus exist and status !== 400
		// then if status == publish and changed from old position
		$post_status = isset($record['post_status'])? $record['post_status']: null;
		$post_type   = isset($record['post_type'])? $record['post_type'] : null;
		$post_type = ($post_type) ? $post_type : $cpModule['type'];

		if($datarow['status'] === 'publish' && $datarow['status'] !== $post_status && $post_type === 'post')
		{
			$url_main = $this->url('MainProtocol'). '://'.$this->url('MainSite');
			if(!(isset($record['twitter']['status']) && $record['twitter']['status'] === 400 ))
			{
				$mytwitte = $datarow['title'] . ' '. $url_main.'/'.$datarow['url'];
				$twitte_result = \lib\utility\SocialNetwork::twitter($mytwitte);
				if(isset($twitte_result) && isset($twitte_result['status']))
				{
					$twitte_result = json_encode($twitte_result);

					$qry_twitter = $this->sql()->table('options')
						->set('post_id',      $post_new_id)
						->set('option_cat',   'post'. $post_new_id. '_SocialNetwork')
						->set('option_key',   'twitter')
						->set('option_value', $twitte_result);
					// $qry_twitter = $qry_twitter->insertString();
					// var_dump($qry_twitter);
					$qry_twitter = $qry_twitter->insert();
				}

			}
			$telegram = \lib\utility\SocialNetwork::telegram($datarow['title'] . "\n". $url_main.'/'.$datarow['url']);

			$facebook_content = html_entity_decode($datarow['content']);
			$facebook_content = preg_replace("/<\/p>/", "\n", $facebook_content);
			$facebook_content = preg_replace("/<[^>]+>/", "", $facebook_content);
			$facebook_content = preg_replace("/^[\s\n\r\t]+/", "", $facebook_content);

			$facebook_url = $url_main.'/'.$datarow['url'];

			$result_fb = \lib\utility\SocialNetwork::facebook($facebook_url, $facebook_content);


				if(isset($result_fb))
				{
					// $result_fb = json_encode($result_fb);

					$qry_facebook = $this->sql()->table('options')
						->set('post_id',        $post_new_id)
						->set('option_cat',   'post'. $post_new_id. '_SocialNetwork')
						->set('option_key',   'facebook')
						->set('option_value', $result_fb);
					// $qry_facebook = $qry_facebook->insertString();
					$qry_facebook = $qry_facebook->insert();
				}
		}


		// add tags to terms table
		$mycats        = utility::post('categories');
		// if(!$mycats)
		// 	$mycats = [1];
		$mytags        = utility::post('tags');
		$mytags        = explode(',', $mytags);
		foreach ($mytags as $key => $value)
		{
			$value = trim($value," ");
			$value = trim($value,"'");

			if($value)
				$mytags[$key] = $value;
			else
				unset($mytags[$key]);
		}


		// --------------------------------------------------- check new tag and cats with old one on edit
		if($_id)
		{
			$myterms_del = null;

			// get old tags and diff of it with new one by title of tags
			$old_tags     = $this->sp_term_list('tag', false);
			$tags_diff    = array_diff( $old_tags, $mytags );
			if(count($tags_diff)>0)
			{
				// get the list of tags id
				$tags_id      = $this->cp_tag_id($tags_diff);
				$myterms_del  = $tags_id;
			}


			// get old cats and diff of it with new one by id
			if($cpModule['raw'] === 'attachments')
			{
				$old_cats     = $this->sp_term_list('filecat', false);
				if(!is_array($mycats))
					$mycats = null;
			}
			elseif($cpModule['raw'] === 'books')
			{
				$old_cats     = $this->sp_term_list('bookcat', false);
				if(!is_array($mycats))
					$mycats = null;
			}
			else
			{
				$old_cats     = $this->sp_term_list('cat', false);
				if(!is_array($mycats))
					$mycats = [1];
			}



			if(is_array($old_cats) && count($old_cats) && is_array($mycats) && count($mycats))
				$cats_diff    = array_diff( $old_cats, $mycats );
			elseif(is_array($mycats) && count($mycats))
				$cats_diff    = $mycats;
			else
				$cats_diff    = $old_cats;



			if(is_array($cats_diff) && count($cats_diff)>0)
			{
				$cats_diff    = implode(",", $cats_diff);
				if($myterms_del)
					$myterms_del .= ',';
				$myterms_del .= $cats_diff;
			}
			// var_dump($myterms_del);
			// exit();

			// delete deleted tags and cats together in one query
			if($myterms_del)
			{
				$qry_term_del = $this->sql()->table('termusages')->where('termusage_id', $post_new_id );
				if(count(explode(',', $myterms_del)) === 1)
					$qry_term_del = $qry_term_del->and('term_id', '=', $myterms_del)->delete();
				else
					$qry_term_del = $qry_term_del->and('term_id', 'in', "(". $myterms_del .")" )->delete();
			}
		}


		// ------------------------------------------------- if user enter new tag
		$tags_id = array();
		if(count($mytags)>0)
		{
			$qry_tag = $this->sql()->table('terms');
			// add each tag to sql syntax
			foreach ($mytags as $value)
			{
				if($value)
				{
					$qry_tag = $qry_tag
						->set('term_type',  'tag')
						->set('term_title',  $value)
						->set('term_slug',   $value)
						->set('term_url',    $value);
				}
			}
			// var_dump($qry_tag->insertString('IGNORE'));exit();
			$qry_tag->insert('IGNORE');


			// get the list of tags id
			$tags_id = $this->cp_tag_id($mytags, false);
			// var_dump($tags_id);
			if(!is_array($tags_id))
				$tags_id = array();
		}


		// add selected tag to term usages table
		// on pages dont need cats and only add tags
		if($mymodule === 'pages')
			$myterms = $tags_id;
		elseif(is_array($mycats) && count($mycats))
			$myterms = array_merge($tags_id, $mycats);
		else
			$myterms = $tags_id;



		// ---------------------------------------------- set termusage table
		// if terms exist go to foreach
		if(isset($myterms) && count($myterms)>0)
		{
			$qry_tagusages = $this->sql()->table('termusages');
			foreach ($myterms as $value)
				$qry_tagusages = $qry_tagusages
					->set('term_id',           $value)
					->set('termusage_id',      $post_new_id)
					->set('termusage_foreign', 'posts');
			// var_dump($qry_tagusages->insertString());exit();
			$qry_tagusages->insert('IGNORE');
		}



		// update post url
		// $post_url = utility::post('slug', 'filter');
		// $this->sql()->table('posts')->set('post_url', $post_url)
			// ->where('id', $post_new_id)->update();


		// ======================================================
		// you can manage next event with one of these variables,
		// commit for successfull and rollback for failed
		// if query run without error means commit
		if($cpModule['raw'] == 'socialnetwork'){
			$twitte_result = \lib\utility\SocialNetwork::telegram($datarow['content']);
		}
		$this->commit(function($_module, $_postId, $_edit = null)
		{
			if($_edit)
			{
				debug::true(T_("Update Successfully"));
				$this->redirector()->set_url($_module.'/edit='.$_postId);
			}
			else
			{
				debug::true(T_("Insert Successfully"));
				$this->redirector()->set_url($_module.'/edit='.$_postId);
			}
		}, $mymodule, $post_new_id, $_id );

		// if a query has error or any error occour in any part of codes, run roolback
		$this->rollback(function()
		{
			debug::title(T_("Transaction error").': ');
		} );
	}



	/**
	 * Upload file to project files and database
	 * @return [type] [description]
	 */
	function sp_attachment_add()
	{
		$FOLDER_SIZE = 1000;
		// 1. check upload process and validate it
		$invalid = utility\Upload::invalid('upfile');
		if($invalid)
		{
			debug::property('status','fail');
			debug::property('error', $invalid);

			$this->_processor(['force_json'=>true, 'not_redirect'=>true]);
			return false;
		}


		// 2. Generate file_id, folder_id and url
		$qry_count = $this->sql()->table('posts')->where('post_type', 'attachment')->select('id')->num();
		$folder_prefix = "files/";
		$folder_id = $folder_prefix . ceil(($qry_count+1) / $FOLDER_SIZE);
		$file_id   = $qry_count % $FOLDER_SIZE + 1;
		$url_full  = "$folder_id/$file_id-" . utility\Upload::$fileFullName;



		// 3. Check for record exist in db or not
		$qry_count = $this->sql()->table('posts')->where('post_slug', utility\Upload::$fileMd5)->select('id');
		if($qry_count->num())
		{
			$id = $qry_count->assoc('id');
			debug::property('status','fail');
			$link = '<a target="_blank" href=/attachments/edit='. $id. '>'. T_('Duplicate - File exist').'</a>';
			debug::property('error', $link);

			$this->_processor(['force_json'=>true, 'not_redirect'=>true]);
			return false;
		}

		// 4. transfer file to project folder with new name
		if(!utility\Upload::transfer($url_full, $folder_id))
		{
			debug::property('status', 'fail');
			debug::property('error', T_('Fail on tranfering file'));

			$this->_processor(['force_json'=>true, 'not_redirect'=>true]);
			return false;
		}
		$file_ext   = utility\Upload::$fileExt;
		$url_thumb  = null;
		$url_normal = null;

		switch ($file_ext)
		{
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
				$extlen = strlen(utility\Upload::$fileExt);
				$url_file = substr($url_full, 0, -$extlen-1);
				$url_thumb = $url_file.'-thumb.'.utility\Upload::$fileExt;
				$url_normal = $url_file.'-normal.'.utility\Upload::$fileExt;
				// var_dump($thumb_url);
				// exit();
				utility\Image::load($url_full);
				utility\Image::thumb(600, 400);
				utility\Image::save($url_normal);

				utility\Image::thumb(150, 150);
				utility\Image::save($url_thumb);
				break;
		}

		// 5. get filemeta data
		$file_meta = [
						'mime'   => utility\Upload::$fileMime,
						'type'   => utility\Upload::$fileType,
						'size'   => utility\Upload::$fileSize,
						'ext'    => $file_ext,
						'url'    => $url_full,
						'thumb'  => $url_thumb,
						'normal' => $url_normal,
					 ];
		$page_url  = $file_meta['type'].'/'.substr($url_full, strlen($folder_prefix));

		if( strpos($file_meta['mime'], 'image') !== false)
			list($file_meta['width'], $file_meta['height'])= getimagesize($url_full);
		$file_meta = json_encode($file_meta);
		// var_dump($file_meta);exit();

		// 6. add uploaded file record to db
		$qry = $this->sql();
		$qry = $qry->table('posts')
					->set('post_title',       utility\Upload::$fileName)
					->set('post_slug',        utility\Upload::$fileMd5)
					->set('post_meta',        $file_meta)
					->set('post_type',        'attachment')
					->set('post_url',         $page_url)
					->set('user_id',          $this->login('id'))
					->set('post_status',      'draft')
					->set('post_publishdate', date('Y-m-d H:i:s'));

		$qry         = $qry->insert();
		$post_new_id = $qry->LAST_INSERT_ID();






		// 7. commit all changes or rollback and remove file
		// ======================================================
		// you can manage next event with one of these variables,
		// commit for successfull and rollback for failed
		// if query run without error means commit
		$this->commit(function($_id)
		{
			debug::property('status', 'ok');
			$link = '<a target="_blank" href=/attachments/edit='.$_id.'>'. T_('Edit').'</a>';
			debug::property('edit', $link);

		}, $post_new_id);

		// if a query has error or any error occour in any part of codes, run roolback
		$this->rollback(function()
		{
			debug::property('status', 'fail');
			debug::property('error', T_('Error'));
			// remove file if has problem
		});

		$this->_processor(['force_json'=>true, 'not_redirect'=>true]);
	}





	// ----------------------------------------------------------------------- Other Useful Queries

	/**
	 * send list of tag title and get list of it's id
	 * @param  [type]  $_list   list received by func
	 * @param  boolean $_string type of output
	 * @return [type]           send depending on type of output
	 */
	function cp_tag_id($_list, $_string = true )
	{
		// get the list of tags
		$qry_tags  = $this->sql()->table('terms')->where('term_type', 'tag');
		$_list     = array_filter($_list);

		if(is_array($_list))
		{
			if(count($_list) === 1)
			{
				// use =
				$qry_tags = $qry_tags->and('term_title', '=', "'".array_pop($_list)."'");
			}
			else
			{
				// use IN
				$_list = implode("','", $_list);
				$_list = "'" . $_list."'";

				$qry_tags = $qry_tags->and('term_title', 'IN', "(". $_list . ")");
			}
		}

		// set field name and assoc all rows
		// var_dump($qry_tags->field('id')->selectString());
		// var_dump($qry_tags->select()->num());
		// var_dump($qry_tags);
		$qry_tags = $qry_tags->field('id')->select()->allassoc('id');

		if($qry_tags)
		{
			if($_string)
			{
				if(count($qry_tags) === 1 && isset($qry_tags[0]))
					return $qry_tags[0];
				else
					return implode(",", $qry_tags);
			}

			return $qry_tags;
		}
		return null;
	}


	/**
	 * create the list of cats and if in edit check selected cats
	 * @param  boolean $_tree create tree
	 * @return [type]         datatable
	 */
	public function sp_cats($_type = 'cat', $_all = true)
	{
		$id = $this->childparam('edit');
		if($id && $this->cpModule('raw') === 'books')
		{
			// get the list of cats
			$myslug = $this->sql()->table('posts')->where('id', $id)->select()->assoc('post_slug');

			$myslug_exist = $this->sql()->table('terms')
				->where('term_type', $_type)
				->and('term_url', 'LIKE', "'book-index/$myslug%'")
				->select()->num();
		}

		$recordsNo = $this->sql()->table('terms')->where('term_type', $_type)->select()->num();
		if($recordsNo > 100)
		{
			// get the list of cats
			$datatable = $this->sql()->table('terms')
				->where('term_type', $_type)
				->and('term_parent', 'IS', 'NULL')
				->field('id', 'term_title', 'term_url', 'term_parent')
				->order('term_parent','ASC')->order('id','ASC');
		}
		else
		{
			// get the list of cats
			$datatable = $this->sql()->table('terms')
				->where('term_type', $_type)
				->field('id', 'term_title', 'term_url', 'term_parent')
				->order('term_parent','ASC')->order('id','ASC');
		}



		// show related category of books if exist
		if(isset($myslug) && isset($myslug_exist) && $myslug_exist)
		{
			// $datatable = $datatable->and('term_slug', $myslug);
			$datatable = $datatable->and('term_url', 'LIKE', "'book-index/$myslug%'");

		}

		// get the list of cats
		$datatable = $datatable->select()->allassoc();


		// if in edit continue else return raw list
		if($id)
		{
			// get list of selected cats
			$qry_selected = $this->sql()->table('termusages')
				->where('termusage_foreign', '#"posts"')
				->and('termusage_id', $id)
				->select()
				->allassoc('term_id');
		}



		$result = array();

		foreach ($datatable as $id => $row)
		{
			$result[$row['id']] = array();
			$result[$row['id']]['id']  = $row['id'];
			$result[$row['id']]['url'] = $row['term_url'];


			// if this item selected mark it as selected
			if(isset($qry_selected) && in_array($row['id'], $qry_selected))
				$result[$row['id']]['selected'] = true;

			if($row['term_parent'] && array_key_exists($row['term_parent'], $result))
			{
				$parent_title = $result[$row['term_parent']]['title'];
				if($parent_title)
				{
					// if not exist search in all of array and find a parent
					// var_dump($parent_title);
				}

				$result[$row['id']]['title'] = $parent_title . " &gt; " . $row['term_title'];
			}
			else
			{
				$result[$row['id']]['title'] = $row['term_title'];
			}

		}

		// var_dump($result);
		return $result;
	}


	/**
	 * get the list of terms
	 * @param  string  $_type   type of terms
	 * @param  boolean $_string create string of it
	 * @return [type]           return string or dattable
	 */
	public function sp_term_list($_type = 'tag', $_string = true)
	{
		$id = $this->childparam('edit');
		if(!$id)
			return null;


		// SELECT DISTINCT
		// terms.term_title
		// FROM
		// terms
		// INNER JOIN termusages ON termusages.term_id = terms.id
		// WHERE
		// terms.term_type = 'tag'

		$qry = $this->sql()->table('terms')
			->where('term_type', $_type)
			->field('term_title');

		$qry->joinTermusages()->on('term_id', '#terms.id')
			->and('termusage_foreign', '#"posts"')
			->and('termusage_id', $id);


		// echo "<pre>";
		// var_dump($qry->selectString());
		// exit();

		if($_type === 'tag')
		{
			$qry = $qry->select()->allassoc('term_title');
		}
		else
		{
			$qry = $qry->select()->allassoc('term_id');
			// var_dump($qry);
		}

		if($_string)
			$qry = $qry? implode($qry, ', ').', ' : null;


		return $qry;
	}


	/**
	 * get the list of pages
	 * @param  boolean $_select for use in select box
	 * @return [type]           return string or dattable
	 */
	public function sp_parent_list($_select = true, $_type = 'page')
	{

		$qry = $this->sql()->table('posts')->where('post_type', $_type)->and('post_status', 'publish')
			->and('post_parent', 'IS', 'NULL')
			->order('post_parent','ASC')->order('id','ASC');

		if($_select)
			$qry = $qry->field('id', 'post_title', 'post_parent');

		// var_dump($qry->selectString());
		$datatable = $qry->select()->allassoc();
		// var_dump($datatable);
		$result = array();

		foreach ($datatable as $id => $row)
		{
			// var_dump($row['id']);
			if($row['post_parent'] && array_key_exists($row['post_parent'], $result))
			// if($row['post_parent'] )
			{
				$parent_title = $result[$row['post_parent']];
				if($parent_title)
				{
					// if not exist search in all of array and find a parent
					// var_dump($parent_title);
				}

				$result[$row['id']] = $parent_title . " &gt; " . $row['post_title'];
			}
			else
			{
				$result[$row['id']] = $row['post_title'];
			}
		}

		return $result;
	}
}
?>