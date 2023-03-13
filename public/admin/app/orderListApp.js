let orderListApp = angular.module('orderListApp', ['listModule', 'apiModule', 'routingModule']);

orderListApp.directive('onEnter', onEnterDirective);
orderListApp.controller('orderListAppCtrl', ['$scope', 'api', 'listManager', 'routingModule', function($scope, api, listManager, routingModule){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getOrders(listManager.options, function (result) {
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

angular.bootstrap(document.getElementById("orderListAppHandler"),["orderListApp"]);