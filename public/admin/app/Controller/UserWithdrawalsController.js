function UserWithdrawalsController($scope, api, routingModule, swangular){
    $scope.routing = routingModule;

    $scope.userId = null;
    $scope.withdrawals = [];
    $scope.isLoading = true;

    let loadWithdrawals = function(){
        $scope.isLoading = true;
        api.getUserWithdrawals($scope.userId, function (result) {
            $scope.withdrawals = result.withdrawals;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadWithdrawals();
    });
};
