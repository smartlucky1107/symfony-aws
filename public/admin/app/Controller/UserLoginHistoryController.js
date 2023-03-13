function UserLoginHistoryController($scope, api, swangular){
    $scope.userId = null;
    $scope.loginHistory = [];
    $scope.isLoading = true;

    let loadLoginHistory = function(){
        $scope.isLoading = true;
        api.getUserLoginHistory($scope.userId, function (result) {
            $scope.loginHistory = result.loginHistory;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadLoginHistory();
    });
};