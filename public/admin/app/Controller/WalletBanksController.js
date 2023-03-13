function WalletBanksController($scope, api, swangular){
    $scope.walletId = null;
    $scope.banks = [];
    $scope.isLoading = true;

    let loadWalletBanks = function(){
        $scope.isLoading = true;
        api.getWalletBanks($scope.walletId, function (result) {
            $scope.banks = result.banks;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        loadWalletBanks();
    });

    let validateWalletBankRequest = function(callback){
        if(!$scope.walletBankRequest.iban){
            swangular.swal("Something is wrong", "IBAN is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.walletBankRequest.swift){
            swangular.swal("Something is wrong", "SWIFT is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            iban : $scope.walletBankRequest.iban,
            swift : $scope.walletBankRequest.swift,
        });
    };

    let resetWalletBankRequest = function(){
        $scope.walletBankRequest = {
            iban: null,
            swift: null,
        };
    };
    resetWalletBankRequest();

    $scope.makeWalletBankRequest = function () {
        validateWalletBankRequest(function(data){
            api.postWalletBank($scope.walletId, data, function (result) {
                swangular.swal("Success", "Bank account added.", "success");

                resetWalletBankRequest();
                loadWalletBanks();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };
};