let liquidityReportsApp = angular.module('liquidityReportsApp', ['apiModule', 'routingModule']);

liquidityReportsApp.controller('LiquidityReportsController', ['$scope', 'api', 'routingModule', function($scope, api, routingModule){
    $scope.routing = routingModule;

    api.initializeApi();

    let loadLiquidityReports = function(){
        $scope.liquidityReports = null;

        api.getLiquidityReports( function (result){
            $scope.liquidityReports = result.report;
        }, function(){
            // error handler
        });
    };
    loadLiquidityReports();
}]);

angular.bootstrap(document.getElementById("liquidityReportsAppHandler"),["liquidityReportsApp"]);
