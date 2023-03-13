let loginApp = angular.module('loginApp', ['apiModule', 'routingModule', 'swangular', 'vcRecaptcha']);

loginApp.directive('onEnter', onEnterDirective);
loginApp.controller('LoginAppController', ['$scope', 'api', 'swangular', 'routingModule', 'vcRecaptchaService', function($scope, api, swangular, routingModule, vcRecaptchaService){
    $scope.response = null;
    $scope.widgetId = null;
    $scope.model = {
        key: '6LdfsCcdAAAAAM2nrocMktH4Q6iG24mOlPBgj3Wp'
    };
    $scope.setResponse = function (response) {
        $scope.reCaptcha = response;
    };
    $scope.setWidgetId = function (widgetId) {
        $scope.widgetId = widgetId;
    };
    $scope.reCaptcha = null;

///////////// reCaptcha

    let resetLoginForm = function(){
        $scope.loginData = {
            username: null,
            password: null
        };
    };
    resetLoginForm();

    let validateLogin = function(callback){
        if(!$scope.loginData.username){
            $scope.isLoginProcessing = false;

            swangular.swal("Something is wrong", "Email is required. Please make sure that the field is filled.", "warning");
            return false;
        }
        if(!$scope.loginData.password){
            $scope.isLoginProcessing = false;

            swangular.swal("Something is wrong", "Password is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            username: $scope.loginData.username,
            password: $scope.loginData.password,
            reCaptcha: $scope.reCaptcha
        });
    };

    $scope.isLoginProcessing = false;
    $scope.login = function(){
        $scope.isLoginProcessing = true;
        validateLogin(function(data){
            api.login(data.username, data.password, data.reCaptcha, function(result){
                $scope.isLoginProcessing = false;

                routingModule.openDashboard();
            }, function(result){
                $scope.isLoginProcessing = false;

                swangular.swal("Something is wrong", "Login failed. Please correct the form and try again.", "warning");
            });
        });
    };
}]);

angular.bootstrap(document.getElementById("loginAppHandler"),["loginApp"]);
