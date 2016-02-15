'use strict';

/* Services */
angular.module('mtServices').service('AlbumService', function($resource, $q) 
{
	this._album=null;
    var a = this;
	this.resource = $resource("data.php");

	this.getAlbum = function(path)
	{
	    if(this._album && path == (this._album.path || ""))
	    	return this._album;

        var deferred = $q.defer();
	    this.resource.get({ data: "album", path: path }, function(response)
        {
            a._album = response;
            deferred.resolve(response);
        });
 		return deferred.promise;
	};

    this.getAlbumInstance = function(path)
    {
        return this._album;
    };
});

//http://tylermcginnis.com/angularjs-factory-vs-service-vs-provider/
//http://stackoverflow.com/questions/16130345/is-there-a-way-i-can-return-a-promise-from-a-resource-without-needing-to-use-q
