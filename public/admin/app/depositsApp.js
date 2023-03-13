let depositsApp = angular.module('depositsApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

depositsApp.factory('bridge', ControllerBridge);
depositsApp.directive('onEnter', onEnterDirective);
depositsApp.controller('DepositListController', ['$scope', 'api', 'listManager', 'routingModule', 'swangular', 'bridge', DepositListController]);

angular.bootstrap(document.getElementById("depositsAppHandler"),["depositsApp"]);