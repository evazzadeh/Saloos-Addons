<?php
namespace content_cp\terms;

use \lib\utility;
use \lib\debug;

class model extends \content_cp\home\model
{
	// ---------------------------------------------------- handle all type of request used for all common modules
	function get_delete()
	{
		$this->delete( $this->sql()->table('terms')->where('id', $this->childparam('delete')));
	}

	function delete_delete()
	{
		var_dump(0);exit();
		$this->delete( $this->sql()->table('terms')->where('id', $this->childparam('delete')));
	}

	function post_delete()
	{
		$this->delete( $this->sql()->table('terms')->where('id', $this->childparam('delete')));
	}

	function post_add()
	{
		$this->cp_create_query();
	}

	function put_edit()
	{
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

		$cpModule          = $this->cpModule();
		$mymodule          = $this->cpModule('raw');
		$qry               = $this->sql();
		$datarow           = array();
		$datarow['slug']   = utility::post('slug', 'filter');
		$datarow['parent'] = utility::post('parent');

		if(!$datarow['slug'])
			$datarow['slug'] = utility\Filter::slug(utility::post('title'));

		if($datarow['parent'])
		{
			$datarow['url'] = $this->sql()->table('terms')
				->where('id', $datarow['parent'])
				->select()->assoc('term_url').'/'.$datarow['slug'];
		}
		else
		{
			$datarow['parent'] = '#NULL';
			$datarow['url']    = $datarow['slug'];
		}

		if($cpModule['raw'] === 'bookcategories')
			$datarow['url'] = 'book-index/' . preg_replace("#^(book-index\/)+#", "", $datarow['url']);

		// var_dump($datarow['slug']);exit();

		if(utility::post('title'))
		{
			$qry = $qry->table('terms')
						->set('term_type',     $cpModule['type'])
						->set('term_language', utility::post('language'))
						->set('term_title',    utility::post('title'))
						->set('term_slug',     $datarow['slug'])
						->set('term_desc',     utility::post('desc'))
						->set('term_parent',   $datarow['parent'])
						->set('term_url',      $datarow['url']);
		}
		else
		{
			debug::error(T_("Please enter title!"));
			return false;
		}


		$post_new_id = null;
		if($_id)
		{
			// on edit
			$qry         = $qry->where('id', $_id)->update();
			$post_new_id = $_id;
		}
		else
		{
			// on add
			$qry         = $qry->insert();
			$post_new_id = $qry->LAST_INSERT_ID();
		}

		// ======================================================
		// you can manage next event with one of these variables,
		// commit for successfull and rollback for failed
		// if query run without error means commit
		$this->commit(function($_module, $_postId, $_edit = null)
		{
			if($_edit)
			{
				debug::true(T_("Update Successfully"));
				// $this->redirector()->set_url($_module.'/edit='.$_postId);
			}
			else
			{
				debug::true(T_("Insert Successfully"));
				$this->redirector()->set_url($_module.'/add');
				// $this->redirector()->set_url($_module.'/edit='.$_postId);
			}
		}, $mymodule, $post_new_id, $_id );

		// if a query has error or any error occour in any part of codes, run roolback
		$this->rollback(function()
		{
			debug::title(T_("Transaction error").': ');
		} );
	}


	/**
	 * get the list of pages
	 * @param  boolean $_select for use in select box
	 * @return [type]           return string or dattable
	 */
	public function sp_category_list($_type = 'cat', $_select = true)
	{

		$qry = $this->sql()->table('terms')->where('term_type', $_type)
			->and('term_parent', "IS", "NULL")
			->order('term_parent','ASC')->order('id','ASC');

		if($_select)
			$qry = $qry->field('id', 'term_title', 'term_url', 'term_parent');


		$datatable = $qry->select()->allassoc();
		// var_dump($datatable);
		$result = array();

		foreach ($datatable as $id => $row)
		{
			if($row['term_parent'] && array_key_exists($row['term_parent'], $result))
			{
				$parent_title = $result[$row['term_parent']];
				if($parent_title)
				{
					// if not exist search in all of array and find a parent
					// var_dump($parent_title);
				}

				$result[$row['id']] = $parent_title . " &gt; " . $row['term_title'];
			}
			else
			{
				$result[$row['id']] = $row['term_title'];
			}
		}

		return $result;
	}

}
?>