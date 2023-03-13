function NewVoterRoleController($scope, api, swangular, bridge) {
    let validateVoterRoleData = function(callback){
        if(!$scope.voterRoleData.module){
            swangular.swal("Something is wrong", "Module is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.voterRoleData.action){
            swangular.swal("Something is wrong", "Action is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            module : $scope.voterRoleData.module,
            action : $scope.voterRoleData.action,
        });
    };

    let resetVoterRoleData = function(){
        $scope.voterRoleData = {
            module: null,
            action: null
        };
    };
    resetVoterRoleData();

    $scope.createVoterRole = function () {
        validateVoterRoleData(function(data){
            api.postVoterRole(data, function (result) {
                swangular.swal("Success", "Voter role added.", "success");

                resetVoterRoleData();
                bridge.refreshVoterRoles();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };
}