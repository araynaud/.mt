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

    ac.loadAlbum();
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
    ac.loadAlbum();
  }

  ac.loadAlbum = function()
  {
    var path = ac.pathSlash();
    AlbumService.getAlbum(path).then(function(data)
    {
      ac.album = { groupedFiles: data };
      ac.urlAbsPath = AlbumService.getUrlRootPath(path);
      //ac.config = data.config;
      ac.dirs = Object.values(data.DIR);
      ac.images = Object.values(data.IMAGE);
      ac.audio = Object.values(data.AUDIO);
      ac.images = ac.images.concat(Object.values(data.VIDEO));
    },
    ac.errorMessage);

    ac.loadMetadata(path, "image");
    ac.loadMetadata(path, "video");
  };

  ac.loadMetadata = function(path, type)
  {
    AlbumService.getMetadata(path, type).then(function(data)
    {
      if(!ac.metadata) ac.metadata = {};
      ac.metadata[type] = data.indexBy("name");
    });
  };

  ac.errorMessage = function(result)
  {
      alert("Error: No data returned");
  };
  
  ac.init();
}

]); 
