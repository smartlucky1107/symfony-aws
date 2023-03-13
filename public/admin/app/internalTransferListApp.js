let internalTransferListApp = angular.module('internalTransferListApp', ['listModule', 'apiModule', 'routingModule']);

internalTransferListApp.directive('onEnter', onEnterDirective);
internalTransferListApp.controller('InternalTransferListAppCtrl', ['$scope', 'api', 'listManager', 'routingModule', function($scope, api, listManager, routingModule){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id', 'status', 'address']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getInternalTransfers(listManager.options, function (result) {
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

angular.bootstrap(document.getElementById("internalTransferListAppHandler"),["internalTransferListApp"]);
