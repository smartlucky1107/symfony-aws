function WalletBalancesController($scope, api, routingModule){
    $scope.routing = routingModule;

    $scope.walletId = null;
    $scope.walletBalances = [];

    let loadWalletBalances = function(){
        api.getWalletBalancesByWallet($scope.walletId, function (result){
            $scope.walletBalances = result.walletBalances;
        }, function(){
            // error handler
        });
    };

    $scope.detailsAllowed = function(walletBalance){
        if(walletBalance.tradeId){
            return true;
        }else if(walletBalance.orderId){
            return true;
        }

        return false;
    };

    $scope.details = function(walletBalance){
        if(walletBalance.transfersEnabled){
            walletBalance.transfers = [];
            walletBalance.transfersEnabled = false;
        }else{
            if(walletBalance.tradeId){
                api.getWalletTransferByTrade(walletBalance.tradeId, function (result) {
                    walletBalance.transfers = [result.walletTransfer];
                    walletBalance.transfersEnabled = true;
                    console.log(walletBalance);
                }, function(){
                    // error handler
                });
            }else if(walletBalance.orderId){
                api.getWalletTransfersByOrder(walletBalance.orderId, function (result) {
                    walletBalance.transfers = result.walletTransfers;
                    walletBalance.transfersEnabled = true;
                    console.log(walletBalance);
                }, function(){
                    // error handler
                });
            }
        }
    };

    $scope.$on('depositApproved', function() {
        loadWalletBalances();
    });
    $scope.$on('refreshWalletBalances', function() {
        loadWalletBalances();
    });
    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        loadWalletBalances();
    });
};