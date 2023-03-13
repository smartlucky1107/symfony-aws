let walletApp = angular.module('walletApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

walletApp.factory('bridge', ControllerBridge);
walletApp.controller('WalletAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedWallet($walletId);
    }, 200);

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
    });

    $scope.createNewAddress = function(){
        api.postUserWalletAddress($scope.walletId,function (result) {
            swangular.swal("Success", "Address added.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Error occurred.", "warning");
        });
    };
}]);

walletApp.controller('NewInternalTransferController', ['$scope', 'api', 'swangular', 'bridge', NewInternalTransferController]);
walletApp.controller('NewReleaseBlockedController', ['$scope', 'api', 'swangular', 'bridge', NewReleaseBlockedController]);
walletApp.controller('NewDepositController', ['$scope', 'api', 'swangular', 'bridge', NewDepositController]);
// walletApp.controller('WalletInfoController', ['$scope', 'api', WalletInfoController]);
walletApp.controller('WalletInfoController', ['$scope', 'api', WalletInfoController]);
walletApp.controller('WalletTransfersController', ['$scope', 'api', 'routingModule', WalletTransfersController]);
walletApp.controller('WalletBalancesController', ['$scope', 'api', 'routingModule', WalletBalancesController]);
walletApp.controller('DepositListController', ['$scope', 'api', 'listManager', 'routingModule', 'swangular', 'bridge', DepositListController]);
walletApp.controller('WalletPendingOrdersController', ['$scope', 'api', 'routingModule', 'swangular', WalletPendingOrdersController]);
walletApp.controller('WalletBanksController', ['$scope', 'api', 'swangular', WalletBanksController]);

angular.bootstrap(document.getElementById("walletAppHandler"),["walletApp"]);
