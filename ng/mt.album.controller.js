'use strict';

// Create new controller, that accepts two services
angular.module('mtControllers').controller('AlbumCtrl', ['$stateParams', 'AlbumService',
function($stateParams, AlbumService)
{
  var ac = this;
  window.AlbumCtrl = this;

  ac.init = function() 
  {
    ac.images = [];
    ac.query = "";
    ac.orderProp = "takenDate";
    ac.path = ($stateParams.path && $stateParams.path != "_") ? $stateParams.path : "";
    ac.filename = $stateParams.filename;
    ac.currentPage = 0;
    ac.entryLimit = 10;

    ac.fetch();
  };

  ac.nbFiles=function() {
    return ac.images.length;
  };

  ac.nbPages=function(){
    return Math.ceil(ac.images.length / ac.entryLimit);
  };

  ac.nextPage=function()
  {
    return ac.currentPage = modulo(ac.currentPage + 1, ac.nbPages());
  };

  ac.prevPage=function()
  {
    return ac.currentPage = modulo(ac.currentPage - 1, ac.nbPages());
  };

  ac.loadParent = function()
  {
    ac.loadPath(ac.pathParent());
  }

  ac.pathSlash=function()
  {
    return ac.path ? ac.path.replace(/\./g, "/") : "";
  };

  ac.pathDot=function()
  {
    return ac.path ? ac.path.replace(/\//g, ".") : "";
  };

  ac.pathParent=function()
  {
    return ac.path.substringBefore(".", true, true);
  };

  ac.combine=function(sep,a,b)
  {
    if(!a) return b;
    if(!b) return a;
    return a+sep+b;
  };

  ac.loadSubdir = function(subdir)
  {
    ac.loadPath(ac.path + "/" + subdir);
  }

  ac.loadPath = function(path)
  {
    ac.fetch();
  }

  ac.fetch = function()
  {
    var al = AlbumService.getAlbum(ac.pathSlash());
    if(al.then)
        al.then(ac.loadAlbum, function (result)
        {
            alert("Error: No data returned");
        });
    else
      ac.loadAlbum(al);
  }
  
  ac.loadAlbum = function(data)
  {
    if(!data.groupedFiles)
    {
      data = { groupedFiles: data };
      data.urlAbsPath =  AlbumService.getUrlRootPath(ac.pathSlash());
    }
    ac.album = data; // Bind the data returned from web service to $scope
    ac.urlAbsPath = data.urlAbsPath; // "/pictures",
    ac.config = data.config;
    ac.dirs = Object.values(data.groupedFiles.DIR);
    ac.images = Object.values(data.groupedFiles.IMAGE);
    ac.audio = Object.values(data.groupedFiles.AUDIO);
    ac.images = ac.images.concat(Object.values(data.groupedFiles.VIDEO));
    return data;
  };

  ac.init();
}

]); 
