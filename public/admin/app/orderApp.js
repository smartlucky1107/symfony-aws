let orderApp = angular.module('orderApp', ['apiModule', 'routingModule', 'swangular']);

orderApp.factory('bridge', ControllerBridge);
orderApp.controller('OrderAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedOrder($orderId);
    }, 200);

    $scope.orderId = null;
    $scope.order = null;

    let loadOrder = function(){
        // api.getUser($scope.orderId, function (result) {
        //     $scope.order = result.user;
        // }, function(){
        //     // error handler
        // });
    };

    $scope.$on('refreshOrderInfo', function() {
        loadOrder();
    });

    $scope.$on('loadedOrder', function(event, args) {
        $scope.orderId = args.orderId;
        loadOrder();
    });
}]);

angular.bootstrap(document.getElementById("orderAppHandler"),["orderApp"]);
