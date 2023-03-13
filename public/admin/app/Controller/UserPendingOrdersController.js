function UserPendingOrdersController($scope, api, routingModule, swangular){
    $scope.routing = routingModule;

    $scope.userId = null;
    $scope.pendingOrders = [];
    $scope.isLoading = true;

    let loadPendingOrders = function(){
        $scope.isLoading = true;
        api.getUserPendingOrders($scope.userId, function (result) {
            $scope.pendingOrders = result.pendingOrders;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadPendingOrders();
    });
};
