<?php //user info class
class User extends BaseObject
{
	private $username;
	private $upload;
	private $admin;
	private $role;
	private $groups;

    public function __construct($username="")
	{
		if($username)
			$this->username = $username;
		else
		{
			$this->username = current_user();
	        $this->upload = is_uploader();
	        $this->admin = is_admin();
			//$this->role = currentUserRole();
		}
		$this->getGroups();
		$this->role = $this->getAccessLevel();
		setRole($this->role);
	}	

	//what groups the user belongs to, based on config
	public function getGroups()
	{
		if($this->groups) return $this->groups;

		$this->groups = array();
		if(!($groups = getConfig("groups")))
			return $this->groups;

		foreach ($groups as $group => $users)
		{
			$users = toArray($users);
			if(in_array($this->username, $users))
				$this->groups[] = $group;
		}
		return $this->groups;
	}

//accesss to current dir
	public function getRole()
	{
		if(!$this->role)
			$this->role = $this->getAccessLevel();
		return $this->role;
	}	

	public function getAccessLevel()
	{
		$defaultAccess = 
		$dirAccess = getConfig("access");		
//debug("dirAccess", $dirAccess, true);
		if(!$dirAccess)
			return "admin";

		ksort($dirAccess);
		foreach ($dirAccess as $level => $list)
		{
			if(!$list || $list=="*") return $level;
			$list = toArray($list);
			$userAccess = in_array($this->username, $list);
			$groupAccess = array_intersect($this->groups, $list);
			if($userAccess || $groupAccess)
				return $level;
		}
		return "";
	}

	public function getAccessLevelTo($relPath, $subdir="")
	{
		$hasAccess = $this->getAccessLevel();
debug("hasAccess $relPath", $hasAccess, true);
		if(!$hasAccess) return "";

		$dirAccess = getConfig("access");
		$subdirAccess = getSubdirConfig($relPath, $subdir, "access");
		if($subdirAccess)
			$dirAccess = arrayUnion($dirAccess , $subdirAccess);
debug("dirAccess $subdir", $dirAccess, true);
		if(!$dirAccess)
			return "admin";

		ksort($dirAccess);
		foreach ($dirAccess as $level => $list)
		{
			if(!$list || $list=="*") return $level;
			$list = toArray($list);
			$userAccess = in_array($this->username, $list);
			$groupAccess = array_intersect($this->groups, $list);
			if($userAccess || $groupAccess)
				return $level;
		}
		return "";
	}

	public function hasAccess($role="read")
	{
		$dirAccess = getConfig("access.$role");
		if(!$dirAccess) return true;
		$dirAccess = toArray($dirAccess);
//debug("dirAccess $role", $dirAccess);
		$userAccess = in_array($this->username, $dirAccess);
		$groupAccess = array_intersect($this->groups, $dirAccess);
//debug("hasAccess $role", $userAccess || $groupAccess);
		return $userAccess || $groupAccess;

	}

	public function hasAccessTo($relPath, $subdir="", $role="read")
	{
		$hasAccess = $this->hasAccess($role);
		if(!$hasAccess) return false;
		
		$dirAccess = getSubdirConfig($relPath, $subdir, "access.$role");
		if(!$dirAccess) return true;
		$dirAccess = toArray($dirAccess);
//debug("dirAccess $subdir $role", $dirAccess);

		$userAccess = in_array($this->username, $dirAccess);
		$groupAccess = array_intersect($this->groups, $dirAccess);
debug("hasAccessTo $subdir $role", $userAccess || $groupAccess);
		return $userAccess || $groupAccess;
	}


}
?>