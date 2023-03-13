let checkoutOrdersApp = angular.module('checkoutOrdersApp', ['listModule', 'apiModule', 'routingModule']);

checkoutOrdersApp.factory('bridge', ControllerBridge);
checkoutOrdersApp.directive('onEnter', onEnterDirective);
checkoutOrdersApp.controller('CheckoutOrderListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', CheckoutOrderListController]);

angular.bootstrap(document.getElementById("checkoutOrdersAppHandler"),["checkoutOrdersApp"]);
