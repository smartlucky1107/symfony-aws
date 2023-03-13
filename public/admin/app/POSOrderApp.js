let POSOrderApp = angular.module('POSOrderApp', ['apiModule', 'routingModule', 'swangular']);

POSOrderApp.factory('bridge', ControllerBridge);
POSOrderApp.controller('POSOrderAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedPOSOrder($POSOrderId);
    }, 200);

    $scope.POSOrderId = null;

    $scope.$on('loadedPOSOrder', function(event, args) {
        $scope.POSOrderId = args.POSOrderId;
    });
}]);

angular.bootstrap(document.getElementById("POSOrderAppHandler"),["POSOrderApp"]);
