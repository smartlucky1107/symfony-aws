let checkoutOrderApp = angular.module('checkoutOrderApp', ['apiModule', 'routingModule', 'swangular']);

checkoutOrderApp.factory('bridge', ControllerBridge);
checkoutOrderApp.controller('CheckoutOrderAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedCheckoutOrder($checkoutOrderId);
    }, 200);

    $scope.checkoutOrderId = null;

    $scope.$on('loadedCheckoutOrder', function(event, args) {
        $scope.checkoutOrderId = args.checkoutOrderId;
    });
}]);

angular.bootstrap(document.getElementById("checkoutOrderAppHandler"),["checkoutOrderApp"]);
