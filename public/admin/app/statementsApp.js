let statementsApp = angular.module('statementsApp', ['apiModule', 'routingModule']);

statementsApp.controller('StatementsController', ['$scope', 'api', 'routingModule', function($scope, api, routingModule){
    $scope.routing = routingModule;

    api.initializeApi();

    $scope.balancesTypeChoices = [
        { id: 'user', label: 'Real user balances' },
        { id: 'fee', label: 'Fee balances' },
    ];
    $scope.balancesType = $scope.balancesTypeChoices[0].id;

    let loadBalances = function(){
        $scope.balances = null;

        api.getBalances($scope.balancesType, function (result){
            $scope.balances = result.balances;
        }, function(){
            // error handler
        });
    };
    $scope.loadBalances = loadBalances;

    $scope.incomingFeesFrom = '2020-01-01';
    $scope.incomingFeesTo = '2020-02-01';

    let loadIncomingFees = function(){
        $scope.incomingFees = null;

        api.getIncomingFees($scope.incomingFeesFrom, $scope.incomingFeesTo, function (result){
            $scope.incomingFees = result.fees;
        }, function(){
            // error handler
        });
    };
    $scope.loadIncomingFees = loadIncomingFees;

    loadBalances();
    loadIncomingFees();
}]);

angular.bootstrap(document.getElementById("statementsAppHandler"),["statementsApp"]);
