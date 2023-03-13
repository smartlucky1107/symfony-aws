function NewDepositController($scope, api, swangular, bridge) {
    $scope.walletId = null;

    let validateDepositRequest = function(callback){
        if(!$scope.depositRequest.amount){
            swangular.swal("Something is wrong", "Deposit amount is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.depositRequest.amountOriginal){
            swangular.swal("Something is wrong", "Deposit amount original is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.depositRequest.bankTransactionDate){
            swangular.swal("Something is wrong", "Transaction date is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.depositRequest.bankTransactionId){
            swangular.swal("Something is wrong", "Transaction ID is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            walletId: $scope.depositRequest.walletId,
            amount : $scope.depositRequest.amount,
            amountOriginal : $scope.depositRequest.amountOriginal,
            bankTransactionDate : $scope.depositRequest.bankTransactionDate,
            bankTransactionId : $scope.depositRequest.bankTransactionId,
        });
    };

    let resetDepositRequest = function(){
        $scope.depositRequest = {
            walletId: null,
            amount: null,
            amountOriginal: null,
            bankTransactionDate: null,
            bankTransactionId: null,
        };
    };
    resetDepositRequest();

    $scope.makeDepositRequest = function () {
        validateDepositRequest(function(data){
            api.postDeposit(data, function (result) {
                swangular.swal("Success", "Deposit request added. It is waiting for additional approval.", "success");

                resetDepositRequest();
                bridge.refreshDepositRequests();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        $scope.depositRequest.walletId = args.walletId;
    });
}
