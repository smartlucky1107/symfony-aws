let depositApp = angular.module('depositApp', ['apiModule', 'routingModule', 'swangular']);

depositApp.factory('bridge', ControllerBridge);
depositApp.controller('DepositAppController', ['$scope', 'api', 'routingModule', 'swangular', 'bridge', function($scope, api, routingModule, swangular, bridge){
    $scope.routing = routingModule;

    setTimeout(function () {
        bridge.initLoadedDeposit($depositId);
    }, 200);

    $scope.depositId = null;
    $scope.deposit = null;
    $scope.depositEthereumBlockchainTx = null;
    $scope.depositBitcoinBlockchainTx = null;

    let loadDeposit = function(){
        api.getDeposit($scope.depositId, function (result) {
            $scope.deposit = result.deposit;
        }, function(){
            // error handler
        });
    };

    let loadDepositBlockchainTx = function(){
        api.getDepositBlockchainTx($scope.depositId, function (result) {
            $scope.depositEthereumBlockchainTx = result.ethereumBlockchainTx;
            $scope.depositBitcoinBlockchainTx = result.bitcoinBlockchainTx;
        }, function(){
            // error handler
        });
    };

    $scope.$on('refreshDepositInfo', function() {
        loadDeposit();
        loadDepositBlockchainTx();
    });

    $scope.$on('loadedDeposit', function(event, args) {
        $scope.depositId = args.depositId;
        loadDeposit();
        loadDepositBlockchainTx();
    });

    $scope.approveDeposit = function(depositId){
        api.putDepositApprove(depositId, function (result) {
            loadDeposit();
            //bridge.depositApproved();
            //bridge.refreshWalletInfo();

            swangular.swal("Success", "Deposit approved.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Approval not allowed, please try again.", "warning");
        });
    };

    $scope.declineDeposit = function(depositId){
        api.putDepositDecline(depositId, function (result) {
            loadDeposit();

            swangular.swal("Success", "Deposit declined.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.revertDeposit = function(depositId){
        api.putDepositRevert(depositId, function (result) {
            loadDeposit();

            swangular.swal("Success", "Deposit reverted.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
}]);

angular.bootstrap(document.getElementById("depositAppHandler"),["depositApp"]);
