function WalletTransfersController($scope, api, routingModule){
    $scope.routing = routingModule;

    $scope.walletId = null;
    $scope.transfers = [];

    let loadTransfers = function(){
        api.getTransfersByWallet($scope.walletId, function (result) {
            $scope.transfers = result.transfers;
        }, function(){
            // error handler
        });
    };

    $scope.$on('depositApproved', function() {
        loadTransfers();
    });
    $scope.$on('refreshTransfers', function() {
        loadTransfers();
    });
    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        loadTransfers();
    });
};