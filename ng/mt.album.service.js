'use strict';

/* Services */
angular.module('mtServices').service('AlbumService', function($resource, ConfigService, $q) 
{
    var svc = this;
    window.AlbumService = this;

    svc.mapping = window.mapping;
    svc.mapping.dirs = window.mapping.dirs.toMap();

	svc.resource = $resource("data.php");

    svc.getAlbum = function(path)
    {
        return ConfigService.getFromResource(svc.resource, { data: "groupedFiles", path: path }, "album");
    };

    svc.getFiles = function(path)
    {
        return ConfigService.queryFromResource(svc.resource, { data: "files", path: path }, "files");
    };

    svc.getThumbnails = function(path, tndir)
    {
        path = String.combine(path, "." + tndir);
        return ConfigService.queryFromResource(svc.resource, { data: "files", path: path }, "thumbnails");
    };

    svc.getMetadata = function(path, type)
    {
        var url = svc.getUrlRootPath(path);
        var file = ".metadata.{0}.csv".format(type);
        url = String.combine(url, file);
        return ConfigService.loadCsv(url, "metadata", svc);
    };

    svc.getUrlRootPath = function(path)
    {
        if(!path) return "/" + svc.mapping.root;

        var root = path.substringBefore("/").toLowerCase();
        var sub = path.substringAfter("/");

        var rootPath = valueIfDefined(root, svc.mapping.dirs);
        if(!rootPath)
            return "/" + svc.mapping.root + "/" + path;
        return "/" + rootPath + "/" + sub;
    };

});

//http://tylermcginnis.com/angularjs-factory-vs-service-vs-provider/
//http://stackoverflow.com/questions/16130345/is-there-a-way-i-can-return-a-promise-from-a-resource-without-needing-to-use-q
