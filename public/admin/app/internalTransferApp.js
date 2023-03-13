let internalTransferApp = angular.module('internalTransferApp', ['apiModule', 'routingModule', 'swangular']);

internalTransferApp.factory('bridge', ControllerBridge);
internalTransferApp.controller('InternalTransferAppController', ['$scope', 'api', 'swangular', 'bridge', 'routingModule', function($scope, api, swangular, bridge, routingModule){
    $scope.routing = routingModule;

    setTimeout(function () {
        bridge.initLoadedInternalTransfer($internalTransferId);
    }, 200);

    $scope.internalTransferId = null;
    $scope.internalTransfer = null;

    let loadInternalTransfer = function(){
        api.getInternalTransfer($scope.internalTransferId, function (result) {
            $scope.internalTransfer = result.internalTransfer;
        }, function(){
            // error handler
        });
    };

    $scope.$on('refreshInternalTransferInfo', function() {
        loadInternalTransfer();
    });

    $scope.$on('loadedInternalTransfer', function(event, args) {
        $scope.internalTransferId = args.internalTransferId;
        loadInternalTransfer();
    });

    $scope.declineInternalTransfer = function(internalTransferId){
        api.putInternalTransferDecline(internalTransferId, function (result) {
            loadInternalTransfer();

            swangular.swal("Success", "Internal transfer declined.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.approveInternalTransfer = function(internalTransferId){
        api.putInternalTransferApprove(internalTransferId, function (result) {
            loadInternalTransfer();

            swangular.swal("Success", "Internal transfer approved.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.rejectInternalTransfer = function(internalTransferId){
        api.putInternalTransferReject(internalTransferId, function (result) {
            loadInternalTransfer();

            swangular.swal("Success", "Internal transfer rejected.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.revertInternalTransfer = function(internalTransferId){
        api.putInternalTransferRevert(internalTransferId, function (result) {
            loadInternalTransfer();

            swangular.swal("Success", "Internal transfer reverted.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
}]);

angular.bootstrap(document.getElementById("internalTransferAppHandler"),["internalTransferApp"]);
