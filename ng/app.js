'use strict';

/* App Module */

// Define new module for our application
var app = angular.module('app', ['ui.router', 'ui.bootstrap', 'ngFileUpload', 'mtControllers', 'mtFilters', 'mtServices']);

app.config(function($stateProvider, $urlRouterProvider)
{  
  $stateProvider
  	.state('root', { url: "/",                controller: 'AlbumCtrl', controllerAs: 'ac', templateUrl: 'ng/views/album.html' })
    .state('dir',  { url: "/:path",           controller: 'AlbumCtrl', controllerAs: 'ac', templateUrl: 'ng/views/album.html' })
    .state('file', { url: "/:path/:filename", controller: 'AlbumCtrl', controllerAs: 'ac', templateUrl: 'ng/views/image.html' });

  $urlRouterProvider.otherwise("/");
});

angular.module('mtServices', ['ngResource']);
angular.module('mtControllers', []);
angular.module('mtFilters', []);

app.isMobile = function() 
{ 
    return !!navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|Phone|mobile/i);
};
