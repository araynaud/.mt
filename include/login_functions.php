<?php 
function session_login()
{
	$_SESSION['user']= $_SERVER["REMOTE_USER"];
	if(!$_SESSION['user']) return false;
	return $_SESSION['user'];
}

function session_logout()
{
  unset($_SESSION['user']);
  unset($_SESSION['role']);
  unset($_SESSION['upload']);
  unset($_SESSION['admin']);
}

function current_user()
{
	return sessionParam("user");
//	return isset($_SESSION['user']) ? $_SESSION['user'] : "";
}

function currentUserRole()
{
	$role=sessionParam("role");
	return $role;
}


function setRole($role)
{
	$_SESSION[$role] = true; 
	$_SESSION["role"] = $role; 
}

function set_admin()
{
	setRole("admin");
}

function set_upload()
{
	setRole("upload");
}

function sessionParam($name)
{
	return isset($_SESSION[$name]) ? $_SESSION[$name] : "";
}

function sessionParamBoolean($name)
{
	return isset($_SESSION[$name]) && $_SESSION[$name];
}

function is_admin()
{
	return sessionParamBoolean("admin"); 
}

function is_uploader()
{
	return sessionParamBoolean("upload"); 
}

function is_loggedin()
{
	return isset($_SESSION['user']); 
}
?>