let systemTagsApp = angular.module('systemTagsApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

systemTagsApp.factory('bridge', ControllerBridge);
systemTagsApp.directive('onEnter', onEnterDirective);
systemTagsApp.controller('SystemTagListController', ['$scope', 'api', 'listManager', 'routingModule', 'swangular', 'bridge', SystemTagListController]);

angular.bootstrap(document.getElementById("systemTagsAppHandler"),["systemTagsApp"]);