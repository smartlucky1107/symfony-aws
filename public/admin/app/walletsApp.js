let walletsApp = angular.module('walletsApp', ['listModule', 'apiModule', 'routingModule']);

walletsApp.factory('bridge', ControllerBridge);
walletsApp.directive('onEnter', onEnterDirective);
walletsApp.controller('WalletListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', WalletListController]);

angular.bootstrap(document.getElementById("walletsAppHandler"),["walletsApp"]);