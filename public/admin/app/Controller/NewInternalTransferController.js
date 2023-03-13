function NewInternalTransferController($scope, api, swangular, bridge) {
    $scope.walletId = null;

    let validateInternalTransfer = function(callback){
        if(!$scope.internalTransfer.toWalletId){
            swangular.swal("Something is wrong", "Wallet ID is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.internalTransfer.amount){
            swangular.swal("Something is wrong", "Transfer amount is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback(
            $scope.internalTransfer.fromWalletId,
            $scope.internalTransfer.toWalletId,
            $scope.internalTransfer.amount
        );
    };

    let resetInternalTransfer = function(){
        $scope.internalTransfer = {
            fromWalletId: null,
            toWalletId: null,
            amount: null
        };
    };
    resetInternalTransfer();

    $scope.makeInternalTransfer = function () {
        validateInternalTransfer(function(fromWalletId, toWalletId, amount){
            api.putWalletInternalTransfer(fromWalletId, toWalletId, amount, function (result) {
                swangular.swal("Success", "New currency saved. Feel free to add another one.", "success");

                resetInternalTransfer();

                bridge.refreshTransfers();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        $scope.internalTransfer.fromWalletId = args.walletId;
    });
}