<?php
namespace content_cp\home;

class view extends \mvc\view
{
	public function config()
	{
		// $this->data->list             = $this->cpModlueList('all');
		$this->data->bodyclass        = 'fixed';
		$this->include->css           = false;
		$this->include->js            = false;
		$this->include->fontawesome   = true;
		$this->include->datatable     = true;
		$this->include->chart         = true;
		$this->include->introjs       = true;
		$this->include->lightbox      = true;
		$this->include->editor        = true;
		$this->include->cp            = true;
		$this->include->uploader      = true;
		$this->global->js             = array();

		// $this->global->js             = [$this->url->myStatic.'js/highcharts/highcharts.js'];
		// $this->data->page['desc']  = 'salam';
		$this->data->page['haschild'] = true;
		$this->data->page['title']    = T_(ucfirst(\lib\router::get_url(' ')));

		$this->data->dir['right']   = $this->global->direction == 'rtl'? 'left':  'right';
		$this->data->dir['left']    = $this->global->direction == 'rtl'? 'right': 'left';

		$mymodule = $this->module();
		switch ($mymodule)
		{
			case 'tags':
				$this->data->page['desc']     = T_('Assign keywords to your posts using tags');
				break;

			case 'categories':
				$this->data->page['desc']     = T_('Use categories to define sections of your site and group related posts');
				$this->data->page['title']    = T_('Categories');
				break;

			case 'filecategories':
				$this->data->page['desc']     = T_('Use categories to define sections of your site and group related files');
				$this->data->page['title']    = T_('File Categories');
				break;

			case 'bookcategories':
				$this->data->page['desc']     = T_('Use categories to define sections of your site and group related books');
				$this->data->page['title']    = T_('Book Categories');
				break;

			case 'books':
				$this->data->page['desc']     = T_('Use book to define important parts to use in posts');
				$this->data->page['title']    = T_('books');
				break;

			case 'posts':
				$this->data->page['desc']     = T_('Use posts to share your news in specefic category');
				break;

			case 'pages':
				$this->data->page['desc']     = T_('Use pages to share your static content');
				break;

			case 'attachments':
				$this->data->page['desc']     = T_('Upload your media');
				break;

			case 'socialnetwork':
				$this->data->page['desc']     = T_('Publish new post in social networks');
				break;

			case 'options':
				$this->data->page['desc']     = T_('Edit your site general options');
				$this->data->page['haschild'] = false;
				break;

			case 'visitors':
				if(LogVisitors)
				{
					// create for chart
					$type = \lib\utility::get('type');
					$utype = \lib\utility::get('utype');
					$this->data->chart_type             = $type? $type: 'column';
					$this->data->chart_unique_type      = $utype? $utype: 'areaspline';


					$this->data->visitors               = $this->model()->visitors();
					$this->data->visitors_unique        = $this->model()->visitors(true);

					if($this->data->visitors <= 1)
						$this->data->error = T_("Chart must be contain at least 2 column!");
				}
				break;

			case 'home':
				$this->data->page['title']          = T_('Dashboard');

				$this->data->countOf['posts']       = $this->model()->countOf('posts');
				$this->data->countOf['pages']       = $this->model()->countOf('pages');
				$this->data->countOf['attachments'] = $this->model()->countOf('attachments');
				$this->data->countOf['books']       = $this->model()->countOf('books');
				$this->data->countOf['tags']        = $this->model()->countOf('tags');
				$this->data->countOf['categories']  = $this->model()->countOf('categories');
				$this->data->countOf['users']       = $this->model()->countOf('users');

				$this->data->bodyclass              .= ' unselectable';
				// check visitor is new or not
				$this->data->visitor_new            = false;
				$ref = \lib\router::urlParser('referer', 'sub');
				if($ref !== 'cp' && $ref !== null)
					$this->data->visitor_new = true;

				if(LogVisitors)
				{
					// create for chart
					$this->data->chart_type             = 'column';
					$this->data->visitors               = $this->model()->visitors();
					$this->data->visitors_toppages      = $this->model()->visitors_toppages(15);

					if($this->data->visitors <= 1)
						$this->data->error = T_("Chart must be contain at least 2 column!");
				}

				break;

			default:
				# code...
				break;
		}

		if($this->data->page['haschild'])
		{
			// Check permission and if user can do this operation
			// allow to do it, else show related message in notify center
			$myResult = $this->access('cp', $mymodule, 'add');
			$this->data->page['haschild'] = $myResult? true: false;
		}
		// $this->data->site['title']  = T_('Control Panel'). ' - ' . $this->data->site['title'];
	}

	function view_datatable()
	{
		// in root page like site.com/admin/banks show datatable
		// get data from database through model
		switch ($this->module())
		{
			case 'profile':
				$this->data->datarow = $this->model()->datarow('users', $this->login('id'));
				break;

			case 'options':
				$this->data->datarow = $this->model()->draw_options();
				// var_dump($this->data->datarow);
				break;

			default:
				$this->data->datatable = $this->model()->datatable();
				break;
		}
	}


	public function view_child()
	{
		$mytable                = $this->cpModule('table');
		$mychild                = $this->child();
		// $this->global->js       = array($this->url->myStatic.'js/cp/medium-editor.min.js');
		$this->data->enum       = \lib\sql\getTable::enumValues('posts');

		switch ($mytable)
		{
			case 'posts':
				// show list of tags
				$this->data->tagList = $this->model()->sp_term_list();

				// for each type of post
				switch ($this->cpModule('raw'))
				{
					case 'pages':
						$this->data->parentList = $this->model()->sp_parent_list();
						break;

					case 'attachments':
						$this->data->maxSize = \lib\utility\Upload::max_file_upload_in_bytes();
						// $this->include->uploader      = true;
						// array_push($this->global->js, $this->url->myStatic.'js/cp/uploader.js');
						$this->data->catList = $this->model()->sp_cats('filecat');
						$this->data->catListSelected = $this->model()->sp_cats('filecat', true);
						break;

					case 'books':
						$this->data->catList = $this->model()->sp_cats('bookcat');
						$this->data->catListSelected = $this->model()->sp_cats('bookcat', true);
						$this->data->parentList = $this->model()->sp_parent_list(true, 'book');

						break;

					case 'socialnetwork':
						$this->data->catList = null;

						break;

					default:
						$this->data->catList = $this->model()->sp_cats();
						break;
				}
				break;

			default:
				switch ($this->cpModule('raw'))
				{
					case 'categories':
					case 'filecategories':
					case 'bookcategories':
						$this->data->parentList = $this->model()->sp_category_list($this->cpModule('type'));
						break;
				}

				$this->data->field_list = \lib\sql\getTable::get($mytable);
				$myform = $this->createform('@'.db_name.'.'.$mytable, $this->data->child);
				break;
		}

		// if module for users then fill permission list
		if($this->cpModule('raw') === 'users')
		{
			$myPermList = $this->data->form->users->user_permission;
			$myPermList->type('select');
			// get list of permissions
			foreach ($this->model()->permList() as $value)
			{
				$myPermList->child()->value($value)->label(T_($value));
			}
		}


		if($mychild === 'edit')
		{
			$this->data->datarow = $this->model()->datarow($mytable, null, true);

			if(isset($this->data->datarow['post_meta']))
				$this->data->datarow['meta'] = json_decode($this->data->datarow['post_meta'], true);
			// var_dump($this->data->datarow['meta']);

			if($this->cpModule('raw') === 'attachments')
			{
				if(isset($this->data->datarow['meta']['slug']))
					$this->data->datarow['post_slug'] = $this->data->datarow['meta']['slug'];
			}


			if($mytable === 'posts')
			{
				// $this->data->datarow['post_content'] .= '<img src="/static/images/logo.png" />';
				// var_dump($this->data->datarow['post_content']);
				$url = $this->data->datarow['post_url'];
				$this->data->datarow['cat_url'] = substr($url, 0, strrpos( $url, '/'));
			}
		}
	}

	// function pushState()
	// {
	// 	// temporary disable push state on control panel
	// 	$this->data->display['cp'] = null;
	// }
}
?>