let POSOrdersApp = angular.module('POSOrdersApp', ['listModule', 'apiModule', 'routingModule']);

POSOrdersApp.factory('bridge', ControllerBridge);
POSOrdersApp.directive('onEnter', onEnterDirective);
POSOrdersApp.controller('POSOrderListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', POSOrderListController]);

angular.bootstrap(document.getElementById("POSOrdersAppHandler"),["POSOrdersApp"]);
