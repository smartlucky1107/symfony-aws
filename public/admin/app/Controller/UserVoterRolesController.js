function UserVoterRolesController($scope, api, swangular){
    $scope.userId = null;
    $scope.userVoterRoles = [];
    $scope.isLoading = true;

    $scope.voterRoles = [];
    $scope.selectedVoterRole = null;

    let loadUserVoterRoles = function(){
        $scope.isLoading = true;
        api.getUserVoterRoles($scope.userId, function (result) {
            $scope.userVoterRoles = result.voterRoles;
            $scope.isLoading = false;
        }, function(){
            // error handler
        });
    };

    let loadVoterRoles = function(){
        api.getVoterRoles({ 'page': 1, 'pageSize': 0, 'sortBy': 'id', 'sortType': 0 }, function (result) {
            $scope.voterRoles = result.result;
        }, function(){
            // error handler
        });
    };

    $scope.deny = function(voterRoleId) {
        api.putUserDeny($scope.userId, voterRoleId, function (result) {
            loadUserVoterRoles();

            swangular.swal("Success", "User voter role denied.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
    
    $scope.grant = function() {
        if(!$scope.userId){
            swangular.swal("Something is wrong", "User is require for this action. Please make sure that the field is filled.", "warning");
            return false;
        }
        if(!$scope.selectedVoterRole){
            swangular.swal("Something is wrong", "Voter role is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if($scope.selectedVoterRole){
            api.putUserGrant($scope.userId, $scope.selectedVoterRole, function (result) {
                loadUserVoterRoles();

                swangular.swal("Success", "User voter role granted.", "success");
            }, function(){
                swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
            });
        }
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadVoterRoles();
        loadUserVoterRoles();
    });
};