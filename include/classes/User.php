<?php //user info class
class User extends BaseObject
{
	private $username;
	private $upload;
	private $admin;
	private $role;

    public function __construct()
	{
		$this->username=current_user();
        $this->upload=is_uploader();
        $this->admin=is_admin();
		$this->role=currentUserRole();
	}	
}
?>