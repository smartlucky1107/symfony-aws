function UserDepositsController($scope, api, routingModule, swangular){
    $scope.routing = routingModule;

    $scope.userId = null;
    $scope.deposits = [];
    $scope.isLoading = true;

    let loadDeposits = function(){
        $scope.isLoading = true;
        api.getUserDeposits($scope.userId, function (result) {
            $scope.deposits = result.deposits;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadDeposits();
    });
};
