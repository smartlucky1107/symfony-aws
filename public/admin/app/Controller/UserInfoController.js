function UserInfoController($scope, api, swangular){
    $scope.userId = null;
    $scope.user = null;
    $scope.userForm = null;

    $scope.countries = null;

    $scope.formToggle = function(){
        if($scope.isFormVisible) {
            $scope.isFormVisible = false;
        }else{
            $scope.isFormVisible = true;
        }
    };
    $scope.saveForm = function(){
        api.putUserUpdateData($scope.userId, $scope.userForm, function (result) {
            loadUser();
            swangular.swal("Success", "User data updated.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });

        $scope.formToggle();
    };

    let loadUser = function(){
        api.getUser($scope.userId, function (result) {
            $scope.user = angular.copy(result.user);
            $scope.userForm = angular.copy(result.user);
        }, function(){
            // error handler
        });
    };

    let loadUserPepInfo = function(){
        api.getUserPepInfo($scope.userId, function (result) {
            $scope.pepInfo = result;
        }, function(){
            // error handler
        });
    };

    let loadCountries = function(){
        api.getCountries({'includeDisabled': true}, function(result){
            $scope.countries = result;
        }, function(){
            // error handler
        });
    };
    loadCountries();

    $scope.$on('refreshUserInfo', function() {
        loadUser();
    });

    $scope.loadUserPepInfo = loadUserPepInfo;
    $scope.pepInfo = null;

    $scope.verificationStatusLoading = false;
    $scope.setVerificationStatus = function(status) {
        $scope.verificationStatusLoading = true;

        api.putVerificationStatus($scope.userId, status, function (result) {
            loadUser();
            swangular.swal("Success", "Verification status updated.", "success");

            $scope.verificationStatusLoading = false;
        }, function (result) {
            loadUser();
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");

            $scope.verificationStatusLoading = false;
        });
    };

    $scope.removeUser = function(){
        api.patchRemoveUser($scope.userId,function (result) {
            loadUser();
            swangular.swal("Success", "User removed.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadUser();
    });
}
