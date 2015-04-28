var User = new function()
{
	this.get = function(field)
	{
		if(!window.album || !album.user) return null;
		if(!field) return album.user;
		return album.user[field] || "";
	};
	
	this.getUser = function()
	{
		return User.get();
	};
	
	this.getUsername = function()
	{
		return User.get("username");
	};
	
	this.getRole = function()
	{
		return User.get("role");
	};
	
	this.isAdmin = function()
	{
		return User.get("role")=="admin";
		//return valueOrDefault(album.user.admin,false);
	};
	
	this.isUploader = function()
	{
		var role = User.get("role");
		return role=="admin" || role=="upload" || role=="edit";
		//return valueOrDefault(album.user.uploader,false);
	};

	this.toString = function()
	{ 
		if(User.getUsername() && User.getRole())
			return "{0} ({1})".format(User.getUsername(), User.getRole()); 
		return User.getUsername() || User.getRole();
	}
	
	//try getting login page with ajax, asks for authentication
	this.logout=function()
	{
		return this.login();
	}

	this.login=function(role)
	{
		var link;
		if(role=="logout") role=null;
		if(!role)
			link="logout.php";
		else
			link=".{0}/?path={1}".format(role, window.album && album.path ? album.path : "");

		var userDiv=$('#userLabel');

		$.ajax({	
			url: link,
			dataType: "json",
			contentType: "application/json",
			cache: false,		
			success: function(response) 
			{ 
				album.user=response;
				UI.displayUser(userDiv);
				UI.displayEditEvent();
			},
			error:   function(xhr, textStatus, errorThrown)
			{ 
				userDiv.html("");
				userDiv.append("Error " + xhr.status + " : " + xhr.statusText + "\n");
				userDiv.append(xhr.responseText);
			}
		});				
	};
};
