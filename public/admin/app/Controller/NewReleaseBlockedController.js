function NewReleaseBlockedController($scope, api, swangular, bridge) {
    $scope.walletId = null;

    let validateReleaseBlocked = function(callback){
        if(!$scope.releaseBlocked.amount){
            swangular.swal("Something is wrong", "Release amount is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback(
            $scope.releaseBlocked.walletId,
            $scope.releaseBlocked.amount
        );
    };

    let resetReleaseBlocked = function(){
        $scope.releaseBlocked = {
            walletId: null,
            amount: null
        };
    };
    resetReleaseBlocked();

    $scope.makeReleaseBlocked = function () {
        validateReleaseBlocked(function(walletId, amount){
            api.putWalletReleaseBlocked(walletId, amount, function (result) {
                swangular.swal("Success", "New currency saved. Feel free to add another one.", "success");

                resetReleaseBlocked();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        $scope.releaseBlocked.walletId = args.walletId;
    });
}