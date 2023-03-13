function UserApiKeysController($scope, api, swangular){
    $scope.userId = null;
    $scope.userApiKeys = [];
    $scope.isLoading = true;

    let loadUserApiKeys = function(){
        $scope.isLoading = true;
        api.getUserApiKeys($scope.userId, function (result) {
            $scope.userApiKeys = result.apiKeys;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    $scope.deactivate = function(key) {
        api.putUserApiKeyDeactivate($scope.userId, key, function (result) {
            loadUserApiKeys();

            swangular.swal("Success", "User api key denied.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadUserApiKeys();
    });
};