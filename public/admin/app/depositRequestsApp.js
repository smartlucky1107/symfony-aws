let depositRequestsApp = angular.module('depositRequestsApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

depositRequestsApp.factory('bridge', ControllerBridge);
depositRequestsApp.directive('onEnter', onEnterDirective);
depositRequestsApp.controller('DepositListController', ['$scope', 'api', 'listManager', 'routingModule', 'swangular', 'bridge', DepositListController]);

angular.bootstrap(document.getElementById("depositRequestsAppHandler"),["depositRequestsApp"]);