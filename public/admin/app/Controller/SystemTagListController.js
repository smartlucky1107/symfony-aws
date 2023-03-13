function SystemTagListController($scope, api, listManager, routingModule, swangular, bridge){
    $scope.routing = routingModule;

    //listManager.initFilterFields(['id']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getSystemTags(listManager.options, function (result) {
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

    $scope.$on('refreshSystemTags', function() {
        refreshList();
    });


    $scope.toggle = function(systemTagId) {
        api.putSystemTagToggle(systemTagId, function (result) {
            refreshList();

            swangular.swal("Success", "System tag updated.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Action not allowed, please try again.", "warning");
        });
    };
};