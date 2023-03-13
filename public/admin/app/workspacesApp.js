let workspacesApp = angular.module('workspacesApp', ['listModule', 'apiModule', 'routingModule']);

workspacesApp.factory('bridge', ControllerBridge);
workspacesApp.directive('onEnter', onEnterDirective);
workspacesApp.controller('WorkspaceListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', WorkspaceListController]);

angular.bootstrap(document.getElementById("workspacesAppHandler"),["workspacesApp"]);
