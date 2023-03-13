let tradeListApp = angular.module('tradeListApp', ['listModule', 'apiModule', 'routingModule']);

tradeListApp.directive('onEnter', onEnterDirective);
tradeListApp.controller('tradeListAppCtrl', ['$scope', 'api', 'listManager', 'routingModule', function($scope, api, listManager, routingModule){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getTrades(listManager.options, function (result) {
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
}]);

angular.bootstrap(document.getElementById("tradeListAppHandler"),["tradeListApp"]);