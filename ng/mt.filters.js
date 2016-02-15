'use strict';

/* Filters */
angular.module('mtFilters')
.filter('checkmark', function()
{
  return function(input) {
    return input ? '\u2713' : '\u2718';
  };
})
.filter('offset', function()
{
    return function(input,start)
    {
      return input.slice(start);
    };
})
.filter('paginate', function()
{
    return function(input,start,count)
    {
      if(!count)
        return input.slice(start);
      start *= count;
      return input.slice(start, start + count);
    };
});
