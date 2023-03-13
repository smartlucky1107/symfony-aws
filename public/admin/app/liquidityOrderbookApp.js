let liquidityOrderbookApp = angular.module('liquidityOrderbookApp', ['apiModule', 'routingModule', 'swangular', 'ngWebSocket']);

liquidityOrderbookApp.factory('bridge', ControllerBridge);
liquidityOrderbookApp.directive('onEnter', onEnterDirective);
liquidityOrderbookApp.controller('LiquidityOrderbookController', ['$scope', 'api', 'routingModule', 'swangular', '$websocket', 'bridge', LiquidityOrderbookController]);

angular.bootstrap(document.getElementById("liquidityOrderbookAppHandler"),["liquidityOrderbookApp"]);