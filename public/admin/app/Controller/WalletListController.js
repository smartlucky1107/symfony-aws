function WalletListController($scope, api, listManager, routingModule, bridge){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id', 'name']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        // clear loaded wallet
        bridge.initLoadedWallet(null);

        listManager.filtersApply();

        api.getWallets(listManager.options, function (result) {
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

    $scope.setForDeposit = function(walletId){
        bridge.initLoadedWallet(walletId);

        let singleResult = null;
        angular.forEach($scope.results, function(wallet){
            if(wallet.id === walletId){
                singleResult = (wallet);
            }
        });

        // clear search results
        $scope.results = [singleResult];
        $scope.pages = [];
    };
};