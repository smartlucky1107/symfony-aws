let employeeApp = angular.module('employeeApp', ['apiModule', 'routingModule', 'swangular']);

employeeApp.factory('bridge', ControllerBridge);
employeeApp.controller('EmployeeAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedEmployee($employeeId);
    }, 200);

    $scope.employeeId = null;

    $scope.$on('loadedEmployee', function(event, args) {
        $scope.employeeId = args.employeeId;
    });
}]);

angular.bootstrap(document.getElementById("employeeAppHandler"),["employeeApp"]);
