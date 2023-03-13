let orderbookApp = angular.module('orderbookApp', ['apiModule', 'routingModule', 'swangular', 'ngWebSocket']);

orderbookApp.factory('bridge', ControllerBridge);
orderbookApp.directive('onEnter', onEnterDirective);
orderbookApp.controller('OrderbookController', ['$scope', 'api', 'routingModule', 'swangular', '$websocket', 'bridge', OrderbookController]);

angular.bootstrap(document.getElementById("orderbookAppHandler"),["orderbookApp"]);