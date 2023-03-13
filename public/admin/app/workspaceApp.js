let workspaceApp = angular.module('workspaceApp', ['apiModule', 'routingModule', 'swangular']);

workspaceApp.factory('bridge', ControllerBridge);
workspaceApp.controller('WorkspaceAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedWorkspace($workspaceId);
    }, 200);

    $scope.workspaceId = null;

    $scope.$on('loadedWorkspace', function(event, args) {
        $scope.workspaceId = args.workspaceId;
    });
}]);

angular.bootstrap(document.getElementById("workspaceAppHandler"),["workspaceApp"]);
