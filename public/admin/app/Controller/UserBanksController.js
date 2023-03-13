function UserBanksController($scope, api, swangular){
    $scope.userId = null;
    $scope.banks = [];
    $scope.isLoading = true;

    let loadUserBanks = function(){
        $scope.isLoading = true;
        api.getUserBanks($scope.userId, function (result) {
            $scope.banks = result.banks;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadUserBanks();
    });

    let validateUserBankRequest = function(callback){
        if(!$scope.userBankRequest.iban){
            swangular.swal("Something is wrong", "IBAN is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.userBankRequest.swift){
            swangular.swal("Something is wrong", "SWIFT is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            iban : $scope.userBankRequest.iban,
            swift : $scope.userBankRequest.swift,
        });
    };

    let resetUserBankRequest = function(){
        $scope.userBankRequest = {
            iban: null,
            swift: null,
        };
    };
    resetUserBankRequest();

    $scope.makeUserBankRequest = function () {
        validateUserBankRequest(function(data){
            api.postUserBank($scope.userId, data, function (result) {
                swangular.swal("Success", "Bank account added.", "success");

                resetUserBankRequest();
                loadUserBanks();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };
};