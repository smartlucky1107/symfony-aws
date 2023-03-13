let currencyPairListApp = angular.module('currencyPairListApp', ['listModule', 'apiModule', 'swangular']);

currencyPairListApp.controller('currencyPairListAppCtrl', ['$scope', 'api', 'listManager', 'swangular', function($scope, api, listManager, swangular){
    //listManager.initFilterFields([]);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getCurrencyPairs(listManager.options, function (result) {
            listManager.processResult(result);

            $scope.results = result.result;
            $scope.pages = listManager.generatePages(result);
        }, function(){
            // error handler
        });
    };

    $scope.setSortBy = function(field){
        listManager.setSortBy(field, function(){
            refreshList();
        });
    };

    $scope.changePage = function(page){
        listManager.changePage(page, function(){
            refreshList()
        });
    };
    $scope.changePageSize = function(pageSize){
        listManager.changePageSize(pageSize, function(){
            refreshList()
        });
    };
    $scope.search = function(){
        refreshList();
    };

    refreshList();

//// ###################################################################################################################

    $scope.disableCurrencyPair = function(currencyPairId){
        api.putCurrencyPairDisable(currencyPairId, function (result) {
            angular.forEach($scope.results, function(currencyPair){
                if(currencyPair.id === currencyPairId){
                    let index = $scope.results.indexOf(currencyPair);
                    $scope.results[index].enabled = false;
                }
            });

            swangular.swal("Success", "Currency pair disabled.", "success");
        }, function(){
            // error handler
        });
    };

    $scope.enableCurrencyPair = function(currencyPairId){
        api.putCurrencyPairEnable(currencyPairId, function (result) {
            angular.forEach($scope.results, function(currencyPair){
                if(currencyPair.id === currencyPairId){
                    let index = $scope.results.indexOf(currencyPair);
                    $scope.results[index].enabled = true;
                }
            });

            swangular.swal("Success", "Currency pair enabled.", "success");
        }, function(){
            // error handler
        });
    };
}]);

angular.bootstrap(document.getElementById("currencyPairListAppHandler"),["currencyPairListApp"]);
