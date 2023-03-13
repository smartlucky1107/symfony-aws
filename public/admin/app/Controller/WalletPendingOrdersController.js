function WalletPendingOrdersController($scope, api, routingModule, swangular){
    $scope.routing = routingModule;

    $scope.walletId = null;
    $scope.pendingOrders = [];
    $scope.isLoading = true;

    let loadPendingOrders = function(){
        $scope.isLoading = true;
        api.getWalletPendingOrders($scope.walletId, function (result) {
            $scope.pendingOrders = result.pendingOrders;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        loadPendingOrders();
    });
};
