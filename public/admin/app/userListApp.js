let userListApp = angular.module('userListApp', ['listModule', 'apiModule', 'routingModule']);

userListApp.directive('onEnter', onEnterDirective);
userListApp.controller('userListAppCtrl', ['$scope', 'api', 'listManager', 'routingModule', function($scope, api, listManager, routingModule){
    $scope.routing = routingModule;

    listManager.initFilterFields(['id', 'email', 'firstName', 'lastName', 'isFilesSent', 'verificationStatus']);

    $scope.verificationStatusChoices = [
        { id: null, name: 'Tier 1 waiting' },
        { id: 1, name: 'Tier 1 pending' },
        { id: 2, name: 'Tier 1 approved' },
        { id: 3, name: 'Tier 1 declined' },
        { id: 4, name: 'Tier 2 pending' },
        { id: 5, name: 'Tier 2 approved' },
        { id: 6, name: 'Tier 2 declined' },
        { id: 7, name: 'Tier 3 pending' },
        { id: 8, name: 'Tier 3 approved' },
        { id: 9, name: 'Tier 3 declined' },
    ];
    $scope.verificationStatus = $scope.verificationStatusChoices[0];
    $scope.setVerificationStatus = function(statusChoice){
        listManager.filters.verificationStatus = statusChoice.id;
    };

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getUsers(listManager.options, function (result) {
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

angular.bootstrap(document.getElementById("userListAppHandler"),["userListApp"]);