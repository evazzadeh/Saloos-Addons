<?php
namespace content_cp\permissions;

class view extends \content_cp\home\view
{
	public function options()
	{
		$this->data->page['desc']     = T_('Define or edit user permissions to allow or block access to special pages');
		$this->data->page['haschild'] = false;
		$myChild                      = $this->child();
		if($myChild)
		{
			$this->data->page['title'] =
				T_('Edit'). ' '. T_('permission'). ' '. T_($myChild);

			$this->data->permissions = $this->model()->permModuleFill();
		}
		else
		{
			$this->data->datarow = $this->model()->draw_permissions();
		}
	}
}
?>