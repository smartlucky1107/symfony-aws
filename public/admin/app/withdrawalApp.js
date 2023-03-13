let withdrawalApp = angular.module('withdrawalApp', ['apiModule', 'routingModule', 'swangular']);

withdrawalApp.factory('bridge', ControllerBridge);
withdrawalApp.controller('WalletInfoController', ['$scope', 'api', WalletInfoController]);
withdrawalApp.controller('WithdrawalAppController', ['$scope', 'api', 'swangular', 'bridge', 'routingModule', function($scope, api, swangular, bridge, routingModule){
    $scope.routing = routingModule;

    setTimeout(function () {
        bridge.initLoadedWithdrawal($withdrawalId);
    }, 200);

    $scope.withdrawalId = null;
    $scope.withdrawal = null;

    let loadWithdrawal = function(){
        api.getWithdrawal($scope.withdrawalId, function (result) {
            $scope.withdrawal = result.withdrawal;

            setTimeout(function () {
                if(result.withdrawal.wallet.id){
                    bridge.initLoadedWallet(result.withdrawal.wallet.id);
                }
            }, 200);
        }, function(){
            // error handler
        });
    };

    $scope.$on('refreshWithdrawalInfo', function() {
        loadWithdrawal();
    });

    $scope.$on('loadedWithdrawal', function(event, args) {
        $scope.withdrawalId = args.withdrawalId;
        loadWithdrawal();
    });

    $scope.sendForExternalApproval = function(withdrawalId){
        api.putWithdrawalSendForExternalApproval(withdrawalId, function (result) {
            loadWithdrawal();

            swangular.swal("Success", "Withdrawal sent for external approval.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Approval not allowed, please try again.", "warning");
        });
    };

    $scope.declineWithdrawal = function(withdrawalId){
        api.putWithdrawalDecline(withdrawalId, function (result) {
            loadWithdrawal();

            swangular.swal("Success", "Withdrawal declined.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.approveWithdrawal = function(withdrawalId){
        api.putWithdrawalApprove(withdrawalId, function (result) {
            loadWithdrawal();

            swangular.swal("Success", "Withdrawal approved.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.rejectWithdrawal = function(withdrawalId){
        api.putWithdrawalReject(withdrawalId, function (result) {
            loadWithdrawal();

            swangular.swal("Success", "Withdrawal rejected.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
}]);

angular.bootstrap(document.getElementById("withdrawalAppHandler"),["withdrawalApp"]);
