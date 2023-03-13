let dashboardApp = angular.module('dashboardApp', ['apiModule', 'routingModule']);

dashboardApp.controller('DashboardAppController', ['$scope', 'api', 'routingModule', function($scope, api, routingModule){
    $scope.routing = routingModule;

    api.initializeApi();

    let loadLiquidityBalances = function(){
        $scope.liquidityBalances = null;

        api.getLiquidityBalances( function (result){
            $scope.liquidityBalances = result.balances;
        }, function(){
            // error handler
        });
    };
    loadLiquidityBalances();
    $scope.loadLiquidityBalances = loadLiquidityBalances;

    $scope.walletTransfers = null;
    let loadNotProcessedWalletTransfers = function(){
        $scope.walletTransfers = null;

        api.getWalletTransfersNotProcessed( function (result){
            $scope.walletTransfers = result.walletTransfers;
        }, function(){
            // error handler
        });
    };

    $scope.tradingTransactions = null;
    let loadNotProcessedTradingTransactions = function(){
        $scope.tradingTransactions = null;

        api.getTradingTransactionsNotProcessed( function (result){
            $scope.tradingTransactions = result.tradingTransactions;
        }, function(){
            // error handler
        });
    };

    let loadTodayStatistics = function(){
        $scope.todayStatistics = null;

        api.getTodayStatistics( function (result){
            $scope.todayStatistics = result;
        }, function(){
            // error handler
        });
    };

    loadNotProcessedWalletTransfers();
    loadNotProcessedTradingTransactions();
    loadTodayStatistics();

    $scope.pushWalletTransfers = function(){
        loadNotProcessedWalletTransfers();
    };
    $scope.pushTradingTransactions = function(){
        loadNotProcessedTradingTransactions();
    }
}]);

angular.bootstrap(document.getElementById("dashboardAppHandler"),["dashboardApp"]);
