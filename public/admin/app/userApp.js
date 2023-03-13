let userApp = angular.module('userApp', ['apiModule', 'routingModule', 'swangular', 'thatisuday.dropzone']);

// TODO - to chyba można usunąć?  PRZETESTOWAC
userApp.config(function(dropzoneOpsProvider){
    dropzoneOpsProvider.setOptions({
        url : '',
        maxFilesize : '5',
        addRemoveLinks : true,
        //autoProcessQueue: false
    });
});

userApp.factory('bridge', ControllerBridge);
userApp.controller('UserAppController', ['$scope', 'api', 'swangular', 'bridge', function($scope, api, swangular, bridge){
    setTimeout(function () {
        bridge.initLoadedUser($userId);
    }, 200);

    $scope.userId = null;
    $scope.user = null;

    let loadUser = function(){
        api.getUser($scope.userId, function (result) {
            $scope.user = angular.copy(result.user);
        }, function(){
            // error handler
        });
    };

    $scope.$on('refreshUserInfo', function() {
        loadUser();
    });

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
        loadUser();
    });

    $scope.tags = [
        'FIAT_TRADE_SUSPENDED', 'CRYPTO_TRADE_SUSPENDED',
        'FIAT_WITHDRAWAL_SUSPENDED', 'CRYPTO_WITHDRAWAL_SUSPENDED',
        'FIAT_DEPOSIT_SUSPENDED', 'CRYPTO_DEPOSIT_SUSPENDED'
    ];
    $scope.selectedTag = $scope.tags[0];

    $scope.assignTag = function(tag){
        api.putUserTagAssign($scope.userId, tag, function (result) {
            loadUser();
            swangular.swal("Success", "Tag assigned.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
    $scope.unassignTag = function(tag){
        api.putUserTagUnassign($scope.userId, tag, function (result) {
            loadUser();
            swangular.swal("Success", "Tag unassigned.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.toggleEmailConfirmed = function(){
        api.putUserToggleEmailConfirmed($scope.userId, function (result) {
            loadUser();
            swangular.swal("Success", "Toggled with success.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
    $scope.toggleTradingEnabled = function(){
        api.putUserToggleTradingEnabled($scope.userId, function (result) {
            loadUser();
            swangular.swal("Success", "Toggled with success.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.disableGAuth = function(){
        api.putUserGAuthDisable($scope.userId, function (result) {
            loadUser();
            swangular.swal("Success", "Google Authenticator disabled", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.resendConfirmation = function(){
        api.putUserResendConfirmation($scope.userId, function (result) {
            loadUser();
            swangular.swal("Success", "Confirmation resent.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
}]);
userApp.controller('UserVoterRolesController', ['$scope', 'api', 'swangular', UserVoterRolesController]);
userApp.controller('UserApiKeysController', ['$scope', 'api', 'swangular', UserApiKeysController]);
userApp.controller('UserLoginHistoryController', ['$scope', 'api', 'swangular', UserLoginHistoryController]);
userApp.controller('UserPendingOrdersController', ['$scope', 'api', 'routingModule', 'swangular', UserPendingOrdersController]);
userApp.controller('UserDepositsController', ['$scope', 'api', 'routingModule', 'swangular', UserDepositsController]);
userApp.controller('UserWithdrawalsController', ['$scope', 'api', 'routingModule', 'swangular', UserWithdrawalsController]);
userApp.controller('UserCommunicationController', ['$scope', 'api', 'swangular', UserCommunicationController]);
userApp.controller('UserBanksController', ['$scope', 'api', 'swangular', UserBanksController]);
userApp.controller('UserInfoController', ['$scope', 'api', 'swangular', UserInfoController]);

angular.bootstrap(document.getElementById("userAppHandler"),["userApp"]);
