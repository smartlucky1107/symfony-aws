function UserCommunicationController($scope, api, swangular){
    $scope.userId = null;

    $scope.allowedNotificationTypes = [
        { id: 301, name: 'TYPE_USER_REGISTERED' },
        { id: 303, name: 'TYPE_USER_EMAIL_CONFIRMED' },
        { id: 305, name: 'TYPE_USER_BANK_ACCOUNT_APPROVED' },
        { id: 310, name: 'TYPE_USER_TIER2_APPROVED' },
        { id: 311, name: 'TYPE_USER_TIER3_APPROVED' },
];

    $scope.resend = function(notificationTypeId){
        api.putUserResendEmailNotification($scope.userId, notificationTypeId, function (result) {
            swangular.swal("Success", "Notification resent.", "success");
        }, function (result) {
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };

    $scope.$on('loadedUser', function(event, args) {
        $scope.userId = args.userId;
    });
};
