function DepositListController($scope, api, listManager, routingModule, swangular, bridge){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id', 'status', 'walletId', 'excludeUser', 'bankTransaction']);
    $scope.initFilterValues = function(status, walletId, excludeUser){
        listManager.filters.status = status;
        listManager.filters.walletId = walletId;
        listManager.filters.excludeUser = excludeUser;

        refreshList();
    };

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getDeposits(listManager.options, function (result) {
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

    $scope.$on('refreshDepositRequests', function() {
        refreshList();
    });
};