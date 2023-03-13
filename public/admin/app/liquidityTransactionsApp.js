let liquidityTransactionsApp = angular.module('liquidityTransactionsApp', ['apiModule', 'routingModule']);

liquidityTransactionsApp.controller('LiquidityTransactionsController', ['$scope', 'api', 'routingModule', function($scope, api, routingModule){
    $scope.routing = routingModule;

    api.initializeApi();

    $scope.currencyPairId = 1;
    $scope.from = '2020-01-01';
    $scope.to = 'now';

    let loadLiquidityTransactions = function(){
        $scope.liquidityTransactions = null;

        api.getLiquidityTransactions( $scope.currencyPairId, $scope.from, $scope.to,function (result){
            $scope.liquidityTransactions = result.liquidityTransactions;
        }, function(){
            // error handler
        });
    };
    loadLiquidityTransactions();

    $scope.loadLiquidityTransactions = loadLiquidityTransactions;

    $scope.calculateProfit = function(loadLiquidityTransactionArray){
        // TODO
    };
}]);

angular.bootstrap(document.getElementById("liquidityTransactionsAppHandler"),["liquidityTransactionsApp"]);
