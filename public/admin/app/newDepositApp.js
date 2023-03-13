let newDepositApp = angular.module('newDepositApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

newDepositApp.factory('bridge', ControllerBridge);
newDepositApp.directive('onEnter', onEnterDirective);
newDepositApp.controller('WalletListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', WalletListController]);
newDepositApp.controller('NewDepositController', ['$scope', 'api', 'swangular', 'bridge', NewDepositController]);

angular.bootstrap(document.getElementById("newDepositAppHandler"),["newDepositApp"]);